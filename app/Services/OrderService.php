<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\AsaasService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private AsaasService $asaasService
    ) {}

    public function createOrder(array $orderData): array
    {
        DB::beginTransaction();

        try {
            $customer = $this->processCustomer($orderData['customer']);
            $items = collect($orderData['items']);
            $products = $this->productRepository->getProductsByIds($items->pluck('product_id')->toArray());
            
            $subtotal = $this->calculateSubtotal($items, $products);
            $total = $subtotal;
            $order = $this->orderRepository->createOrder($orderData, $customer->id, $subtotal, $total);
            
            $this->orderRepository->createOrderItems($order, $items, $products);

            $paymentResponse = $this->processPayment($order, $orderData, $customer, $total);
            
            $this->orderRepository->updateOrderPayment($order, $paymentResponse['id'], $paymentResponse['status']);

            DB::commit();

            return $this->prepareResponse($order, $orderData, $paymentResponse);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $orderData,
            ]);

            throw $e;
        }
    }

    private function processCustomer(array $customerData)
    {
        $customer = $this->customerRepository->firstOrCreate($customerData);
        
        if (!$customer->asaas_customer_id) {
            $asaasCustomer = $this->asaasService->createCustomer($customerData);
            $this->customerRepository->updateAsaasCustomerId($customer, $asaasCustomer['id']);
        }
        
        return $customer;
    }

    private function calculateSubtotal(Collection $items, Collection $products): float
    {
        return $items->sum(function ($item) use ($products) {
            $product = $products->find($item['product_id']);
            return $product->price * $item['quantity'];
        });
    }

    private function processPayment(Order $order, array $orderData, $customer, float $total): array
    {
        $paymentData = [
            'customer_id'             => $customer->asaas_customer_id,
            'customer_email'          => $customer->email,
            'customer_phone'          => $customer->phone,
            'customer_postal_code'    => $customer->address['zip_code'] ?? null,
            'customer_address_number' => $customer->address['number'] ?? null,
            'payment_method'          => $orderData['payment_method'],
            'total'                   => $total,
            'description'             => "Pedido #{$order->order_number}",
            'order_number'            => $order->order_number,
            'remote_ip'               => request()->ip(),
        ];

        if ($orderData['payment_method'] === 'credit_card') {
            $paymentData['installments'] = $orderData['installments'] ?? 1;
            $paymentData['credit_card'] = $orderData['credit_card'];
            $paymentData['totalValue'] = $total / 100;
        }

        return $this->asaasService->createPayment($paymentData);
    }

    private function prepareResponse(Order $order, array $orderData, array $paymentResponse): array
    {
        $response = ['order' => $order];

        if ($orderData['payment_method'] === 'pix') {
            $pixData = $this->asaasService->getPixQrCode($paymentResponse['id']);
            $response['pix'] = $pixData;
        } elseif ($orderData['payment_method'] === 'boleto') {
            $boletoData = $this->asaasService->getBoletoIdentificationField($paymentResponse['id']);
            $response['boleto'] = [
                'identificationField' => $boletoData['identificationField'] ?? null,
                'barCode'             => $boletoData['barCode'] ?? null,
                'bankSlipUrl'         => $paymentResponse['bankSlipUrl'] ?? null,
                'dueDate'             => $paymentResponse['dueDate'] ?? null,
            ];
        } elseif ($orderData['payment_method'] === 'credit_card') {
            $response['credit_card'] = [
                'status'     => $paymentResponse['status'],
                'authorized' => in_array($paymentResponse['status'], ['CONFIRMED', 'RECEIVED']),
                'message'    => $this->getCreditCardStatusMessage($paymentResponse['status']),
                'last4'      => substr($orderData['credit_card']['number'], -4),
                'brand'      => $paymentResponse['creditCard']['creditCardBrand'] ?? null,
            ];
        }

        return $response;
    }

    private function getCreditCardStatusMessage(string $status): string
    {
        $messages = [
            'CONFIRMED'  => 'Pagamento aprovado',
            'RECEIVED'   => 'Pagamento recebido',
            'PENDING'    => 'Pagamento pendente',
            'REFUNDED'   => 'Pagamento estornado',
            'REFUSED'    => 'Pagamento recusado',
            'CHARGEBACK' => 'Pagamento contestado',
        ];

        return $messages[$status] ?? 'Status desconhecido';
    }

    public function getUserOrders(int $userId)
    {
        return $this->orderRepository->getUserOrders($userId);
    }
}
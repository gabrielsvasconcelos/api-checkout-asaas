<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private AsaasService $asaasService)
    {
    }

    public function store(OrderRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $customerData = $request->input('customer');
            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email']],
                [
                    'name'     => $customerData['name'],
                    'document' => $customerData['document'],
                    'phone'    => $customerData['phone'],
                    'address'  => $customerData['address'] ?? null,
                ]
            );

            $asaasCustomer = $this->asaasService->createCustomer($customerData);
            $customer->update(['asaas_customer_id' => $asaasCustomer['id']]);

            $items    = collect($request->input('items'));
            $products = Product::whereIn('id', $items->pluck('product_id'))->get();
            $subtotal = $items->sum(function ($item) use ($products) {
                $product = $products->find($item['product_id']);
                return $product->price * $item['quantity'];
            });
            $total = $subtotal;

            $order = Order::create([
                'order_number'   => 'ORD-' . Str::random(8),
                'customer_id'    => $customer->id,
                'user_id'        => Auth::id(),
                'subtotal'       => $subtotal,
                'total'          => $total,
                'payment_method' => $request->input('payment_method'),
                'status'         => 'pending',
            ]);

            foreach ($items as $item) {
                $product = $products->find($item['product_id']);

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            $paymentData = [
                'customer_id'             => $asaasCustomer['id'],
                'customer_email'          => $customerData['email'],
                'customer_phone'          => $customerData['phone'],
                'customer_postal_code'    => $customerData['address']['zip_code'] ?? null,
                'customer_address_number' => $customerData['address']['number'] ?? null,
                'payment_method'          => $request->input('payment_method'),
                'total'                   => $total, // em centavos
                'description'             => "Pedido #{$order->order_number}",
                'order_number'            => $order->order_number,
                'remote_ip'               => $request->ip(),
            ];

            if ($request->input('payment_method') === 'credit_card') {
                $installments = $request->input('installments', 1);
                $paymentData['credit_card'] = $request->input('credit_card');
                $paymentData['installments'] = $installments;
                $paymentData['totalValue'] = $total / 100;
            }

            $payment = $this->asaasService->createPayment($paymentData);

            $order->update([
                'asaas_payment_id' => $payment['id'],
                'status'           => $payment['status'],
            ]);

            $response = ['order' => $order];

            if ($request->input('payment_method') === 'pix') {
                $pixData = $this->asaasService->getPixQrCode($payment['id']);
                $response['pix'] = $pixData;
            } elseif ($request->input('payment_method') === 'boleto') {
                $boletoData = $this->asaasService->getBoletoIdentificationField($payment['id']);
                $response['boleto'] = [
                    'identificationField' => $boletoData['identificationField'] ?? null,
                    'barCode'             => $boletoData['barCode'] ?? null,
                    'bankSlipUrl'         => $payment['bankSlipUrl'] ?? null,
                    'dueDate'             => $payment['dueDate'] ?? null,
                ];
            } elseif ($request->input('payment_method') === 'credit_card') {
                $response['credit_card'] = [
                    'status'     => $payment['status'],
                    'authorized' => in_array($payment['status'], ['CONFIRMED', 'RECEIVED']),
                    'message'    => $this->getCreditCardStatusMessage($payment['status']),
                    'last4'      => substr($request->input('credit_card.number'), -4),
                    'brand'      => $payment['creditCard']['creditCardBrand'] ?? null,
                ];
            }

            DB::commit();
            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            $statusCode = $e->getCode() >= 400 && $e->getCode() < 500 ? $e->getCode() : 500;

            return response()->json([
                'message' => 'Order creation failed',
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ], $statusCode);
        }
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'order' => $order->load(['customer', 'items.product']),
        ]);
    }

    public function myOrders(): JsonResponse
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['customer', 'items.product'])
            ->get();

        return response()->json($orders);
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
}

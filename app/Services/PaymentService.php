<?php

namespace App\Services;

use App\Models\Order;
use App\Services\AsaasService;

class PaymentService
{
    public function __construct(private AsaasService $asaasService) {}

    public function processPayment(Order $order, array $paymentData): array
    {
        $response = ['order' => $order];

        if ($paymentData['payment_method'] === 'pix') {
            $pixData = $this->asaasService->getPixQrCode($order->asaas_payment_id);
            $response['pix'] = $pixData;
        } elseif ($paymentData['payment_method'] === 'boleto') {
            $boletoData = $this->asaasService->getBoletoIdentificationField($order->asaas_payment_id);
            $response['boleto'] = [
                'identificationField' => $boletoData['identificationField'] ?? null,
                'barCode'             => $boletoData['barCode'] ?? null,
                'bankSlipUrl'         => $boletoData['bankSlipUrl'] ?? null,
                'dueDate'             => $boletoData['dueDate'] ?? null,
            ];
        } elseif ($paymentData['payment_method'] === 'credit_card') {
            $response['credit_card'] = [
                'status'     => $order->status,
                'authorized' => in_array($order->status, ['CONFIRMED', 'RECEIVED']),
                'message'    => $this->getCreditCardStatusMessage($order->status),
                'last4'      => substr($paymentData['credit_card']['number'], -4),
                'brand'      => $paymentData['credit_card']['brand'] ?? null,
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
}
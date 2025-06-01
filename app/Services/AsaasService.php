<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.asaas.base_url');
        $this->apiKey  = config('services.asaas.api_key');
    }

    private function sendRequest(string $method, string $endpoint, array $data = []): array
    {
        $fullUrl = $this->baseUrl . $endpoint;

        $response = Http::withHeaders([
            'accept'       => 'application/json',
            'content-type' => 'application/json',
            'access_token' => $this->apiKey,
        ])->{$method}($fullUrl, $data);

        $status       = $response->status();
        $responseData = $response->json();

        if ($response->failed()) {
            $errors       = $responseData['errors'] ?? [['description' => 'Unknown Asaas error']];
            $errorMessages = array_map(fn($e) => $e['description'], $errors);
            $errorMessage = implode('; ', $errorMessages);

            throw new \Exception($errorMessage, $status);
        }

        return $responseData;
    }

    public function createCustomer(array $customerData): array
    {
        $data = [
            'name'                 => $customerData['name'],
            'email'                => $customerData['email'],
            'mobilePhone'          => $customerData['phone'],
            'cpfCnpj'              => $customerData['document'],
            'notificationDisabled' => true,
        ];

        if (isset($customerData['address'])) {
            $address = $customerData['address'];
            $data = array_merge($data, [
                'address'       => $address['street'] ?? null,
                'addressNumber' => $address['number'] ?? null,
                'province'      => $address['neighborhood'] ?? null,
                'postalCode'    => $address['zip_code'] ?? null,
            ]);
        }

        return $this->sendRequest('post', '/v3/customers', $data);
    }

    public function createPayment(array $data): array
    {
        $paymentData = [
            'customer'          => $data['customer_id'],
            'billingType'       => $this->mapBillingType($data['payment_method']),
            'value'             => $data['total'] / 100,
            'dueDate'           => now()->addDays(3)->format('Y-m-d'),
            'description'       => $data['description'],
            'externalReference' => $data['order_number'],
            'remoteIp'          => $data['remote_ip'],
        ];

        if ($data['payment_method'] === 'credit_card' && isset($data['credit_card'])) {
            $installmentCount = $data['installments'] ?? 1;
            $totalInReais     = $data['total'] / 100;

            $paymentData = array_merge($paymentData, [
                'installmentCount' => $installmentCount,
                'totalValue'       => $totalInReais,
            ]);

            $paymentData['creditCard'] = [
                'holderName'  => $data['credit_card']['holder_name'],
                'number'      => $data['credit_card']['number'],
                'expiryMonth' => $data['credit_card']['expiry_month'],
                'expiryYear'  => $data['credit_card']['expiry_year'],
                'ccv'         => $data['credit_card']['cvv'],
            ];

            $paymentData['creditCardHolderInfo'] = [
                'name'          => $data['credit_card']['holder_name'],
                'email'         => $data['customer_email'] ?? null,
                'cpfCnpj'       => $data['credit_card']['holder_document'] ?? null,
                'postalCode'    => $data['customer_postal_code'] ?? null,
                'addressNumber' => $data['customer_address_number'] ?? null,
                'phone'         => $data['customer_phone'] ?? null,
            ];
        }

        return $this->sendRequest('post', '/v3/payments', $paymentData);
    }

    private function mapBillingType(string $method): string
    {
        return match (strtolower($method)) {
            'pix'         => 'PIX',
            'boleto'      => 'BOLETO',
            'credit_card' => 'CREDIT_CARD',
            default       => strtoupper($method),
        };
    }

    public function getPixQrCode(string $paymentId): array
    {
        return $this->sendRequest('get', "/v3/payments/{$paymentId}/pixQrCode");
    }

    public function getBoletoIdentificationField(string $paymentId): array
    {
        return $this->sendRequest('get', "/v3/payments/{$paymentId}/identificationField");
    }
}

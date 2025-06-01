<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function firstOrCreate(array $customerData): Customer
    {
        return Customer::firstOrCreate(
            ['email' => $customerData['email']],
            [
                'name'     => $customerData['name'],
                'document' => $customerData['document'],
                'phone'    => $customerData['phone'],
                'address'  => $customerData['address'] ?? null,
            ]
        );
    }

    public function updateAsaasCustomerId(Customer $customer, string $asaasCustomerId): void
    {
        $customer->update(['asaas_customer_id' => $asaasCustomerId]);
    }
}
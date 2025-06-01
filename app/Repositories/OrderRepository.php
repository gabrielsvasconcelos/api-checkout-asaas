<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderRepository
{
    public function createOrder(array $orderData, int $customerId, float $subtotal, float $total): Order
    {
        return Order::create([
            'order_number'   => 'ORD-' . Str::random(8),
            'customer_id'    => $customerId,
            'user_id'       => Auth::id(),
            'subtotal'      => $subtotal,
            'total'         => $total,
            'payment_method' => $orderData['payment_method'],
            'status'        => 'pending',
        ]);
    }

    public function updateOrderPayment(Order $order, string $asaasPaymentId, string $status): void
    {
        $order->update([
            'asaas_payment_id' => $asaasPaymentId,
            'status'          => $status,
        ]);
    }

    public function createOrderItems(Order $order, Collection $items, Collection $products): void
    {
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
    }

    public function getUserOrders(int $userId)
    {
        return Order::where('user_id', $userId)
            ->with(['customer', 'items.product'])
            ->get();
    }
}
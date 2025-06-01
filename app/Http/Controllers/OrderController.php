<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(OrderRequest $request): JsonResponse
    {
        try {
            $orderData = $request->validated();
            $response = $this->orderService->createOrder($orderData);
            
            return response()->json($response, 201);
        } catch (\Exception $e) {
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
            'order' => new OrderResource($order->load(['customer', 'items.product'])),
        ]);
    }

    public function myOrders(): JsonResponse
    {
        $orders = $this->orderService->getUserOrders(auth()->id());
        return response()->json(OrderResource::collection($orders));
    }
}
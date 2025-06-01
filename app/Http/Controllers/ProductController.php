<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $productData = $request->validated();
        $productData['user_id'] = Auth::id();
        $product = Product::create($productData);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        if (Auth::id() !== $product->user_id) {
            abort(403, 'Unauthorized action.');
        }
        
        $product->update($request->validated());
        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        if (Auth::id() !== $product->user_id) {
            abort(403, 'Unauthorized action.');
        }
        
        $product->delete();
        return response()->json(null, 204);
    }

    public function myProducts()
    {
        $user = Auth::user();
        $products = $user->products()->with('user')->get();
        
        return response()->json($products);
    }
}
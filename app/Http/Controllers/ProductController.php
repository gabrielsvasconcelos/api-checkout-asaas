<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index()
    {
        $products = $this->productService->getAllProducts();
        return ProductResource::collection($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());
        return response()->json(new ProductResource($product), 201);
    }

    public function show(int $id)
    {
        $product = $this->productService->getProductById($id);
        return new ProductResource($product);
    }

    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        $updatedProduct = $this->productService->updateProduct($product, $request->validated());
        return response()->json(new ProductResource($updatedProduct));
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        $this->productService->deleteProduct($product);
        return response()->json(null, 204);
    }

    public function myProducts()
    {
        $products = $this->productService->getUserProducts(auth()->id());
        return ProductResource::collection($products);
    }
}
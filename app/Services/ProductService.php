<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function getAllProducts()
    {
        return $this->productRepository->all();
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function createProduct(array $data): Product
    {
        $data['user_id'] = Auth::id();
        return $this->productRepository->create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $this->checkOwnership($product);
        return $this->productRepository->update($product, $data);
    }

    public function deleteProduct(Product $product): void
    {
        $this->checkOwnership($product);
        $this->productRepository->delete($product);
    }

    public function getUserProducts(int $userId)
    {
        return $this->productRepository->getByUser($userId);
    }

    private function checkOwnership(Product $product): void
    {
        if (Auth::id() !== $product->user_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
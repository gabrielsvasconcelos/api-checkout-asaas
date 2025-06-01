<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function all()
    {
        return Product::all();
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function getByUser(int $userId)
    {
        return Product::where('user_id', $userId)->with('user')->get();
    }

    public function getProductsByIds(array $productIds): Collection
    {
        return Product::whereIn('id', $productIds)->get();
    }

    public function getUserProducts(int $userId)
    {
        return Product::where('user_id', $userId)->get();
    }
}
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string, price: string|float}  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param  array{name?: string, price?: string|float}  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    /**
     * Soft delete so past orders keep pointing at the product.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }
}

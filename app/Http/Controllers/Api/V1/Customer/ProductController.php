<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->productService->paginate());
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}

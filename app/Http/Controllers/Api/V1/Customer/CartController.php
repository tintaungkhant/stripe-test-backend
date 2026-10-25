<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreCartItemRequest;
use App\Http\Requests\Api\V1\Customer\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->cartService->items($request->user());

        return CartItemResource::collection($items)->additional([
            'total_price' => $this->cartService->total($items),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $item = $this->cartService->add(
            $request->user(),
            Product::findOrFail($request->validated('product_id')),
            $request->validated('quantity', 1),
        );

        return (new CartItemResource($item))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateCartItemRequest $request, Product $product): CartItemResource
    {
        return new CartItemResource(
            $this->cartService->updateQuantity($request->user(), $product, $request->validated('quantity')),
        );
    }

    public function destroy(Request $request, Product $product): Response
    {
        $this->cartService->remove($request->user(), $product);

        return response()->noContent();
    }
}

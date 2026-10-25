<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->paginateForUser($request->user()));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $result = $this->orderService->checkout(
            $request->user(),
            Product::findOrFail($request->validated('product_id')),
        );

        return response()->json([
            'data' => new OrderResource($result['order']),
            'checkout_url' => $result['checkout_url'],
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_if($order->user_id !== $request->user()->id, Response::HTTP_NOT_FOUND);

        return new OrderResource($order->load('product'));
    }
}

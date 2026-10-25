<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([Order::STATUS_PENDING, Order::STATUS_PAID])],
        ]);

        return OrderResource::collection($this->orderService->paginateAll($validated['status'] ?? null));
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['product', 'user']));
    }
}

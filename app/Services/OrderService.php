<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->orders()->with('product')->latest()->paginate($perPage);
    }

    /**
     * Create a pending order and a Stripe Checkout session the customer pays through.
     *
     * The price is copied from the product so later price changes don't affect this order.
     *
     * @return array{order: Order, checkout_url: string}
     */
    public function checkout(User $user, Product $product): array
    {
        return DB::transaction(function () use ($user, $product) {
            $order = $user->orders()->create([
                'product_id' => $product->id,
                'total_price' => $product->price,
            ]);

            $checkout = $this->paymentService->createCheckoutSession($order);

            $order->update(['stripe_checkout_session_id' => $checkout->id]);

            return [
                'order' => $order->load('product'),
                'checkout_url' => $checkout->url,
            ];
        });
    }

    /**
     * Mark the order behind a Stripe Checkout session as paid.
     *
     * Stripe may deliver the same webhook more than once, so this is safe to call repeatedly.
     */
    public function markAsPaid(string $checkoutSessionId): ?Order
    {
        $order = Order::where('stripe_checkout_session_id', $checkoutSessionId)->first();

        if (! $order || $order->isPaid()) {
            return $order;
        }

        $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $order;
    }
}

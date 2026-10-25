<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private PaymentService $paymentService,
        private CartService $cartService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->orders()->with('items.product')->latest()->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateAll(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['items.product', 'user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Turn the customer's cart into a pending order and a Stripe Checkout session they pay through.
     *
     * Prices are copied from the products so later price changes don't affect this order.
     * The cart is left alone until Stripe confirms payment, so a cancelled checkout keeps it.
     *
     * @return array{order: Order, checkout_url: string}
     */
    public function checkout(User $user): array
    {
        $cartItems = $this->cartService->items($user);

        return DB::transaction(function () use ($user, $cartItems) {
            $order = $user->orders()->create([
                'total_price' => $this->cartService->total($cartItems),
            ]);

            $order->items()->createMany($cartItems->map(fn (CartItem $item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]));

            $checkout = $this->paymentService->createCheckoutSession($order->load('items.product'));

            $order->update(['stripe_checkout_session_id' => $checkout->id]);

            return [
                'order' => $order,
                'checkout_url' => $checkout->url,
            ];
        });
    }

    /**
     * Mark the order behind a Stripe Checkout session as paid and take its products out of the cart.
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

        $this->cartService->removePurchased($order);

        return $order;
    }
}

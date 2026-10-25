<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Laravel\Cashier\Checkout;

class PaymentService
{
    /**
     * Create a one-off Stripe Checkout session with a line item per order item.
     *
     * The order id travels in the session metadata so the webhook can find it later.
     */
    public function createCheckoutSession(Order $order): Checkout
    {
        return $order->user->checkout(
            items: $order->items->map(fn (OrderItem $item) => [
                'price_data' => [
                    'currency' => $order->user->preferredCurrency(),
                    'product_data' => ['name' => $item->product->name],
                    'unit_amount' => $this->toCents($item->price),
                ],
                'quantity' => $item->quantity,
            ])->all(),
            sessionOptions: [
                'success_url' => $this->frontendUrl("/checkout/success?order={$order->id}&session_id={CHECKOUT_SESSION_ID}"),
                'cancel_url' => $this->frontendUrl("/checkout/cancel?order={$order->id}"),
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
        );
    }

    /**
     * Build a URL on the storefront, where Stripe sends the customer back.
     */
    private function frontendUrl(string $path): string
    {
        return rtrim(config('app.frontend_url'), '/').$path;
    }

    /**
     * Stripe expects amounts in the smallest currency unit.
     */
    private function toCents(string|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}

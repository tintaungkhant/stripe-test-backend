<?php

namespace App\Services;

use App\Models\Order;
use Laravel\Cashier\Checkout;

class PaymentService
{
    /**
     * Create a one-off Stripe Checkout session for the order.
     *
     * The order id travels in the session metadata so the webhook can find it later.
     */
    public function createCheckoutSession(Order $order): Checkout
    {
        return $order->user->checkoutCharge(
            amount: $this->toCents($order->total_price),
            name: $order->product->name,
            sessionOptions: [
                'success_url' => url('/checkout/success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => url('/checkout/cancel'),
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
        );
    }

    /**
     * Stripe expects amounts in the smallest currency unit.
     */
    private function toCents(string|float $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}

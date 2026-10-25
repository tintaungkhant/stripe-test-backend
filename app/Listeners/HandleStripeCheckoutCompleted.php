<?php

namespace App\Listeners;

use App\Services\OrderService;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeCheckoutCompleted
{
    /**
     * Stripe events that mean a checkout session has been paid.
     *
     * @var list<string>
     */
    private const PAID_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
    ];

    public function __construct(private OrderService $orderService) {}

    /**
     * Mark the matching order as paid once Stripe confirms the payment.
     */
    public function handle(WebhookReceived $event): void
    {
        if (! in_array($event->payload['type'] ?? null, self::PAID_EVENTS, true)) {
            return;
        }

        $session = $event->payload['data']['object'];

        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $this->orderService->markAsPaid($session['id']);
    }
}

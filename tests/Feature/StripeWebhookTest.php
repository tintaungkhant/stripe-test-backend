<?php

use App\Models\Order;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config(['cashier.webhook.secret' => null]);
});

/**
 * @return array<string, mixed>
 */
function checkoutSessionPayload(string $sessionId, string $type = 'checkout.session.completed', string $paymentStatus = 'paid'): array
{
    return [
        'id' => 'evt_test_123',
        'type' => $type,
        'data' => [
            'object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'payment_status' => $paymentStatus,
            ],
        ],
    ];
}

test('completed checkout marks the order as paid', function () {
    $order = Order::factory()->create();

    $this->postJson(route('cashier.webhook'), checkoutSessionPayload($order->stripe_checkout_session_id))
        ->assertOk();

    expect($order->fresh())
        ->status->toBe(Order::STATUS_PAID)
        ->paid_at->not->toBeNull();
});

test('unpaid checkout session leaves the order pending', function () {
    $order = Order::factory()->create();

    $this->postJson(route('cashier.webhook'), checkoutSessionPayload($order->stripe_checkout_session_id, paymentStatus: 'unpaid'))
        ->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

test('async payment success marks the order as paid', function () {
    $order = Order::factory()->create();

    $this->postJson(route('cashier.webhook'), checkoutSessionPayload($order->stripe_checkout_session_id, 'checkout.session.async_payment_succeeded'))
        ->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID);
});

test('duplicate webhooks do not change paid_at', function () {
    $paidAt = Carbon::parse('2026-01-01 10:00:00');
    $order = Order::factory()->paid()->create(['paid_at' => $paidAt]);

    $this->postJson(route('cashier.webhook'), checkoutSessionPayload($order->stripe_checkout_session_id))
        ->assertOk();

    expect($order->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

test('unknown session id is ignored', function () {
    $this->postJson(route('cashier.webhook'), checkoutSessionPayload('cs_test_unknown'))
        ->assertOk();
});

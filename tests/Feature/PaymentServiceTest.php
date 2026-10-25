<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Laravel\Cashier\Checkout;
use Stripe\Checkout\Session;

test('checkout sends the customer back to the frontend', function () {
    config(['app.frontend_url' => 'https://shop.test/']);

    $order = Order::factory()
        ->for(Product::factory()->create(['name' => 'Laravel Masterclass', 'price' => 19.99]))
        ->create();

    $customer = Mockery::mock(User::class)->makePartial();
    $customer->shouldReceive('checkoutCharge')
        ->once()
        ->withArgs(function (int $amount, string $name, int $quantity, array $sessionOptions) use ($order) {
            return $amount === 1999
                && $name === 'Laravel Masterclass'
                && $sessionOptions['success_url'] === "https://shop.test/checkout/success?order={$order->id}&session_id={CHECKOUT_SESSION_ID}"
                && $sessionOptions['cancel_url'] === "https://shop.test/checkout/cancel?order={$order->id}"
                && $sessionOptions['metadata']['order_id'] === $order->id;
        })
        ->andReturn(new Checkout(null, Session::constructFrom(['id' => 'cs_test_123'])));

    $order->setRelation('user', $customer);

    expect(app(PaymentService::class)->createCheckoutSession($order)->id)->toBe('cs_test_123');
});

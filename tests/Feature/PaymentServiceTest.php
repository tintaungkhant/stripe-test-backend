<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentService;
use Laravel\Cashier\Checkout;
use Stripe\Checkout\Session;

test('checkout sends the customer back to the frontend', function () {
    config(['app.frontend_url' => 'https://shop.test/']);

    $order = Order::factory()
        ->has(OrderItem::factory()->for(Product::factory()->create(['name' => 'Laravel Masterclass', 'price' => 19.99]))->state(['quantity' => 2]), 'items')
        ->has(OrderItem::factory()->for(Product::factory()->create(['name' => 'Docker E-book', 'price' => 5.00]))->state(['quantity' => 1]), 'items')
        ->create();

    $customer = Mockery::mock(User::class)->makePartial();
    $customer->shouldReceive('checkout')
        ->once()
        ->withArgs(function (array $items, array $sessionOptions) use ($order) {
            $lineItems = collect($items)->map(fn (array $item) => [
                $item['price_data']['product_data']['name'],
                $item['price_data']['unit_amount'],
                $item['quantity'],
            ])->all();

            return $lineItems === [['Laravel Masterclass', 1999, 2], ['Docker E-book', 500, 1]]
                && $sessionOptions['success_url'] === "https://shop.test/checkout/success?order={$order->id}&session_id={CHECKOUT_SESSION_ID}"
                && $sessionOptions['cancel_url'] === "https://shop.test/checkout/cancel?order={$order->id}"
                && $sessionOptions['metadata']['order_id'] === $order->id;
        })
        ->andReturn(new Checkout(null, Session::constructFrom(['id' => 'cs_test_123'])));

    $order->setRelation('user', $customer);

    expect(app(PaymentService::class)->createCheckoutSession($order)->id)->toBe('cs_test_123');
});

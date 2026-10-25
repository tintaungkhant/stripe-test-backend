<?php

use App\Models\Order;
use App\Models\Product;
use App\Services\PaymentService;
use Laravel\Cashier\Checkout;
use Stripe\Checkout\Session;

function fakeCheckout(string $sessionId = 'cs_test_123'): Checkout
{
    return new Checkout(null, Session::constructFrom([
        'id' => $sessionId,
        'url' => 'https://checkout.stripe.com/c/pay/'.$sessionId,
    ]));
}

test('customer can start checkout for a product', function () {
    $customer = actingAsCustomer();
    $product = Product::factory()->create(['price' => 25.50]);

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn(fakeCheckout());

    $this->postJson(route('api.v1.customer.orders.store'), ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.status', Order::STATUS_PENDING)
        ->assertJsonPath('data.total_price', '25.50')
        ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_123');

    $this->assertDatabaseHas('orders', [
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'status' => Order::STATUS_PENDING,
        'stripe_checkout_session_id' => 'cs_test_123',
    ]);
});

test('checkout rejects deleted products', function () {
    actingAsCustomer();
    $product = Product::factory()->create();
    $product->delete();

    $this->postJson(route('api.v1.customer.orders.store'), ['product_id' => $product->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});

test('no order is saved when stripe fails', function () {
    actingAsCustomer();
    $product = Product::factory()->create();

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->andThrow(new RuntimeException('Stripe is down'));

    $this->postJson(route('api.v1.customer.orders.store'), ['product_id' => $product->id])
        ->assertServerError();

    expect(Order::count())->toBe(0);
});

test('customer only sees their own orders', function () {
    $customer = actingAsCustomer();
    Order::factory()->count(2)->for($customer)->create();
    Order::factory()->create();

    $this->getJson(route('api.v1.customer.orders.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('customer cannot view another customers order', function () {
    actingAsCustomer();
    $otherOrder = Order::factory()->create();

    $this->getJson(route('api.v1.customer.orders.show', $otherOrder))
        ->assertNotFound();
});

test('customer can view their own order', function () {
    $customer = actingAsCustomer();
    $order = Order::factory()->for($customer)->create();

    $this->getJson(route('api.v1.customer.orders.show', $order))
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

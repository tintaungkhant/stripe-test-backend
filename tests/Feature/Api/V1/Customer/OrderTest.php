<?php

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
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

test('customer can check out their cart', function () {
    $customer = actingAsCustomer();
    $ebook = Product::factory()->create(['price' => 25.50]);
    $course = Product::factory()->create(['price' => 10.00]);
    CartItem::factory()->for($customer)->for($ebook)->create(['quantity' => 2]);
    CartItem::factory()->for($customer)->for($course)->create(['quantity' => 1]);

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn(fakeCheckout());

    $this->postJson(route('api.v1.customer.orders.store'))
        ->assertCreated()
        ->assertJsonPath('data.status', Order::STATUS_PENDING)
        ->assertJsonPath('data.total_price', '61.00')
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/pay/cs_test_123');

    $order = Order::sole();

    expect($order)
        ->user_id->toBe($customer->id)
        ->stripe_checkout_session_id->toBe('cs_test_123');

    $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $ebook->id, 'quantity' => 2, 'price' => '25.50']);
    $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $course->id, 'quantity' => 1, 'price' => '10.00']);
});

test('checkout keeps the cart until payment is confirmed', function () {
    $customer = actingAsCustomer();
    CartItem::factory()->for($customer)->create();

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->andReturn(fakeCheckout());

    $this->postJson(route('api.v1.customer.orders.store'))->assertCreated();

    expect($customer->cartItems()->count())->toBe(1);
});

test('checkout rejects an empty cart', function () {
    actingAsCustomer();

    $this->postJson(route('api.v1.customer.orders.store'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');
});

test('checkout leaves out deleted products', function () {
    $customer = actingAsCustomer();
    $deleted = CartItem::factory()->for($customer)->create();
    $deleted->product->delete();
    $kept = CartItem::factory()->for($customer)->create();

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->andReturn(fakeCheckout());

    $this->postJson(route('api.v1.customer.orders.store'))
        ->assertCreated()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.product.id', $kept->product_id);
});

test('checkout rejects a cart with only deleted products', function () {
    $customer = actingAsCustomer();
    CartItem::factory()->for($customer)->create()->product->delete();

    $this->postJson(route('api.v1.customer.orders.store'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');
});

test('no order is saved when stripe fails', function () {
    $customer = actingAsCustomer();
    CartItem::factory()->for($customer)->create();

    $this->mock(PaymentService::class)
        ->shouldReceive('createCheckoutSession')
        ->andThrow(new RuntimeException('Stripe is down'));

    $this->postJson(route('api.v1.customer.orders.store'))
        ->assertServerError();

    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0);
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
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonStructure(['data' => ['items' => [['id', 'quantity', 'price', 'product']]]]);
});

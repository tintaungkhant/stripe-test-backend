<?php

use App\Models\CartItem;
use App\Models\Product;

test('customer sees their cart with a total', function () {
    $customer = actingAsCustomer();
    CartItem::factory()->for($customer)->for(Product::factory()->create(['price' => 19.99]))->create(['quantity' => 2]);
    CartItem::factory()->for($customer)->for(Product::factory()->create(['price' => 5.00]))->create(['quantity' => 1]);
    CartItem::factory()->create();

    $this->getJson(route('api.v1.customer.cart.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'quantity', 'product' => ['id', 'name', 'price']]]])
        ->assertJsonPath('total_price', '44.98');
});

test('cart leaves out deleted products', function () {
    $customer = actingAsCustomer();
    CartItem::factory()->for($customer)->create()->product->delete();

    $this->getJson(route('api.v1.customer.cart.index'))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('total_price', '0.00');
});

test('customer can add a product to their cart', function () {
    $customer = actingAsCustomer();
    $product = Product::factory()->create();

    $this->postJson(route('api.v1.customer.cart.items.store'), ['product_id' => $product->id])
        ->assertCreated()
        ->assertJsonPath('data.quantity', 1)
        ->assertJsonPath('data.product.id', $product->id);

    $this->assertDatabaseHas('cart_items', ['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1]);
});

test('adding a product already in the cart raises its quantity', function () {
    $customer = actingAsCustomer();
    $item = CartItem::factory()->for($customer)->create(['quantity' => 2]);

    $this->postJson(route('api.v1.customer.cart.items.store'), ['product_id' => $item->product_id, 'quantity' => 3])
        ->assertCreated()
        ->assertJsonPath('data.quantity', 5);

    expect($customer->cartItems()->count())->toBe(1);
});

test('adding never goes past the maximum quantity', function () {
    $customer = actingAsCustomer();
    $item = CartItem::factory()->for($customer)->create(['quantity' => 98]);

    $this->postJson(route('api.v1.customer.cart.items.store'), ['product_id' => $item->product_id, 'quantity' => 5])
        ->assertCreated()
        ->assertJsonPath('data.quantity', 99);
});

test('customer cannot add deleted products', function () {
    actingAsCustomer();
    $product = Product::factory()->create();
    $product->delete();

    $this->postJson(route('api.v1.customer.cart.items.store'), ['product_id' => $product->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');
});

test('customer can change a quantity', function () {
    $customer = actingAsCustomer();
    $item = CartItem::factory()->for($customer)->create(['quantity' => 1]);

    $this->putJson(route('api.v1.customer.cart.items.update', $item->product_id), ['quantity' => 4])
        ->assertOk()
        ->assertJsonPath('data.quantity', 4);

    expect($item->fresh()->quantity)->toBe(4);
});

test('quantity must be between 1 and 99', function (int $quantity) {
    $customer = actingAsCustomer();
    $item = CartItem::factory()->for($customer)->create();

    $this->putJson(route('api.v1.customer.cart.items.update', $item->product_id), ['quantity' => $quantity])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
})->with([0, 100]);

test('changing a product that is not in the cart is not found', function () {
    actingAsCustomer();
    $otherCustomersItem = CartItem::factory()->create();

    $this->putJson(route('api.v1.customer.cart.items.update', $otherCustomersItem->product_id), ['quantity' => 2])
        ->assertNotFound();

    expect($otherCustomersItem->fresh()->quantity)->toBe($otherCustomersItem->quantity);
});

test('customer can remove a product from their cart', function () {
    $customer = actingAsCustomer();
    $item = CartItem::factory()->for($customer)->create();
    $otherCustomersItem = CartItem::factory()->for($item->product)->create();

    $this->deleteJson(route('api.v1.customer.cart.items.destroy', $item->product_id))
        ->assertNoContent();

    $this->assertModelMissing($item);
    $this->assertModelExists($otherCustomersItem);
});

test('guests cannot use the cart', function () {
    $this->getJson(route('api.v1.customer.cart.index'))->assertUnauthorized();
});

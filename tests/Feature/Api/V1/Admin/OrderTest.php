<?php

use App\Models\Order;

test('admin can list all orders', function () {
    actingAsAdmin();
    Order::factory()->count(3)->create();

    $this->getJson(route('api.v1.admin.orders.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'status', 'items', 'user']]]);
});

test('admin can filter orders by status', function () {
    actingAsAdmin();
    Order::factory()->count(2)->create();
    Order::factory()->paid()->create();

    $this->getJson(route('api.v1.admin.orders.index', ['status' => Order::STATUS_PAID]))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('admin order filter rejects unknown status', function () {
    actingAsAdmin();

    $this->getJson(route('api.v1.admin.orders.index', ['status' => 'refunded']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('admin can view any order', function () {
    actingAsAdmin();
    $order = Order::factory()->create();

    $this->getJson(route('api.v1.admin.orders.show', $order))
        ->assertOk()
        ->assertJsonPath('data.user.id', $order->user_id);
});

test('customer cannot list all orders', function () {
    actingAsCustomer();

    $this->getJson(route('api.v1.admin.orders.index'))->assertForbidden();
});

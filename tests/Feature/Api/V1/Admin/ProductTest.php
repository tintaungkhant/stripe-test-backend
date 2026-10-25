<?php

use App\Models\Product;

test('admin can list products', function () {
    actingAsAdmin();
    Product::factory()->count(3)->create();

    $this->getJson(route('api.v1.admin.products.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin can create a product', function () {
    actingAsAdmin();
    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Laravel Course',
        'price' => 49.99,
    ])->assertCreated()->assertJsonPath('data.name', 'Laravel Course');

    $this->assertDatabaseHas('products', ['name' => 'Laravel Course', 'price' => 49.99]);
});

test('product creation validates input', function () {
    actingAsAdmin();
    $this->postJson(route('api.v1.admin.products.store'), [
        'price' => -5,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'price']);
});

test('admin can update a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->patchJson(route('api.v1.admin.products.update', $product), [
        'price' => 10,
    ])->assertOk()->assertJsonPath('data.price', '10.00');
});

test('admin can soft delete a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->deleteJson(route('api.v1.admin.products.destroy', $product))
        ->assertNoContent();

    $this->assertSoftDeleted($product);
});

test('customer cannot access admin product endpoints', function () {
    actingAsCustomer();

    $this->getJson(route('api.v1.admin.products.index'))->assertForbidden();
    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Hack',
        'price' => 1,
    ])->assertForbidden();
});

test('guest cannot access admin product endpoints', function () {
    $this->getJson(route('api.v1.admin.products.index'))->assertUnauthorized();
});

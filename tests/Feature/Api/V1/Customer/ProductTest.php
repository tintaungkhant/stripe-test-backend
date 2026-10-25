<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

test('customer can browse products', function () {
    actingAsCustomer();
    Product::factory()->count(2)->create();

    $this->getJson(route('api.v1.customer.products.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('deleted products are hidden from customers', function () {
    actingAsCustomer();
    Product::factory()->create();
    $deletedProduct = Product::factory()->create();
    $deletedProduct->delete();

    $this->getJson(route('api.v1.customer.products.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson(route('api.v1.customer.products.show', $deletedProduct))
        ->assertNotFound();
});

test('customer can view a single product', function () {
    actingAsCustomer();
    $product = Product::factory()->create();

    $this->getJson(route('api.v1.customer.products.show', $product))
        ->assertOk()
        ->assertJsonPath('data.id', $product->id);
});

test('customer sees a temporary image url for products with an image', function () {
    Storage::fake(Product::IMAGE_DISK);
    actingAsCustomer();
    $product = Product::factory()->create(['file_path' => 'products/'.fake()->uuid().'.png']);
    Product::factory()->create();

    $this->getJson(route('api.v1.customer.products.show', $product))
        ->assertOk()
        ->assertJsonPath('data.image_url', fn (string $url) => str_contains($url, $product->file_path) && str_contains($url, 'expiration='));
});

test('customer cannot write products', function () {
    actingAsCustomer();

    $this->postJson('/api/v1/customer/products', ['name' => 'x', 'price' => 1])
        ->assertMethodNotAllowed();
});

test('guest cannot browse customer products', function () {
    $this->getJson(route('api.v1.customer.products.index'))->assertUnauthorized();
});

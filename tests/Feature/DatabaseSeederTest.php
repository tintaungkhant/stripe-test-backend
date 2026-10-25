<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeder creates users, products and orders', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@example.com')->first()->is_admin)->toBeTrue()
        ->and(User::where('email', 'test@example.com')->first()->is_admin)->toBeFalse()
        ->and(User::count())->toBe(12)
        ->and(Product::count())->toBe(12)
        ->and(Product::onlyTrashed()->count())->toBe(2)
        ->and(Order::whereRelation('user', 'is_admin', true)->exists())->toBeFalse();
});

test('order factory copies the product price', function () {
    $product = Product::factory()->create(['price' => 19.99]);

    expect(Order::factory()->for($product)->create()->total_price)->toBe('19.99');
});

test('paid orders are paid after they were created', function () {
    $order = Order::factory()->paid()->create();

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->paid_at->greaterThan($order->created_at))->toBeTrue();
});

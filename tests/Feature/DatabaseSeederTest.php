<?php

use App\Models\Order;
use App\Models\OrderItem;
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
        ->and(Order::whereRelation('user', 'is_admin', true)->exists())->toBeFalse()
        ->and(Order::doesntHave('items')->exists())->toBeFalse();
});

test('order item factory copies the product price', function () {
    $product = Product::factory()->create(['price' => 19.99]);

    expect(OrderItem::factory()->for($product)->create()->price)->toBe('19.99');
});

test('order factory totals up the items', function () {
    $order = Order::factory()
        ->has(OrderItem::factory()->for(Product::factory()->create(['price' => 19.99]))->state(['quantity' => 2]), 'items')
        ->has(OrderItem::factory()->for(Product::factory()->create(['price' => 5.00]))->state(['quantity' => 1]), 'items')
        ->create();

    expect($order->fresh()->total_price)->toBe('44.98');
});

test('order factory gives an order one item by default', function () {
    expect(Order::factory()->create()->items()->count())->toBe(1);
});

test('paid orders are paid after they were created', function () {
    $order = Order::factory()->paid()->create();

    expect($order->status)->toBe(Order::STATUS_PAID)
        ->and($order->paid_at->greaterThan($order->created_at))->toBeTrue();
});

<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    /**
     * Give each customer a few orders of one to three products, mostly paid, some still pending.
     *
     * Removed products are included so order history shows them too.
     */
    public function run(): void
    {
        $products = Product::withTrashed()->get();

        User::where('is_admin', false)->each(function (User $customer) use ($products) {
            Collection::times(fake()->numberBetween(0, 4), function () use ($customer, $products) {
                $items = $products->random(fake()->numberBetween(1, 3))
                    ->map(fn (Product $product) => ['product_id' => $product->id])
                    ->all();

                $factory = Order::factory()->for($customer)->has(OrderItem::factory()->forEachSequence(...$items), 'items');

                fake()->boolean(75) ? $factory->paid()->create() : $factory->create();
            });
        });
    }
}

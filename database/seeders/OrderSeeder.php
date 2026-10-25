<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Give each customer a few orders, mostly paid, some still pending.
     *
     * Removed products are included so order history shows them too.
     */
    public function run(): void
    {
        $products = Product::withTrashed()->get();

        User::where('is_admin', false)->each(function (User $customer) use ($products) {
            $products->random(fake()->numberBetween(0, 4))->each(function (Product $product) use ($customer) {
                $factory = Order::factory()->for($customer)->for($product);

                fake()->boolean(75) ? $factory->paid()->create() : $factory->create();
            });
        });
    }
}

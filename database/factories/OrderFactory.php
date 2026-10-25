<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-60 days');

        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'total_price' => fn (array $attributes) => Product::withTrashed()->find($attributes['product_id'])->price,
            'status' => Order::STATUS_PENDING,
            'stripe_checkout_session_id' => 'cs_test_'.Str::random(24),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Indicate that the order has been paid a few minutes after checkout started.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAt = Carbon::parse($attributes['created_at'])->addMinutes(fake()->numberBetween(1, 15));

            return [
                'status' => Order::STATUS_PAID,
                'paid_at' => $paidAt,
                'updated_at' => $paidAt,
            ];
        });
    }
}

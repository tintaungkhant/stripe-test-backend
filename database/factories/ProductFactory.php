<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topic = fake()->randomElement([
            'Laravel', 'Vue.js', 'React', 'Tailwind CSS', 'PHP', 'Stripe Payments',
            'Docker', 'UI Design', 'Photography', 'Productivity',
        ]);

        $format = fake()->randomElement([
            'Masterclass', 'E-book', 'Starter Kit', 'Video Course',
            'Template Pack', 'Cheat Sheet', 'Icon Set', 'Workshop Recording',
        ]);

        return [
            'name' => "{$topic} {$format}",
            'price' => fake()->randomElement([4.99, 9.99, 19.99, 29.99, 49.99, 79.99, 129.00]),
        ];
    }
}

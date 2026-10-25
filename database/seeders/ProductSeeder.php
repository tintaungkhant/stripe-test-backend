<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed products customers can buy, plus a couple an admin has removed.
     */
    public function run(): void
    {
        Product::factory()->count(12)->create();

        Product::factory()->count(2)->trashed()->create();
    }
}

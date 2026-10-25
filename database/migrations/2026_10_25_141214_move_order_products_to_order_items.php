<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orders can hold several products now, so each existing order's product
     * becomes its single order item before the column is dropped.
     */
    public function up(): void
    {
        DB::table('order_items')->insertUsing(
            ['order_id', 'product_id', 'quantity', 'price', 'created_at', 'updated_at'],
            DB::table('orders')->select('id', 'product_id', DB::raw('1'), 'total_price', 'created_at', 'updated_at'),
        );

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }

    /**
     * Each order keeps only its first product, so multi-product orders lose the rest.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        DB::table('orders')->update([
            'product_id' => DB::raw('(select product_id from order_items where order_items.order_id = orders.id order by id limit 1)'),
        ]);

        DB::table('order_items')->delete();
    }
};

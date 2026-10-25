<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->index()->after('total_price');
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('status');
            $table->timestamp('paid_at')->nullable()->after('stripe_checkout_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn(['status', 'stripe_checkout_session_id', 'paid_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('phone')->after('user_id');
            $table->enum('delivery_type', ['pickup', 'delivery'])->default('delivery')->after('phone');
            $table->string('pickup_point')->nullable()->after('delivery_type');
            $table->text('delivery_address')->nullable()->after('pickup_point');
            $table->string('payment_method')->default('cash')->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['phone', 'delivery_type', 'pickup_point', 'delivery_address', 'payment_method']);
        });
    }
};
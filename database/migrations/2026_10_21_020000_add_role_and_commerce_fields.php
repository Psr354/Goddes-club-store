<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->after('password');
            $table->string('phone')->nullable()->unique()->after('email');
        });

        Schema::table('products', function (Blueprint $table) {
            // The Figma design differentiates product cards by a "kind"
            // (tee, vinyl, tote, cap) that drives the artwork overlay.
            $table->string('kind')->nullable()->after('tag');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('address_id')->constrained('coupons')->nullOnDelete();
            $table->string('courier_code')->nullable()->after('status');
            $table->string('tracking_number')->nullable()->after('courier_code');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['courier_code', 'tracking_number']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone']);
        });
    }
};

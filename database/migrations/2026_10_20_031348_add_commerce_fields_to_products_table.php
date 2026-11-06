<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('status');
            $table->unsignedInteger('stock')->default(0)->after('price');
            $table->string('image')->nullable()->after('stock');
            $table->string('tag')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price', 'stock', 'image', 'tag']);
        });
    }
};
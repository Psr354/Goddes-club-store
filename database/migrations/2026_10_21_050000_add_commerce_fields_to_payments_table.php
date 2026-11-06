<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('provider_reference');
            }
            if (! Schema::hasColumn('payments', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('payment_method');
            }
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('payments', 'raw_response')) {
                $table->json('raw_response')->nullable()->after('payload');
            }
            if (! Schema::hasColumn('payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('provider_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'amount', 'paid_at', 'raw_response', 'transaction_id']);
        });
    }
};

<?php

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire', function () {
    $count = 0;

    Order::where('status', 'pending_payment')
        ->where('created_at', '<', now()->subHours(1))
        ->with(['items.variant', 'coupon', 'payment'])
        ->chunkById(100, function ($expiredOrders) use (&$count) {
            foreach ($expiredOrders as $order) {
                DB::transaction(function () use ($order) {
                    $order->update(['status' => 'cancelled']);

                    if ($order->payment) {
                        $order->payment->update(['status' => 'failed']);
                    }

                    if ($order->coupon_id) {
                        $coupon = $order->coupon()->lockForUpdate()->first();
                        if ($coupon && $coupon->used_count > 0) {
                            $coupon->decrement('used_count');
                        }
                    }

                    foreach ($order->items as $item) {
                        $variant = ProductVariant::where('id', $item->product_variant_id)
                            ->lockForUpdate()
                            ->first();
                        if ($variant) {
                            $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                        }
                    }

                    $order->trackings()->create([
                        'status' => 'cancelled',
                        'description' => 'Pesanan dibatalkan otomatis karena melewati batas waktu pembayaran.',
                        'occurred_at' => now(),
                    ]);
                });
                $count++;
            }
        });

    $this->info("{$count} expired pending orders cancelled and stock released.");
})->purpose('Cancel expired pending payment orders and release reserved stock');

Schedule::command('orders:expire')->everyFifteenMinutes();

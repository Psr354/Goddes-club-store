<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Notifications\OrderConfirmed;
use App\Services\MidtransService;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * The flat shipping fee fallback.
     */
    public const DEFAULT_SHIPPING_FEE = 5.00;

    /**
     * Turn the current cart into a pending order with atomic stock reservation.
     */
    public function process(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masuk untuk melanjutkan checkout.',
                'redirect' => route('login'),
            ], 401);
        }

        $data = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'full_address' => 'required|string|max:500',
            'postal_code' => 'required|string|max:10',
            'city_id' => 'nullable|integer',
            'courier_code' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $cart = $user->carts()->with('items.variant.product')->latest('id')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tas kamu masih kosong.',
            ], 422);
        }

        $courier = $data['courier_code'] ?? 'JNE';
        $couponCode = ! empty($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null;

        try {
            $order = DB::transaction(function () use ($user, $data, $cart, $courier, $couponCode) {
                // 1. Check and lock stock for each variant deterministically sorted by ID to prevent deadlocks
                $totalWeight = 0;
                $subtotal = 0.0;

                $sortedItems = $cart->items->sortBy('product_variant_id');

                foreach ($sortedItems as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)
                        ->with('product')
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        throw new \RuntimeException('Varian produk tidak ditemukan.');
                    }

                    if ($variant->product?->status !== 'active') {
                        throw new \RuntimeException("Produk '{$variant->product?->name}' sudah tidak aktif atau tidak tersedia.");
                    }

                    if ($variant->availableStock() < $item->quantity) {
                        throw new \RuntimeException("Stok untuk '{$variant->product->name} ({$variant->size()})' tidak mencukupi. Tersedia: {$variant->availableStock()} pcs.");
                    }

                    // Increment reserved stock
                    $variant->increment('reserved_stock', $item->quantity);

                    $weight = (int) ($variant->weight_gram ?: 300);
                    $totalWeight += $weight * $item->quantity;
                    $subtotal += (float) $variant->price * $item->quantity;
                }

                // Dynamic shipping fee calculation
                $shippingCalculation = ShippingCalculator::calculate(
                    destinationPostal: $data['postal_code'],
                    weightGram: $totalWeight,
                    courier: $courier
                );
                $shippingFee = $shippingCalculation['fee'] ?? self::DEFAULT_SHIPPING_FEE;

                // Coupon locking and calculation
                $coupon = null;
                $discount = 0.0;
                if ($couponCode) {
                    $coupon = Coupon::where('code', $couponCode)
                        ->lockForUpdate()
                        ->first();

                    if (! $coupon || ! $coupon->isValid($subtotal)) {
                        throw new \RuntimeException('Kupon promo tidak valid atau kuota telah habis.');
                    }

                    $discount = (float) $coupon->calculateDiscount($subtotal);
                    $coupon->increment('used_count');
                }

                $grandTotal = max(0, $subtotal - $discount + $shippingFee);

                // 2. Save or find user address
                $address = UserAddress::firstOrCreate([
                    'user_id' => $user->id,
                    'recipient_name' => $data['recipient_name'],
                    'phone' => $data['phone'],
                    'full_address' => $data['full_address'],
                    'postal_code' => $data['postal_code'],
                ], [
                    'city_id' => $data['city_id'] ?? 0,
                    'district_id' => 0,
                    'is_primary' => ! $user->addresses()->exists(),
                ]);

                // 3. Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'invoice_number' => 'GCS-'.strtoupper(Str::random(8)),
                    'address_id' => $address->id,
                    'coupon_id' => $coupon?->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'shipping_fee' => $shippingFee,
                    'grand_total' => $grandTotal,
                    'status' => 'pending_payment',
                    'courier_code' => strtoupper($courier),
                    'tracking_number' => null,
                ]);

                // 4. Create Order Items
                foreach ($cart->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->variant->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'sku' => $item->variant->sku,
                        'product_name' => $item->variant->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->variant->price,
                    ]);
                }

                // 5. Create Payment record with unique transaction ID
                $transactionId = 'PAY-'.strtoupper(Str::random(12));
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId,
                    'provider' => 'midtrans',
                    'payment_method' => 'snap',
                    'amount' => $grandTotal,
                    'status' => 'pending',
                ]);

                // 6. Initial tracking step
                $order->trackings()->create([
                    'status' => 'pending_payment',
                    'title' => 'Menunggu Pembayaran',
                    'description' => 'Pesanan berhasil dibuat. Menunggu penyelesaian pembayaran melalui Midtrans Snap.',
                    'occurred_at' => now(),
                ]);

                // 7. Clear cart items
                $cart->items()->delete();

                return $order;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $order->load(['items', 'address', 'payment', 'user', 'coupon']);

        // For free orders ($0 grand total, e.g. 100% discount promo), bypass payment gateway
        if ($order->grand_total <= 0) {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'paid']);
                $order->payment?->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'promo_free',
                ]);

                foreach ($order->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if ($variant) {
                        $variant->decrement('stock', $item->quantity);
                        $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                        $variant->product?->decrement('stock', $item->quantity);
                    }
                }

                $order->trackings()->create([
                    'status' => 'paid',
                    'title' => 'Pesanan Gratis Dikonfirmasi',
                    'description' => 'Pesanan berhasil dikonfirmasi tanpa biaya tambahan.',
                    'occurred_at' => now(),
                ]);
            });

            try {
                $user->notify(new OrderConfirmed($order));
            } catch (\Throwable $exception) {
                report($exception);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dikonfirmasi.',
                'invoice_number' => $order->invoice_number,
                'reference' => $order->reference(),
                'status' => 'paid',
                'discount' => round($order->discount_amount, 2),
                'shipping_fee' => round($order->shipping_fee, 2),
                'grand_total' => 0.0,
                'transaction_id' => $order->payment?->transaction_id,
                'snap_token' => null,
                'snap_url' => null,
                'payment_url' => route('track', ['order' => $order->invoice_number]),
                'track_url' => route('track', ['order' => $order->invoice_number]),
            ]);
        }

        // Generate Midtrans Snap transaction token
        try {
            $snap = MidtransService::createTransaction($order);
            if ($order->payment) {
                $order->payment->update([
                    'provider' => 'midtrans',
                    'payment_method' => 'snap',
                    'provider_reference' => $snap['token'] ?? null,
                    'payment_url' => $snap['redirect_url'] ?? null,
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);

            DB::transaction(function () use ($order) {
                $order->update(['status' => 'cancelled']);
                if ($order->coupon_id) {
                    $coupon = $order->coupon()->lockForUpdate()->first();
                    if ($coupon && $coupon->used_count > 0) {
                        $coupon->decrement('used_count');
                    }
                }
                foreach ($order->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if ($variant) {
                        $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                    }
                }
            });

            return response()->json([
                'success' => false,
                'message' => 'Gagal menginisialisasi gateway pembayaran: '.$exception->getMessage(),
            ], 502);
        }

        // Send order notification email via Mailpit queue
        try {
            $user->notify(new OrderConfirmed($order));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat. Tautan pembayaran Midtrans Snap siap.',
            'invoice_number' => $order->invoice_number,
            'reference' => $order->reference(),
            'status' => $order->status,
            'discount' => round($order->discount_amount, 2),
            'shipping_fee' => round($order->shipping_fee, 2),
            'grand_total' => round($order->grand_total, 2),
            'transaction_id' => $order->payment?->transaction_id,
            'snap_token' => $snap['token'],
            'snap_url' => $snap['redirect_url'],
            'payment_url' => $snap['redirect_url'] ?: route('track', ['order' => $order->invoice_number]),
            'track_url' => route('track', ['order' => $order->invoice_number]),
        ]);
    }
}

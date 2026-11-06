<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Notifications\OrderConfirmed;
use App\Services\MidtransService;
use App\Services\ShippingCalculator;

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

        // Calculate total weight and base subtotal
        $totalWeight = (int) $cart->items->sum(
            fn ($item) => (int) ($item->variant->weight_gram ?: 300) * $item->quantity
        );

        $subtotal = (float) $cart->items->sum(
            fn ($item) => (float) $item->variant->price * $item->quantity
        );

        // Calculate dynamic shipping fee using ShippingCalculator
        $courier = $data['courier_code'] ?? 'JNE';
        $shippingCalculation = ShippingCalculator::calculate(
            destinationPostal: $data['postal_code'],
            weightGram: $totalWeight,
            courier: $courier
        );
        $shippingFee = $shippingCalculation['fee'] ?? self::DEFAULT_SHIPPING_FEE;

        // Coupon calculation
        $coupon = null;
        $discount = 0.0;

        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->first();

            if ($coupon && $coupon->isValid($subtotal)) {
                $discount = (float) $coupon->calculateDiscount($subtotal);
            } else {
                $coupon = null;
            }
        }

        $grandTotal = max(0, $subtotal - $discount + $shippingFee);

        try {
            $order = DB::transaction(function () use ($user, $data, $cart, $subtotal, $discount, $coupon, $shippingFee, $grandTotal, $courier) {
                // 1. Check and lock stock for each variant using lockForUpdate()
                foreach ($cart->items as $item) {
                    $variant = ProductVariant::where('id', $item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        throw new \RuntimeException("Varian produk tidak ditemukan.");
                    }

                    if ($variant->availableStock() < $item->quantity) {
                        throw new \RuntimeException("Stok untuk '{$item->variant->product->name} ({$variant->size()})' tidak mencukupi. Tersedia: {$variant->availableStock()} pcs.");
                    }

                    // Increment reserved stock
                    $variant->increment('reserved_stock', $item->quantity);
                }

                // 2. Save or update user address
                $address = UserAddress::create([
                    'user_id' => $user->id,
                    'recipient_name' => $data['recipient_name'],
                    'phone' => $data['phone'],
                    'full_address' => $data['full_address'],
                    'city_id' => $data['city_id'] ?? 0,
                    'district_id' => 0,
                    'postal_code' => $data['postal_code'],
                    'is_primary' => ! $user->addresses()->exists(),
                ]);

                // 3. Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'invoice_number' => 'GCS-' . strtoupper(Str::random(8)),
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

                // If coupon applied, increment used_count
                if ($coupon) {
                    $coupon->increment('used_count');
                }

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
                $transactionId = 'PAY-' . strtoupper(Str::random(12));
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

        $order->load(['items', 'address', 'payment', 'user']);

        // Generate Midtrans Snap transaction token
        $snap = MidtransService::createTransaction($order);
        if ($order->payment) {
            $order->payment->update([
                'provider' => 'midtrans',
                'payment_method' => 'snap',
                'provider_reference' => $snap['token'] ?? null,
                'payment_url' => $snap['redirect_url'] ?? null,
            ]);
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
            'discount' => round($discount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'grand_total' => round($grandTotal, 2),
            'transaction_id' => $order->payment?->transaction_id,
            'snap_token' => $snap['token'],
            'snap_url' => $snap['redirect_url'],
            'payment_url' => $snap['redirect_url'] ?: route('track', ['order' => $order->invoice_number]),
            'track_url' => route('track', ['order' => $order->invoice_number]),
        ]);
    }
}

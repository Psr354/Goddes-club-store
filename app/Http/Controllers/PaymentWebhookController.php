<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Notifications\OrderConfirmed;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle incoming webhook notifications from Payment Gateway (Midtrans / DOKU).
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // 1. Identify order invoice from various payload conventions
        $invoiceNumber = $payload['order_id']
            ?? $payload['invoice_number']
            ?? ($payload['transaction_details']['order_id'] ?? null);

        if (! $invoiceNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Missing order identification in payload.',
            ], 400);
        }

        // 2. Identify transaction status
        $rawStatus = strtolower(
            $payload['transaction_status']
            ?? $payload['status']
            ?? ($payload['transaction']['status'] ?? 'pending')
        );

        $rawPaymentMethod = (string) ($payload['payment_type']
            ?? $payload['payment_method']
            ?? 'qris');
        $paymentMethod = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawPaymentMethod);
        if ($paymentMethod === '') {
            $paymentMethod = 'qris';
        }

        $order = Order::where('invoice_number', $invoiceNumber)
            ->with(['items.variant.product', 'payment', 'user'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => "Order {$invoiceNumber} not found.",
            ], 404);
        }

        // Enforce HMAC SHA512 signature check unconditionally
        $serverKey = MidtransService::serverKey();
        if (empty($serverKey)) {
            Log::critical('Payment webhook received but MIDTRANS_SERVER_KEY is not configured.');

            return response()->json([
                'success' => false,
                'message' => 'Payment gateway misconfiguration.',
            ], 500);
        }

        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? $invoiceNumber);

        if (! $signatureKey || ! MidtransService::verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing webhook signature.',
            ], 403);
        }

        // 3. Process status transition
        if (in_array($rawStatus, ['settlement', 'capture', 'paid', 'success'])) {
            $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? 'accept'));
            if ($rawStatus === 'capture') {
                if ($fraudStatus === 'challenge') {
                    return response()->json([
                        'success' => true,
                        'message' => "Order {$invoiceNumber} is challenged by fraud detection system.",
                        'status' => 'challenge',
                    ]);
                }
                if ($fraudStatus !== 'accept') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Fraud status rejected.',
                    ], 400);
                }
            }

            $payloadGrossAmount = (float) ($payload['gross_amount'] ?? 0);
            $rate = (float) config('services.midtrans.usd_to_idr_rate', 1.0);
            $multiplier = $rate > 0 ? $rate : 1.0;
            $expectedGrossAmount = (float) round($order->grand_total * $multiplier);

            if (abs($payloadGrossAmount - $expectedGrossAmount) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gross amount mismatch.',
                ], 400);
            }

            $this->processSettlement($order, $paymentMethod, $payload);

            return response()->json([
                'success' => true,
                'message' => "Order {$invoiceNumber} marked as PAID.",
                'status' => 'paid',
            ]);
        }

        if (in_array($rawStatus, ['deny', 'cancel', 'expire', 'expired', 'failed'])) {
            $this->processFailure($order, $payload);

            return response()->json([
                'success' => true,
                'message' => "Order {$invoiceNumber} marked as CANCELLED.",
                'status' => 'cancelled',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Webhook received for status {$rawStatus}.",
            'status' => $order->status,
        ]);
    }

    /**
     * Mark order as paid, permanently deduct stock, release reserved stock, and notify customer.
     */
    public function processSettlement(Order $order, string $paymentMethod, array $rawPayload): void
    {
        DB::transaction(function () use ($order, $paymentMethod, $rawPayload) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            // Only pending_payment orders can transition to paid via settlement
            if (! $lockedOrder || $lockedOrder->status !== 'pending_payment') {
                if ($lockedOrder && $lockedOrder->status === 'cancelled') {
                    Log::warning("Late settlement webhook received for cancelled order #{$lockedOrder->invoice_number}. Manual review or refund required.");
                }

                return;
            }

            // Update Payment
            $payment = $lockedOrder->payment ?? Payment::firstOrNew(['order_id' => $lockedOrder->id]);
            $payment->status = 'paid';
            $payment->payment_method = $paymentMethod;
            $payment->paid_at = now();
            $payment->raw_response = $rawPayload;
            $payment->save();

            // Update Order status
            $lockedOrder->status = 'paid';
            $lockedOrder->save();

            // Deduct stock permanently and release reserved stock
            foreach ($lockedOrder->items as $item) {
                $variant = ProductVariant::where('id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($variant) {
                    $variant->decrement('stock', $item->quantity);
                    $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));

                    // Deduct root product stock if applicable
                    if ($variant->product) {
                        $variant->product->decrement('stock', $item->quantity);
                    }
                }
            }

            // Add Order Tracking event
            $lockedOrder->trackings()->create([
                'status' => 'paid',
                'title' => 'Pembayaran Berhasil Dikonfirmasi',
                'description' => 'Pembayaran diterima melalui '.strtoupper($paymentMethod).'. Merch favoritmu sedang kami siapkan.',
                'occurred_at' => now(),
            ]);
        });

        // Dispatch confirmation email
        try {
            if ($order->user) {
                $order->user->notify(new OrderConfirmed($order));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Mark order as cancelled and release reserved stock back into inventory.
     */
    public function processFailure(Order $order, array $rawPayload): void
    {
        DB::transaction(function () use ($order, $rawPayload) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            // Only pending_payment orders can be cancelled via webhook failure
            if (! $lockedOrder || $lockedOrder->status !== 'pending_payment') {
                return;
            }

            // Update Payment
            $payment = $lockedOrder->payment ?? Payment::firstOrNew(['order_id' => $lockedOrder->id]);
            $payment->status = 'failed';
            $payment->raw_response = $rawPayload;
            $payment->save();

            // Update Order status
            $lockedOrder->status = 'cancelled';
            $lockedOrder->save();

            // Restore coupon quota if applied
            if ($lockedOrder->coupon_id) {
                $coupon = $lockedOrder->coupon()->lockForUpdate()->first();
                if ($coupon && $coupon->used_count > 0) {
                    $coupon->decrement('used_count');
                }
            }

            // Release reserved stock back to available pool
            foreach ($lockedOrder->items as $item) {
                $variant = ProductVariant::where('id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($variant) {
                    $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                }
            }

            // Add Order Tracking event
            $lockedOrder->trackings()->create([
                'status' => 'cancelled',
                'title' => 'Pesanan Dibatalkan',
                'description' => 'Pembayaran tidak diselesaikan atau kedaluwarsa. Stok barang telah dikembalikan ke etalase.',
                'occurred_at' => now(),
            ]);
        });
    }

    /**
     * Simulate payment settlement or cancellation for demo / local testing.
     */
    public function simulate(Request $request): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint simulasi tidak tersedia di lingkungan produksi.',
            ], 403);
        }

        $data = $request->validate([
            'invoice_number' => 'required|string',
            'action' => 'required|in:pay,cancel',
            'payment_method' => 'nullable|string',
        ]);

        $order = Order::where('invoice_number', $data['invoice_number'])
            ->with(['items.variant.product', 'payment', 'user'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => "Order #{$data['invoice_number']} tidak ditemukan.",
            ], 404);
        }

        if ($data['action'] === 'pay') {
            $this->processSettlement(
                order: $order,
                paymentMethod: $data['payment_method'] ?? 'doku_qris',
                rawPayload: ['simulation' => true, 'time' => now()->toIso8601String()]
            );

            return response()->json([
                'success' => true,
                'message' => "Simulasi berhasil! Order #{$order->invoice_number} telah DIBAYAR.",
                'status' => 'paid',
                'track_url' => route('track', ['order' => $order->invoice_number]),
            ]);
        }

        $this->processFailure(
            order: $order,
            rawPayload: ['simulation' => true, 'action' => 'cancelled']
        );

        return response()->json([
            'success' => true,
            'message' => "Simulasi berhasil! Order #{$order->invoice_number} telah DIBATALKAN.",
            'status' => 'cancelled',
            'track_url' => route('track', ['order' => $order->invoice_number]),
        ]);
    }
}

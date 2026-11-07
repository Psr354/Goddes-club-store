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

        $paymentMethod = $payload['payment_type']
            ?? $payload['payment_method']
            ?? 'qris';

        $order = Order::where('invoice_number', $invoiceNumber)
            ->with(['items.variant.product', 'payment', 'user'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => "Order {$invoiceNumber} not found.",
            ], 404);
        }

        // Enforce HMAC SHA512 signature check when server key is configured or in production
        $serverKey = MidtransService::serverKey();
        if ($serverKey || app()->isProduction()) {
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
        }

        // 3. Process status transition
        if (in_array($rawStatus, ['settlement', 'capture', 'paid', 'success'])) {
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
        // Idempotency: if already paid, do nothing
        if ($order->status === 'paid' || $order->status === 'processing' || $order->status === 'shipped' || $order->status === 'completed') {
            return;
        }

        DB::transaction(function () use ($order, $paymentMethod, $rawPayload) {
            // Update Payment
            $payment = $order->payment ?? Payment::firstOrNew(['order_id' => $order->id]);
            $payment->status = 'paid';
            $payment->payment_method = $paymentMethod;
            $payment->paid_at = now();
            $payment->raw_response = $rawPayload;
            $payment->save();

            // Update Order status
            $order->status = 'paid';
            $order->save();

            // Deduct stock permanently and release reserved stock
            foreach ($order->items as $item) {
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
            $order->trackings()->create([
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
        if ($order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order, $rawPayload) {
            // Update Payment
            $payment = $order->payment ?? Payment::firstOrNew(['order_id' => $order->id]);
            $payment->status = 'failed';
            $payment->raw_response = $rawPayload;
            $payment->save();

            // Update Order status
            $order->status = 'cancelled';
            $order->save();

            // Release reserved stock back to available pool
            foreach ($order->items as $item) {
                $variant = ProductVariant::where('id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($variant) {
                    $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                }
            }

            // Add Order Tracking event
            $order->trackings()->create([
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

<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransService
{
    public const SANDBOX_SNAP_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    public const PRODUCTION_SNAP_URL = 'https://app.midtrans.com/snap/v1/transactions';

    public const SANDBOX_SNAP_JS = 'https://app.sandbox.midtrans.com/snap/snap.js';
    public const PRODUCTION_SNAP_JS = 'https://app.midtrans.com/snap/snap.js';

    /**
     * Get the server key from config.
     */
    public static function serverKey(): ?string
    {
        return config('services.midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY');
    }

    /**
     * Get the client key from config.
     */
    public static function clientKey(): ?string
    {
        return config('services.midtrans.client_key') ?? env('MIDTRANS_CLIENT_KEY');
    }

    /**
     * Whether the environment is production.
     */
    public static function isProduction(): bool
    {
        return (bool) (config('services.midtrans.is_production') ?? env('MIDTRANS_IS_PRODUCTION', false));
    }

    /**
     * Get the Snap JS script URL for frontend inclusion.
     */
    public static function snapJsUrl(): string
    {
        return self::isProduction() ? self::PRODUCTION_SNAP_JS : self::SANDBOX_SNAP_JS;
    }

    /**
     * Determine if valid-looking Midtrans credentials are configured.
     */
    public static function isConfigured(): bool
    {
        $serverKey = self::serverKey();

        return ! empty($serverKey) && ! str_contains($serverKey, 'dummy') && ! str_contains($serverKey, 'test-key');
    }

    /**
     * Create a Snap transaction token for an order.
     *
     * @return array{token: string, redirect_url: string, simulated: bool}
     */
    public static function createTransaction(Order $order): array
    {
        $serverKey = self::serverKey();
        $endpoint = self::isProduction() ? self::PRODUCTION_SNAP_URL : self::SANDBOX_SNAP_URL;

        // Build item details according to Midtrans specification
        $itemDetails = [];
        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => (string) $item->product_variant_id,
                'price' => (int) round($item->unit_price),
                'quantity' => (int) $item->quantity,
                'name' => mb_strimwidth($item->product_name . ($item->sku ? ' (' . $item->sku . ')' : ''), 0, 48),
            ];
        }

        // Add discount as negative item if present
        if ($order->discount_amount > 0) {
            $itemDetails[] = [
                'id' => 'DISCOUNT',
                'price' => -(int) round($order->discount_amount),
                'quantity' => 1,
                'name' => 'Diskon Promo',
            ];
        }

        // Add shipping fee as line item if present
        if ($order->shipping_fee > 0) {
            $itemDetails[] = [
                'id' => 'SHIPPING',
                'price' => (int) round($order->shipping_fee),
                'quantity' => 1,
                'name' => 'Ongkos Kirim (' . ($order->courier_code ?: 'JNE') . ')',
            ];
        }

        $grossAmount = (int) round($order->grand_total);

        $payload = [
            'transaction_details' => [
                'order_id' => $order->invoice_number,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->address?->recipient_name ?? ($order->user?->name ?? 'Customer'),
                'email' => $order->user?->email ?? 'customer@example.com',
                'phone' => $order->address?->phone ?? '+628123456789',
                'shipping_address' => [
                    'first_name' => $order->address?->recipient_name,
                    'phone' => $order->address?->phone,
                    'address' => $order->address?->full_address,
                    'postal_code' => $order->address?->postal_code,
                    'country_code' => 'IDN',
                ],
            ],
            'callbacks' => [
                'finish' => route('track', ['order' => $order->invoice_number]),
            ],
        ];

        // Attempt live API call if server key is configured
        if (self::isConfigured()) {
            try {
                $response = Http::timeout(10)
                    ->withBasicAuth($serverKey, '')
                    ->acceptJson()
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    if (! empty($json['token'])) {
                        return [
                            'token' => $json['token'],
                            'redirect_url' => $json['redirect_url'] ?? '',
                            'simulated' => false,
                        ];
                    }
                }

                Log::warning('Midtrans Snap API responded with error: ' . $response->body());
            } catch (\Throwable $e) {
                Log::error('Midtrans Snap request exception: ' . $e->getMessage());
            }
        }

        // Graceful sandbox simulated fallback for offline/development testing
        $mockToken = 'sandbox-snap-' . Str::random(24);
        return [
            'token' => $mockToken,
            'redirect_url' => route('track', ['order' => $order->invoice_number, 'snap_simulated' => 1]),
            'simulated' => true,
        ];
    }

    /**
     * Verify Midtrans notification signature key.
     */
    public static function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = self::serverKey();
        if (empty($serverKey)) {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $signatureKey);
    }
}

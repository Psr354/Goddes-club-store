<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\MidtransService;

class MidtransIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'price' => 35.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'GC-TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 35.00,
            'stock' => 10,
            'reserved_stock' => 0,
            'weight_gram' => 300,
        ]);
    }

    public function test_checkout_generates_midtrans_snap_token(): void
    {
        $this->actingAs($this->user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'size' => 'M',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/checkout', [
            'recipient_name' => 'John Doe',
            'phone' => '+628111222333',
            'full_address' => 'Jl. Jenderal Sudirman No. 1',
            'postal_code' => '12190',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('snap_token'));

        $this->assertDatabaseHas('payments', [
            'provider' => 'midtrans',
            'payment_method' => 'snap',
            'status' => 'pending',
        ]);
    }

    public function test_midtrans_service_calls_snap_api_when_configured(): void
    {
        Config::set('services.midtrans.server_key', 'SB-Mid-server-REAL-KEY-12345');
        Config::set('services.midtrans.client_key', 'SB-Mid-client-REAL-KEY-12345');
        Config::set('services.midtrans.is_production', false);

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'real-snap-token-abc-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/real-snap-token-abc-123',
            ], 201),
        ]);

        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'recipient_name' => 'John Doe',
            'phone' => '+628111222333',
            'full_address' => 'Jl. Jenderal Sudirman No. 1',
            'postal_code' => '12190',
        ]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'GCS-SNAP001',
            'address_id' => $address->id,
            'subtotal' => 35.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 40.00,
            'status' => 'pending_payment',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'unit_price' => 35.00,
        ]);

        $result = MidtransService::createTransaction($order);

        $this->assertEquals('real-snap-token-abc-123', $result['token']);
        $this->assertFalse($result['simulated']);
    }

    public function test_midtrans_webhook_signature_validation(): void
    {
        Config::set('services.midtrans.server_key', 'test-secret-server-key');

        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'recipient_name' => 'John Doe',
            'phone' => '+628111222333',
            'full_address' => 'Jl. Jenderal Sudirman No. 1',
            'postal_code' => '12190',
        ]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'GCS-SIG001',
            'address_id' => $address->id,
            'subtotal' => 35.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 40.00,
            'status' => 'pending_payment',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'PAY-SIG001',
            'amount' => 40.00,
            'status' => 'pending',
        ]);

        $orderId = 'GCS-SIG001';
        $statusCode = '200';
        $grossAmount = '40.00';
        $serverKey = 'test-secret-server-key';
        $validSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Valid signature succeeds
        $response = $this->postJson('/api/payment/webhook', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Invalid signature fails
        $responseInvalid = $this->postJson('/api/payment/webhook', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => 'invalid-signature-hash',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);

        $responseInvalid->assertStatus(403);
    }
}

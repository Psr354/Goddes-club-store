<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\OrderConfirmed;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Order $order;
    protected ProductVariant $variant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'price' => 35.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'GC-TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 35.00,
            'stock' => 10,
            'reserved_stock' => 2,
            'weight_gram' => 300,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'recipient_name' => 'Alex Morgan',
            'phone' => '+628123456789',
            'full_address' => 'Jl. Melodi No. 16',
            'postal_code' => '12160',
            'is_primary' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'invoice_number' => 'GCS-TEST001',
            'address_id' => $address->id,
            'subtotal' => 70.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 75.00,
            'status' => 'pending_payment',
            'courier_code' => 'JNE',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 35.00,
        ]);

        Payment::create([
            'order_id' => $this->order->id,
            'transaction_id' => 'PAY-TEST001',
            'status' => 'pending',
            'amount' => 75.00,
        ]);
    }

    public function test_webhook_settlement_deducts_stock_and_marks_paid(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/payment/webhook', [
            'order_id' => 'GCS-TEST001',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'gross_amount' => 75.00,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'paid');

        // Order status is now paid
        $this->order->refresh();
        $this->assertEquals('paid', $this->order->status);

        // Physical stock decremented from 10 to 8, reserved decremented from 2 to 0
        $this->variant->refresh();
        $this->assertEquals(8, $this->variant->stock);
        $this->assertEquals(0, $this->variant->reserved_stock);

        Notification::assertSentTo($this->user, OrderConfirmed::class);
    }

    public function test_webhook_expire_cancels_order_and_releases_reserved_stock(): void
    {
        $response = $this->postJson('/api/payment/webhook', [
            'order_id' => 'GCS-TEST001',
            'transaction_status' => 'expire',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'cancelled');

        $this->order->refresh();
        $this->assertEquals('cancelled', $this->order->status);

        // Physical stock remains 10, reserved decremented from 2 to 0
        $this->variant->refresh();
        $this->assertEquals(10, $this->variant->stock);
        $this->assertEquals(0, $this->variant->reserved_stock);
    }

    public function test_payment_simulation_endpoint(): void
    {
        $response = $this->postJson('/api/payment/simulate', [
            'invoice_number' => 'GCS-TEST001',
            'action' => 'pay',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'paid');

        $this->order->refresh();
        $this->assertEquals('paid', $this->order->status);
    }
}

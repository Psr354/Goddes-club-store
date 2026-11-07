<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'price' => 35.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'GC-TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 35.00,
            'stock' => 10,
            'reserved_stock' => 0,
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
            'invoice_number' => 'GCS-SECURE99',
            'address_id' => $address->id,
            'subtotal' => 35.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 40.00,
            'status' => 'paid',
            'courier_code' => 'JNE',
            'tracking_number' => 'JNE123456789',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 35.00,
        ]);
    }

    public function test_guest_can_track_order_via_invoice_number(): void
    {
        $response = $this->postJson('/api/track', [
            'order' => 'GCS-SECURE99',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('invoice_number', 'GCS-SECURE99');
        $response->assertJsonPath('tracking_number', 'JNE123456789');
    }

    public function test_guest_cannot_enumerate_order_via_sequential_gc_id(): void
    {
        $reference = $this->order->reference();

        $response = $this->postJson('/api/track', [
            'order' => $reference,
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    }

    public function test_authenticated_owner_can_track_via_gc_reference(): void
    {
        $reference = $this->order->reference();

        $response = $this->actingAs($this->user)->postJson('/api/track', [
            'order' => $reference,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('invoice_number', 'GCS-SECURE99');
    }

    public function test_other_user_cannot_access_order_via_gc_reference(): void
    {
        $reference = $this->order->reference();

        $response = $this->actingAs($this->otherUser)->postJson('/api/track', [
            'order' => $reference,
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    }
}

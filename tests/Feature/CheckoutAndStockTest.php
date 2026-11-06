<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class CheckoutAndStockTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'price' => 35.00,
            'stock' => 5,
            'status' => 'active',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'GC-TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 35.00,
            'stock' => 5,
            'reserved_stock' => 0,
            'weight_gram' => 300,
        ]);
    }

    public function test_checkout_reserves_stock_atomically(): void
    {
        $user = User::factory()->create();

        // Add 3 to cart using product_id & size
        $this->actingAs($user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'size' => 'M',
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'recipient_name' => 'Alex Morgan',
            'phone' => '+628123456789',
            'full_address' => 'Jl. Melodi No. 16, Kebayoran Baru',
            'postal_code' => '12160',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending_payment',
        ]);

        // Reserved stock should now be 3
        $this->variant->refresh();
        $this->assertEquals(3, $this->variant->reserved_stock);
        $this->assertEquals(2, $this->variant->availableStock());
    }

    public function test_checkout_fails_if_stock_insufficient(): void
    {
        $user = User::factory()->create();

        // Variant only has 5 stock. Try to add 10 directly to cart
        $cart = $user->carts()->create();
        $cart->items()->create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/api/checkout', [
            'recipient_name' => 'Alex Morgan',
            'phone' => '+628123456789',
            'full_address' => 'Jl. Melodi No. 16',
            'postal_code' => '12160',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        // Reserved stock should remain 0
        $this->variant->refresh();
        $this->assertEquals(0, $this->variant->reserved_stock);
    }
}

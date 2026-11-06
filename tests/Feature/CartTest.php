<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected ProductVariant $variant;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'price' => 35.00,
            'stock' => 20,
            'status' => 'active',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'GC-TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 35.00,
            'stock' => 20,
            'reserved_stock' => 0,
            'weight_gram' => 300,
        ]);
    }

    public function test_user_can_add_item_to_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'size' => 'M',
            'quantity' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cart_count', 2);
    }

    public function test_user_can_apply_coupon(): void
    {
        $user = User::factory()->create();

        Coupon::create([
            'code' => 'GODDESS10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($user)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'size' => 'M',
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/api/cart/promo', [
            'code' => 'GODDESS10',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('discount', 3.5);
    }
}

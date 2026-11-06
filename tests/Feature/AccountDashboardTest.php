<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Product $product;

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
    }

    public function test_user_can_access_account_page(): void
    {
        $response = $this->actingAs($this->user)->get('/account');
        $response->assertStatus(200);
        $response->assertSee('Back for the');
    }

    public function test_user_can_store_new_address(): void
    {
        $response = $this->actingAs($this->user)->post('/account/address', [
            'recipient_name' => 'Nadia Putri',
            'phone' => '+628111222333',
            'full_address' => 'Jl. Kemang Raya No. 10',
            'postal_code' => '12730',
            'is_primary' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'recipient_name' => 'Nadia Putri',
            'is_primary' => true,
        ]);
    }

    public function test_user_can_delete_address(): void
    {
        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'recipient_name' => 'Delete Me',
            'phone' => '+62800000',
            'full_address' => 'Alamat lama',
            'postal_code' => '12000',
        ]);

        $response = $this->actingAs($this->user)->delete("/account/address/{$address->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_user_can_toggle_wishlist(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/wishlist/toggle', [
            'product_id' => $this->product->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'added');
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        // Toggle again to remove
        $response2 = $this->actingAs($this->user)->postJson('/api/wishlist/toggle', [
            'product_id' => $this->product->id,
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('status', 'removed');
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }
}

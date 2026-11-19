<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private Coupon $coupon;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $category = Category::create([
            'name' => 'Music',
            'slug' => 'music',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Debut Vinyl Edition',
            'slug' => 'debut-vinyl-edition',
            'price' => 40.00,
            'stock' => 20,
            'status' => 'active',
        ]);

        ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'VINYL-STANDARD',
            'attributes' => ['size' => 'Standard'],
            'price' => 40.00,
            'stock' => 20,
            'reserved_stock' => 0,
            'weight_gram' => 500,
        ]);

        $this->coupon = Coupon::create([
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10.00,
            'is_active' => true,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->admin->id,
            'recipient_name' => 'Admin Tester',
            'phone' => '+628123456789',
            'full_address' => 'Jakarta Selatan',
            'city_id' => 152,
            'district_id' => 2100,
            'postal_code' => '12160',
            'is_primary' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-20261119-0001',
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'subtotal' => 40.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 45.00,
        ]);
    }

    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('DEMO ADMIN DASHBOARD');
    }

    public function test_admin_products_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.index'));
        $response->assertStatus(200);
        $response->assertSee('Debut Vinyl Edition');
    }

    public function test_admin_products_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.edit', $this->product));
        $response->assertStatus(200);
        $response->assertSee('Debut Vinyl Edition');
        $response->assertSee('PILIH DARI DEVICE');
    }

    public function test_admin_coupons_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
        $response->assertSee('TEST10');
    }

    public function test_admin_coupons_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.create'));
        $response->assertStatus(200);
        $response->assertSee('Coupon details');
    }

    public function test_admin_coupons_edit_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.coupons.edit', $this->coupon));
        $response->assertStatus(200);
        $response->assertSee('TEST10');
    }

    public function test_admin_orders_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $response->assertStatus(200);
        $response->assertSee('INV-20261119-0001');
    }

    public function test_admin_orders_show_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $this->order));
        $response->assertStatus(200);
        $response->assertSee('INV-20261119-0001');
    }

    public function test_admin_users_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('admin@test.com');
    }

    public function test_admin_can_update_user_role(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.users.role', $customer), [
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect();
        $this->assertEquals(User::ROLE_ADMIN, $customer->fresh()->role);
    }
}

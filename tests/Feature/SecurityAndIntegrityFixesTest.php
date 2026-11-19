<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecurityAndIntegrityFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_manual_order_status_update_to_paid_deducts_stock_and_releases_reserved(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Tour Tee',
            'slug' => 'tour-tee',
            'price' => 30.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEE-M',
            'attributes' => ['size' => 'M'],
            'price' => 30.00,
            'stock' => 10,
            'reserved_stock' => 2,
            'weight_gram' => 300,
        ]);

        $address = UserAddress::create([
            'user_id' => $this->admin->id,
            'recipient_name' => 'Customer Test',
            'phone' => '+62812345678',
            'full_address' => 'Jakarta',
            'postal_code' => '12160',
            'is_primary' => true,
        ]);

        $order = Order::create([
            'user_id' => $this->admin->id,
            'invoice_number' => 'INV-TEST-001',
            'address_id' => $address->id,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'subtotal' => 60.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 65.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 30.00,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'PAY-TEST-001',
            'amount' => 65.00,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update', $order), [
            'status' => 'paid',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $variant->refresh();
        $this->assertEquals(8, $variant->stock);
        $this->assertEquals(0, $variant->reserved_stock);

        $this->assertEquals('paid', $order->payment->status);
    }

    public function test_admin_can_update_customer_role_when_single_admin_exists(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        // There is only 1 admin ($this->admin)
        $this->assertEquals(1, User::where('role', User::ROLE_ADMIN)->count());

        $response = $this->actingAs($this->admin)->patch(route('admin.users.role', $customer), [
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(User::ROLE_CUSTOMER, $customer->fresh()->role);
    }

    public function test_svg_upload_is_rejected_on_product_create(): void
    {
        $category = Category::create(['name' => 'Music', 'slug' => 'music']);

        $svgFile = UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml');

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'name' => 'Insecure SVG Product',
            'category_id' => $category->id,
            'status' => 'active',
            'price' => 15.00,
            'stock' => 10,
            'image_file' => $svgFile,
        ]);

        $response->assertSessionHasErrors(['image_file']);
    }

    public function test_user_model_role_attribute_is_not_mass_assignable(): void
    {
        $user = new User([
            'name' => 'Hacker',
            'email' => 'hacker@test.com',
            'password' => 'secret123',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertNull($user->role);
        $this->assertNotEquals(User::ROLE_ADMIN, $user->role);
    }

    public function test_forgot_password_does_not_enumerate_users(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent_user_for_audit@example.com',
        ]);

        $response->assertSessionHas('status');
        $response->assertSessionDoesntHaveErrors();
    }
}

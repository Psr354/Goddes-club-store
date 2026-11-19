<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@goddessclub.test',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->category = Category::create([
            'name' => 'Apparel',
            'slug' => 'apparel',
        ]);
    }

    public function test_guest_cannot_access_product_create_form(): void
    {
        $response = $this->get(route('admin.products.create'));
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_product_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.create'));

        $response->assertStatus(200);
        $response->assertSee('PILIH DARI DEVICE');
        $response->assertSee('Informasi Dasar');
        $response->assertSee('Ukuran &amp; Varian Produk', false);
    }

    public function test_admin_can_create_product_with_device_image_upload(): void
    {
        Storage::fake('public');

        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('tour-tshirt.png', $pngContent);

        $payload = [
            'name' => 'The Goddess Tour Vintage Tee',
            'category_id' => $this->category->id,
            'slug' => 'the-goddess-tour-vintage-tee',
            'description' => '100% premium heavyweight cotton.',
            'status' => 'active',
            'price' => 45.00,
            'stock' => 100,
            'tag' => 'Limited Edition',
            'kind' => 'tee',
            'weight_gram' => 350,
            'image_file' => $file,
            'sizes' => ['S', 'M', 'L'],
            'variant_prices' => [45.00, 45.00, 48.00],
            'variant_stocks' => [20, 50, 30],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $payload);

        $product = Product::where('slug', 'the-goddess-tour-vintage-tee')->first();
        $this->assertNotNull($product);

        $response->assertRedirect(route('admin.products.edit', $product));

        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('/storage/products/', $product->image);

        $storedPath = str_replace('/storage/', '', $product->image);
        Storage::disk('public')->assertExists($storedPath);

        $this->assertCount(3, $product->variants);
        $this->assertEquals(100, $product->stock);
    }

    public function test_admin_can_create_product_with_image_url_fallback(): void
    {
        $payload = [
            'name' => 'Goddess Tour Vinyl Record',
            'category_id' => $this->category->id,
            'slug' => 'goddess-tour-vinyl-record',
            'description' => 'Limited deluxe vinyl pressed in red wax.',
            'status' => 'active',
            'price' => 38.00,
            'stock' => 50,
            'image' => 'https://images.unsplash.com/photo-example-vinyl',
            'tag' => 'Bestseller',
            'kind' => 'vinyl',
            'weight_gram' => 500,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $payload);

        $product = Product::where('slug', 'goddess-tour-vinyl-record')->first();
        $this->assertNotNull($product);
        $this->assertEquals('https://images.unsplash.com/photo-example-vinyl', $product->image);
    }

    public function test_admin_can_update_product_and_replace_uploaded_image(): void
    {
        Storage::fake('public');

        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $initialFile = UploadedFile::fake()->createWithContent('initial.png', $pngContent);
        $initialPath = $initialFile->store('products', 'public');

        $product = Product::create([
            'name' => 'Original Cap',
            'category_id' => $this->category->id,
            'slug' => 'original-cap',
            'price' => 25.00,
            'stock' => 10,
            'status' => 'active',
            'image' => '/storage/'.$initialPath,
        ]);

        Storage::disk('public')->assertExists($initialPath);

        $newFile = UploadedFile::fake()->createWithContent('updated-cap.png', $pngContent);

        $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
            'name' => 'Original Cap Updated',
            'category_id' => $this->category->id,
            'slug' => 'original-cap',
            'price' => 28.00,
            'stock' => 15,
            'status' => 'active',
            'image_file' => $newFile,
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertEquals('Original Cap Updated', $product->name);
        $this->assertEquals(28.00, (float) $product->price);

        // Old file deleted, new file exists
        Storage::disk('public')->assertMissing($initialPath);
        $newStoredPath = str_replace('/storage/', '', $product->image);
        Storage::disk('public')->assertExists($newStoredPath);
    }
}

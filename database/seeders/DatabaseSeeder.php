<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTracking;
use App\Models\Payment;
use App\Models\Product;
use App\Models\UserAddress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->call(ProductSeeder::class);
        $this->seedCoupons();
        $this->seedSampleOrder();
    }

    /**
     * The two demo accounts: one admin, one customer.
     */
    protected function seedUsers(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Store Admin',
                'password' => 'Admin123',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Customer',
                'password' => 'Goddess123',
                'role' => User::ROLE_CUSTOMER,
                'email_verified_at' => now(),
            ]
        );
    }

    protected function seedCoupons(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'GODDESS10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 0,
                'max_discount_amount' => null,
                'usage_limit' => null,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'CLUB10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 0,
                'max_discount_amount' => null,
                'usage_limit' => null,
                'is_active' => true,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
            ]
        );
    }

    /**
     * A paid order so the admin dashboard has something to show.
     */
    protected function seedSampleOrder(): void
    {
        $user = User::where('email', 'demo@example.com')->first();
        $product = Product::where('slug', 'the-goddess-tour-tee')->first();

        if (! $user || ! $product) {
            return;
        }

        if (Order::where('user_id', $user->id)->exists()) {
            return;
        }

        $address = UserAddress::create([
            'user_id' => $user->id,
            'recipient_name' => 'Demo Customer',
            'phone' => '+6281234567890',
            'full_address' => 'Jl. Melati No. 12, Jakarta Selatan',
            'city_id' => 0,
            'district_id' => 0,
            'postal_code' => '12730',
            'is_primary' => true,
        ]);

        $variant = $product->variants()->where('attributes->size', 'M')->first()
            ?? $product->variants()->first();

        $order = Order::create([
            'user_id' => $user->id,
            'invoice_number' => 'GCS-DEMO0001',
            'address_id' => $address->id,
            'subtotal' => 35.00,
            'discount_amount' => 0,
            'shipping_fee' => 5.00,
            'grand_total' => 40.00,
            'status' => 'shipped',
            'courier_code' => 'JNE',
            'tracking_number' => 'JNE123456789',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'sku' => $variant?->sku ?? 'THE-GODDESS-TOUR-TEE-M',
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 35.00,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'doku',
            'provider_reference' => 'DEMO-REF-0001',
            'status' => 'paid',
        ]);

        foreach ([
            ['pending_payment', 'Pesanan dibuat dan menunggu pembayaran.', 3],
            ['paid', 'Pembayaran diterima, pesanan sedang kami proses.', 2],
            ['processing', 'Barangmu sedang dikemas dengan cinta.', 1],
            ['shipped', 'Paketmu sudah dalam perjalanan.', 0],
        ] as [$status, $description, $daysAgo]) {
            $order->trackings()->create([
                'status' => $status,
                'description' => $description,
                'occurred_at' => now()->subDays($daysAgo),
            ]);
        }
    }
}

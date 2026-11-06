<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;

class ProductSeeder extends Seeder
{
    /**
     * The catalogue as shown in the Figma design.
     */
    public const CATALOGUE = [
        [
            'category' => ['name' => 'Apparel', 'slug' => 'apparel'],
            'name' => 'The Goddess Tour Tee',
            'slug' => 'the-goddess-tour-tee',
            'description' => 'A little piece of the feeling. Official Reality Club merchandise, made in small batches with care.',
            'price' => 35.00,
            'stock' => 5,
            'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85',
            'tag' => 'Bestseller',
            'kind' => 'tee',
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
        ],
        [
            'category' => ['name' => 'Music & vinyl', 'slug' => 'music'],
            'name' => 'Reality Club Presents...',
            'slug' => 'reality-club-presents',
            'description' => 'Limited edition vinyl release. Pressed in small numbers for the front row.',
            'price' => 42.00,
            'stock' => 3,
            'image' => 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=700&q=85',
            'tag' => 'Limited edition',
            'kind' => 'vinyl',
            'sizes' => ['One Size'],
        ],
        [
            'category' => ['name' => 'Accessories', 'slug' => 'accessories'],
            'name' => 'All Night Long Tote',
            'slug' => 'all-night-long-tote',
            'description' => 'Fan favorite tote bag. Room for the records, the flyers and everything after the encore.',
            'price' => 22.00,
            'stock' => 10,
            'image' => 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=700&q=85',
            'tag' => 'Fan favorite',
            'kind' => 'tote',
            'sizes' => ['One Size'],
        ],
        [
            'category' => ['name' => 'Accessories', 'slug' => 'accessories'],
            'name' => 'The After Hours Cap',
            'slug' => 'the-after-hours-cap',
            'description' => 'Limited edition cap. Low profile, high volume.',
            'price' => 28.00,
            'stock' => 8,
            'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=700&q=85',
            'tag' => 'Limited edition',
            'kind' => 'cap',
            'sizes' => ['One Size'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CATALOGUE as $row) {
            $category = Category::updateOrCreate(
                ['slug' => $row['category']['slug']],
                ['name' => $row['category']['name']]
            );

            $product = Product::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'status' => 'active',
                    'price' => $row['price'],
                    'stock' => $row['stock'],
                    'image' => $row['image'],
                    'tag' => $row['tag'],
                    'kind' => $row['kind'],
                ]
            );

            foreach ($row['sizes'] as $size) {
                ProductVariant::updateOrCreate(
                    ['sku' => $product->slug.'-'.strtoupper(str_replace(' ', '', $size))],
                    [
                        'product_id' => $product->id,
                        'attributes' => ['size' => $size],
                        'price' => $row['price'],
                        'stock' => $row['stock'],
                        'reserved_stock' => 0,
                        'weight_gram' => 300,
                    ]
                );
            }
        }
    }
}

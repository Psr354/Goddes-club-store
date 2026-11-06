<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * The product detail page.
     */
    public function show(Request $request, string $slug)
    {
        $product = Product::with(['category', 'variants'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with(['category', 'variants'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(3)
            ->get();

        return view('product', [
            'product' => $product,
            'related' => $related,
            'categories' => Category::with('products')->get(),
        ]);
    }
}

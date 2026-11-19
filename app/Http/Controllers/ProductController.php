<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * The product detail page.
     */
    public function show(Request $request, string $slug)
    {
        $product = Product::with(['category', 'variants'])
            ->where('slug', $slug)
            ->active()
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
        ]);
    }
}

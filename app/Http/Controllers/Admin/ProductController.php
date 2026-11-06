<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
            ))
            ->when($request->query('category'), fn ($query, $category) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $category)
            ))
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['status' => 'active', 'stock' => 0, 'price' => 0]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $product = Product::create($data);
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->variants()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted.');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'slug' => 'required|string|max:255|unique:products,slug'.($product ? ','.$product->id : ''),
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:500',
            'tag' => 'nullable|string|max:50',
            'kind' => 'nullable|string|max:30',
        ]);

        $data['slug'] = Str::slug($data['slug']);

        return $data;
    }

    /**
     * Replace the variant rows of a product with the submitted size/price/stock list.
     */
    protected function syncVariants(Request $request, Product $product): void
    {
        $sizes = (array) $request->input('sizes', []);
        $prices = (array) $request->input('variant_prices', []);
        $stocks = (array) $request->input('variant_stocks', []);

        $keep = [];

        foreach ($sizes as $index => $size) {
            $size = trim((string) $size);

            if ($size === '') {
                continue;
            }

            $variant = $product->variants()->updateOrCreate(
                ['sku' => $product->slug.'-'.strtoupper(str_replace(' ', '', $size))],
                [
                    'attributes' => ['size' => $size],
                    'price' => (float) ($prices[$index] ?? $product->price),
                    'stock' => (int) ($stocks[$index] ?? $product->stock),
                    'weight_gram' => (int) $request->input('weight_gram', 300),
                ]
            );

            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep ?: [0])->delete();
    }
}

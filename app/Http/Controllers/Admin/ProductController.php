<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('products', 'public');
            $data['image'] = '/storage/'.$path;
        }
        unset($data['image_file']);

        $product = Product::create($data);
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Produk berhasil dibuat.');
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
        $data = $this->validated($request, $product);

        if ($request->hasFile('image_file')) {
            if ($product->image && str_starts_with($product->image, '/storage/products/')) {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image_file')->store('products', 'public');
            $data['image'] = '/storage/'.$path;
        }
        unset($data['image_file']);

        $product->update($data);
        $this->syncVariants($request, $product);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $hasOrders = $product->variants()->whereHas('orderItems')->exists();
        if ($hasOrders) {
            return back()->with('error', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi pemesanan. Ubah status menjadi archived.');
        }

        if ($product->image && str_starts_with($product->image, '/storage/products/')) {
            $oldPath = str_replace('/storage/', '', $product->image);
            Storage::disk('public')->delete($oldPath);
        }

        $product->variants()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        if (! $request->filled('slug') && $request->filled('name')) {
            $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        } elseif ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'slug' => 'required|string|max:255|unique:products,slug'.($product ? ','.$product->id : ''),
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:5120',
            'tag' => 'nullable|string|max:50',
            'kind' => 'nullable|string|max:30',
            'sizes' => 'nullable|array',
            'sizes.*' => 'nullable|string|max:30',
            'variant_prices' => 'nullable|array',
            'variant_prices.*' => 'nullable|numeric|min:0',
            'variant_stocks' => 'nullable|array',
            'variant_stocks.*' => 'nullable|integer|min:0',
            'weight_gram' => 'nullable|integer|min:1|max:100000',
        ]);

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

        $candidates = $product->variants()->whereNotIn('id', $keep ?: [0])->get();
        foreach ($candidates as $candidate) {
            if ($candidate->orderItems()->exists()) {
                $candidate->update(['stock' => 0]);
            } else {
                $candidate->cartItems()->delete();
                $candidate->delete();
            }
        }

        $totalVariantStock = (int) $product->variants()->sum('stock');
        $product->update(['stock' => $totalVariantStock]);
    }
}

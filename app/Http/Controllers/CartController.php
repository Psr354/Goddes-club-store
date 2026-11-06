<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartController extends Controller
{
    /**
     * The current cart contents, ready for rendering.
     */
    public function index(Request $request): JsonResponse
    {
        $items = $this->items($request);

        return response()->json([
            'cart' => $items->values(),
            'cart_count' => (int) $items->sum('quantity'),
            'subtotal' => round((float) $items->sum('line_total'), 2),
        ]);
    }

    /**
     * Add a product (optionally with a chosen size) to the cart.
     */
    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'nullable|string|max:20',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $product = Product::with('variants')->findOrFail($data['product_id']);
        $variant = $this->resolveVariant($product, $data['size'] ?? null);

        if (! $variant) {
            return response()->json([
                'success' => false,
                'message' => 'Ukuran yang dipilih tidak tersedia.',
            ], 422);
        }

        $quantity = (int) ($data['quantity'] ?? 1);

        if ($variant->availableStock() < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Stok untuk ukuran '.$variant->size().' tidak mencukupi.',
            ], 422);
        }

        $this->put($request, $variant->id, $quantity);

        return $this->respond($request, [
            'success' => true,
            'message' => $product->name.' masuk ke tas kamu.',
        ]);
    }

    /**
     * Update the quantity of a cart line. A quantity of 0 removes the line.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $quantity = (int) $data['quantity'];

        if ($quantity === 0) {
            $this->forget($request, (int) $data['variant_id']);
        } else {
            $this->put($request, (int) $data['variant_id'], $quantity);
        }

        return $this->respond($request, ['success' => true]);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
        ]);

        $this->forget($request, (int) $data['variant_id']);

        return $this->respond($request, ['success' => true]);
    }

    /**
     * Validate a promo code against the coupons table.
     */
    public function promo(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('code', '')));

        if ($code === '') {
            return response()->json(['success' => false, 'message' => 'Masukkan kode promo dulu.'], 422);
        }

        $coupon = Coupon::where('code', $code)->first();
        $subtotal = (float) $this->items($request)->sum('line_total');

        if (! $coupon || ! $coupon->isValid($subtotal)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $coupon->type === 'percentage'
                ? $coupon->value.'% discount applied'
                : '$'.$coupon->value.' discount applied',
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'discount' => round($coupon->calculateDiscount($subtotal), 2),
        ]);
    }

    /**
     * The cart lines of the current visitor (session for guests, database for members).
     */
    protected function items(Request $request): Collection
    {
        $rows = $request->user()
            ? $this->databaseItems($request)
            : $this->sessionItems($request);

        return $rows->map(function (array $row) {
            $variant = $row['variant'];
            $product = $variant->product;

            return [
                'variant_id' => $variant->id,
                'product_id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'image' => $product->image,
                'type' => $product->category?->name,
                'price' => (float) $variant->price,
                'size' => $variant->size(),
                'quantity' => (int) $row['quantity'],
                'line_total' => round((float) $variant->price * (int) $row['quantity'], 2),
            ];
        })->values();
    }

    protected function databaseItems(Request $request): Collection
    {
        $cart = $this->cart($request);

        if (! $cart) {
            return collect();
        }

        return $cart->items()
            ->with('variant.product.category')
            ->get()
            ->map(fn (CartItem $item) => [
                'variant' => $item->variant,
                'quantity' => $item->quantity,
            ])
            ->filter(fn (array $row) => $row['variant'] !== null)
            ->values();
    }

    protected function sessionItems(Request $request): Collection
    {
        $rows = $request->session()->get($this->sessionKey($request), []);

        return collect($rows)->map(function (array $row) {
            $variant = ProductVariant::with('product.category')->find($row['product_variant_id']);

            return [
                'variant' => $variant,
                'quantity' => (int) $row['quantity'],
            ];
        })->filter(fn (array $row) => $row['variant'] !== null)->values();
    }

    /**
     * Add or increase a line in the cart.
     */
    protected function put(Request $request, int $variantId, int $quantity): void
    {
        if ($request->user()) {
            $cart = $this->cart($request, true);
            $item = $cart->items()->firstOrNew(['product_variant_id' => $variantId]);
            $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
            $item->save();

            return;
        }

        $rows = $request->session()->get($this->sessionKey($request), []);

        foreach ($rows as $index => $row) {
            if ((int) $row['product_variant_id'] === $variantId) {
                $rows[$index]['quantity'] = (int) $row['quantity'] + $quantity;
                $request->session()->put($this->sessionKey($request), $rows);

                return;
            }
        }

        $rows[] = ['product_variant_id' => $variantId, 'quantity' => $quantity];
        $request->session()->put($this->sessionKey($request), $rows);
    }

    protected function forget(Request $request, int $variantId): void
    {
        if ($request->user()) {
            $this->cart($request)?->items()->where('product_variant_id', $variantId)->delete();

            return;
        }

        $rows = collect($request->session()->get($this->sessionKey($request), []))
            ->reject(fn (array $row) => (int) $row['product_variant_id'] === $variantId)
            ->values()
            ->all();

        $request->session()->put($this->sessionKey($request), $rows);
    }

    /**
     * The database cart of the signed in member.
     */
    protected function cart(Request $request, bool $create = false): ?Cart
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $cart = $user->carts()->latest('id')->first();

        if (! $cart && $create) {
            $cart = Cart::create(['user_id' => $user->id]);
        }

        return $cart;
    }

    /**
     * Pick the variant matching the requested size, falling back to the first one.
     */
    protected function resolveVariant(Product $product, ?string $size): ?ProductVariant
    {
        if ($size) {
            $variant = $product->variants->first(
                fn (ProductVariant $variant) => strcasecmp($variant->size(), $size) === 0
            );

            if ($variant) {
                return $variant;
            }
        }

        return $product->variants->first();
    }

    protected function sessionKey(Request $request): string
    {
        return 'cart:'.$request->session()->getId();
    }

    /**
     * Standard response payload with the refreshed cart state attached.
     */
    protected function respond(Request $request, array $payload = []): JsonResponse
    {
        $items = $this->items($request);

        return response()->json(array_merge($payload, [
            'cart' => $items->values(),
            'cart_count' => (int) $items->sum('quantity'),
            'subtotal' => round((float) $items->sum('line_total'), 2),
        ]));
    }
}

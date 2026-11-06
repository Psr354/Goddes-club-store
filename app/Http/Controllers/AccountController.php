<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\UserAddress;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Show the user account / My Club dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // If not logged in, redirect to login
        if (! $user) {
            return redirect()->route('login');
        }

        // If user is admin, redirect to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Fetch real orders from database if available
        $orders = Order::where('user_id', $user->id)
            ->with(['items.product', 'items.variant', 'address', 'trackings', 'payment'])
            ->latest()
            ->get();

        // Fetch addresses
        $addresses = UserAddress::where('user_id', $user->id)->latest('is_primary')->get();

        // Fetch wishlists
        $wishlists = $user->wishlists()->with('product.variants')->get();

        return view('account', [
            'user' => $user,
            'orders' => $orders,
            'addresses' => $addresses,
            'wishlists' => $wishlists,
        ]);
    }

    /**
     * Update user profile settings.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:120|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update($validated);

        return back()->with('account_notice', 'Profile updated successfully for this session.');
    }

    /**
     * Save a new delivery address for the user.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'full_address' => 'required|string|max:500',
            'postal_code' => 'required|string|max:10',
            'city_id' => 'nullable|integer',
            'is_primary' => 'nullable|boolean',
        ]);

        $isPrimary = $request->boolean('is_primary') || ! $user->addresses()->exists();

        if ($isPrimary) {
            $user->addresses()->update(['is_primary' => false]);
        }

        $user->addresses()->create([
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'full_address' => $validated['full_address'],
            'postal_code' => $validated['postal_code'],
            'city_id' => $validated['city_id'] ?? 0,
            'district_id' => 0,
            'is_primary' => $isPrimary,
        ]);

        return back()->with('account_notice', 'Alamat pengiriman baru berhasil disimpan.');
    }

    /**
     * Delete an address belonging to the user.
     */
    public function destroyAddress(UserAddress $address): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || $address->user_id !== $user->id) {
            abort(403);
        }

        $address->delete();

        return back()->with('account_notice', 'Alamat pengiriman berhasil dihapus.');
    }

    /**
     * Toggle a product in user's wishlist (REST API).
     */
    public function toggleWishlist(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masuk untuk menyimpan wishlist.',
            ], 401);
        }

        $productId = $request->input('product_id');
        $product = Product::find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $status = 'removed';
            $message = "'{$product->name}' dihapus dari wishlist.";
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            $status = 'added';
            $message = "'{$product->name}' disimpan ke wishlist.";
        }

        $totalWishlist = Wishlist::where('user_id', $user->id)->count();

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $message,
            'count' => $totalWishlist,
        ]);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TrackController;
use App\Services\ShippingCalculator;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $categories = Category::with(['products' => fn ($query) => $query->active()->with('variants')])->get();

    return view('store', [
        'categories' => $categories,
    ]);
})->name('home');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/track', [TrackController::class, 'show'])->name('track');

/*
|--------------------------------------------------------------------------
| Auth (session-based, tanpa package tambahan)
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| User Account / My Club Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
Route::post('/account/address', [AccountController::class, 'storeAddress'])->name('account.address.store');
Route::delete('/account/address/{address}', [AccountController::class, 'destroyAddress'])->name('account.address.destroy');

/*
|--------------------------------------------------------------------------
| Cart, Wishlist, Shipping, Payment & Checkout API
|--------------------------------------------------------------------------
*/

Route::get('/api/cart', [CartController::class, 'index']);
Route::post('/api/cart', [CartController::class, 'add']);
Route::post('/api/cart/update', [CartController::class, 'update']);
Route::post('/api/cart/remove', [CartController::class, 'remove']);
Route::post('/api/cart/promo', [CartController::class, 'promo']);

Route::post('/api/wishlist/toggle', [AccountController::class, 'toggleWishlist']);

Route::get('/api/shipping/calculate', function (\Illuminate\Http\Request $request) {
    $postal = $request->input('postal', '12160');
    $weight = (int) $request->input('weight', 300);
    $courier = $request->input('courier', 'JNE');

    return response()->json([
        'success' => true,
        'calculated' => ShippingCalculator::calculate($postal, $weight, $courier),
        'available' => ShippingCalculator::availableOptions($postal, $weight),
    ]);
});

Route::post('/api/checkout', [CheckoutController::class, 'process']);
Route::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');
Route::post('/api/payment/simulate', [PaymentWebhookController::class, 'simulate'])->name('payment.simulate');
Route::post('/api/track', [TrackController::class, 'api']);

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', AdminProductController::class)->except('show');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

    Route::resource('coupons', AdminCouponController::class)->except('show');
});

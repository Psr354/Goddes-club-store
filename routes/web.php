<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TrackController;
use App\Models\Category;
use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:3,1');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| User Account / My Club Dashboard
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::post('/account/address', [AccountController::class, 'storeAddress'])->name('account.address.store');
    Route::delete('/account/address/{address}', [AccountController::class, 'destroyAddress'])->name('account.address.destroy');
});

/*
|--------------------------------------------------------------------------
| Cart, Wishlist, Shipping, Payment & Checkout API
|--------------------------------------------------------------------------
*/

Route::get('/api/cart', [CartController::class, 'index']);
Route::post('/api/cart', [CartController::class, 'add'])->middleware('throttle:60,1');
Route::post('/api/cart/update', [CartController::class, 'update'])->middleware('throttle:60,1');
Route::post('/api/cart/remove', [CartController::class, 'remove'])->middleware('throttle:60,1');
Route::post('/api/cart/promo', [CartController::class, 'promo'])->middleware('throttle:10,1');

Route::post('/api/wishlist/toggle', [AccountController::class, 'toggleWishlist'])->middleware('throttle:30,1');

Route::get('/api/shipping/calculate', function (Request $request) {
    $postal = $request->input('postal', '12160');
    $weight = (int) $request->input('weight', 300);
    $courier = $request->input('courier', 'JNE');

    return response()->json([
        'success' => true,
        'calculated' => ShippingCalculator::calculate($postal, $weight, $courier),
        'available' => ShippingCalculator::availableOptions($postal, $weight),
    ]);
})->middleware('throttle:30,1');

Route::post('/api/checkout', [CheckoutController::class, 'process'])->middleware('throttle:5,1');
Route::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');

if (app()->environment('local', 'testing')) {
    Route::post('/api/payment/simulate', [PaymentWebhookController::class, 'simulate'])->name('payment.simulate');
}

Route::post('/api/track', [TrackController::class, 'api'])->middleware('throttle:30,1');

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

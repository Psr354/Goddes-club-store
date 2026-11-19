<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $revenueStatuses = ['paid', 'processing', 'shipped', 'completed'];

        $rawCounts = Order::toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = collect(['pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'])
            ->mapWithKeys(fn (string $status) => [$status => (int) ($rawCounts[$status] ?? 0)]);

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => (float) Order::whereIn('status', $revenueStatuses)->sum('grand_total'),
                'orders' => (int) $rawCounts->sum(),
                'pending' => (int) ($statusCounts['pending_payment'] ?? 0),
                'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
                'products' => Product::count(),
                'lowStock' => Product::where('stock', '<=', 5)->count(),
                'activeCoupons' => Coupon::where('is_active', true)->count(),
            ],
            'recentOrders' => Order::with('user')->latest('id')->limit(8)->get(),
            'statusCounts' => $statusCounts,
            'topProducts' => Product::withSum('orderItems', 'quantity')
                ->orderByDesc('order_items_sum_quantity')
                ->limit(5)
                ->get(),
        ]);
    }
}

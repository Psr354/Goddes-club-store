<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public const STATUSES = ['pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];

    public function index(Request $request)
    {
        $orders = Order::with('user')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($q) => $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"))
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.variant', 'user', 'address', 'payment', 'trackings', 'coupon']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
            'courier_code' => 'nullable|string|max:50',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $status = $data['status'];

        $order->update([
            'status' => $status,
            'courier_code' => $data['courier_code'] ?? $order->courier_code,
            'tracking_number' => $data['tracking_number'] ?? $order->tracking_number,
        ]);

        if ($status !== $order->getOriginal('status')) {
            $order->trackings()->create([
                'status' => $status,
                'description' => 'Order status updated to '.str_replace('_', ' ', $status).'.',
                'occurred_at' => now(),
            ]);
        }

        return back()->with('success', 'Order updated.');
    }
}

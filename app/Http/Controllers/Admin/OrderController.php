<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\ShippingDispatched;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public const STATUSES = ['pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];

    public function index(Request $request)
    {
        $orders = Order::with('user')
            ->withSum('items as total_quantity', 'quantity')
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
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $data, $status, $previousStatus) {
            $order->update([
                'status' => $status,
                'courier_code' => $data['courier_code'] ?? $order->courier_code,
                'tracking_number' => $data['tracking_number'] ?? $order->tracking_number,
            ]);

            if ($status !== $previousStatus) {
                if ($status === 'cancelled') {
                    if ($previousStatus === 'pending_payment') {
                        if ($order->coupon_id) {
                            $coupon = $order->coupon()->lockForUpdate()->first();
                            if ($coupon && $coupon->used_count > 0) {
                                $coupon->decrement('used_count');
                            }
                        }
                        foreach ($order->items as $item) {
                            $variant = $item->variant()->lockForUpdate()->first();
                            if ($variant) {
                                $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                            }
                        }
                    } elseif (in_array($previousStatus, ['paid', 'processing'])) {
                        foreach ($order->items as $item) {
                            $variant = $item->variant()->lockForUpdate()->first();
                            if ($variant) {
                                $variant->increment('stock', $item->quantity);
                                if ($variant->product) {
                                    $variant->product->increment('stock', $item->quantity);
                                }
                            }
                        }
                    }
                }

                $order->trackings()->create([
                    'status' => $status,
                    'description' => 'Order status updated to '.str_replace('_', ' ', $status).'.',
                    'occurred_at' => now(),
                ]);

                if ($status === 'shipped' && $order->user) {
                    try {
                        $order->user->notify(new ShippingDispatched($order));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });

        return back()->with('success', 'Order updated.');
    }
}

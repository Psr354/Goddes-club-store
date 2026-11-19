<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Notifications\ShippingDispatched;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public const STATUSES = ['pending_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];

    public const VALID_TRANSITIONS = [
        'pending_payment' => ['paid', 'cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

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

        if ($status !== $previousStatus && ! in_array($status, self::VALID_TRANSITIONS[$previousStatus] ?? [], true)) {
            return back()->with('error', "Perubahan status dari '".str_replace('_', ' ', $previousStatus)."' ke '".str_replace('_', ' ', $status)."' tidak diizinkan.");
        }

        DB::transaction(function () use ($order, $data, $status, $previousStatus) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $lockedOrder->update([
                'status' => $status,
                'courier_code' => $data['courier_code'] ?? $lockedOrder->courier_code,
                'tracking_number' => $data['tracking_number'] ?? $lockedOrder->tracking_number,
            ]);

            if ($status !== $previousStatus) {
                if ($status === 'paid' && $previousStatus === 'pending_payment') {
                    // Update Payment record
                    $payment = $lockedOrder->payment ?? Payment::firstOrNew(['order_id' => $lockedOrder->id]);
                    $payment->status = 'paid';
                    $payment->paid_at = now();
                    $payment->payment_method = $payment->payment_method ?: 'manual_admin';
                    $payment->save();

                    // Deduct physical stock and release reserved stock
                    foreach ($lockedOrder->items as $item) {
                        $variant = ProductVariant::where('id', $item->product_variant_id)
                            ->lockForUpdate()
                            ->first();

                        if ($variant) {
                            $variant->decrement('stock', $item->quantity);
                            $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));

                            if ($variant->product) {
                                $variant->product->decrement('stock', $item->quantity);
                            }
                        }
                    }
                } elseif ($status === 'cancelled') {
                    if ($lockedOrder->coupon_id) {
                        $coupon = $lockedOrder->coupon()->lockForUpdate()->first();
                        if ($coupon && $coupon->used_count > 0) {
                            $coupon->decrement('used_count');
                        }
                    }

                    if ($lockedOrder->payment && $lockedOrder->payment->status !== 'paid') {
                        $lockedOrder->payment->update(['status' => 'failed']);
                    }

                    if ($previousStatus === 'pending_payment') {
                        foreach ($lockedOrder->items as $item) {
                            $variant = ProductVariant::where('id', $item->product_variant_id)
                                ->lockForUpdate()
                                ->first();

                            if ($variant) {
                                $variant->decrement('reserved_stock', min($variant->reserved_stock, $item->quantity));
                            }
                        }
                    } elseif (in_array($previousStatus, ['paid', 'processing', 'shipped'])) {
                        foreach ($lockedOrder->items as $item) {
                            $variant = ProductVariant::where('id', $item->product_variant_id)
                                ->lockForUpdate()
                                ->first();

                            if ($variant) {
                                $variant->increment('stock', $item->quantity);
                                if ($variant->product) {
                                    $variant->product->increment('stock', $item->quantity);
                                }
                            }
                        }
                    }
                }

                $lockedOrder->trackings()->create([
                    'status' => $status,
                    'description' => 'Order status updated to '.str_replace('_', ' ', $status).'.',
                    'occurred_at' => now(),
                ]);

                if ($status === 'shipped' && $lockedOrder->user) {
                    try {
                        $lockedOrder->user->notify(new ShippingDispatched($lockedOrder));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }
        });

        return back()->with('success', 'Order updated.');
    }
}

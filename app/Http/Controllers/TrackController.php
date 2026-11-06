<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * The delivery stages, in order, as shown on the tracking page.
     */
    public const STAGES = [
        ['label' => 'Order paid', 'description' => 'Pembayaran diterima, pesanan sedang kami proses.'],
        ['label' => 'Packing your favorites', 'description' => 'Barangmu sedang dikemas dengan cinta.'],
        ['label' => 'Shipped', 'description' => 'Paketmu sudah dalam perjalanan.'],
        ['label' => 'Out for delivery', 'description' => 'Kurir sedang menuju alamatmu.'],
        ['label' => 'Delivered', 'description' => 'Paket sudah sampai. hope you love it.'],
    ];

    public function show(Request $request)
    {
        $order = null;
        $reference = trim((string) $request->query('order', ''));

        if ($reference !== '') {
            $order = $this->find($reference);
        }

        return view('track', [
            'order' => $order,
            'reference' => $reference,
            'timeline' => $order ? $this->timeline($order) : [],
        ]);
    }

    /**
     * Look up an order by invoice number or by its public reference (GC-1024).
     */
    public function api(Request $request): JsonResponse
    {
        $reference = trim((string) $request->input('order', ''));

        if ($reference === '') {
            return response()->json(['success' => false, 'message' => 'Masukkan nomor pesanan.'], 422);
        }

        $order = $this->find($reference);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'reference' => $order->reference(),
            'invoice_number' => $order->invoice_number,
            'status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'timeline' => $this->timeline($order),
        ]);
    }

    protected function find(string $reference): ?Order
    {
        $reference = strtoupper($reference);

        $order = Order::with(['items', 'trackings', 'address'])
            ->whereRaw('UPPER(invoice_number) = ?', [$reference])
            ->first();

        if ($order) {
            return $order;
        }

        if (preg_match('/^GC-?(\d+)$/', $reference, $matches)) {
            return Order::with(['items', 'trackings', 'address'])->find((int) $matches[1]);
        }

        return null;
    }

    /**
     * Build the timeline shown on the tracking page.
     */
    protected function timeline(Order $order): array
    {
        $current = match ($order->status) {
            'pending_payment' => 0,
            'paid' => 1,
            'processing' => 1,
            'shipped' => 3,
            'completed' => count(self::STAGES),
            'cancelled' => 0,
            default => 0,
        };

        return collect(self::STAGES)->values()->map(function (array $stage, int $index) use ($current, $order) {
            $done = $index < $current;
            $isCurrent = $index === $current && $current < count(self::STAGES);

            $description = $stage['description'];

            if ($order->status === 'cancelled') {
                $description = 'Pesanan dibatalkan.';
            } elseif ($done) {
                $tracking = $order->trackings
                    ->where('status', '!=', 'pending_payment')
                    ->sortByDesc('occurred_at')
                    ->first();

                $description = $tracking?->description ?? 'Completed';
            } elseif ($isCurrent) {
                $description = 'Sedang diproses.';
            } else {
                $description = 'Coming soon';
            }

            return [
                'label' => $stage['label'],
                'description' => $description,
                'done' => $done,
                'current' => $isCurrent,
            ];
        })->all();
    }
}

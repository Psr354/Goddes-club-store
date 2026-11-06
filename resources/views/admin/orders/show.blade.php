@extends('layouts.admin')

@section('title', 'Order '.$order->reference().' — Goddess Club Admin')

@section('content')
<div class="admin-title">
  <div>
    <div class="eyebrow"><span></span>FULFILMENT</div>
    <h1>Order {{ $order->reference() }}</h1>
    <p class="muted">{{ $order->invoice_number }} &middot; placed {{ $order->created_at?->format('d M Y, H:i') }}</p>
  </div>
  <div class="admin-actions">
    <a class="admin-btn ghost" href="{{ route('admin.orders.index') }}">&larr; BACK TO LIST</a>
  </div>
</div>

<div class="admin-grid-2">
  <div class="admin-panel">
    <h2>Items</h2>
    <table class="admin-table admin-order-items">
      <thead>
        <tr>
          <th>Product</th>
          <th>SKU</th>
          <th>Qty</th>
          <th>Price</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->items as $item)
          <tr>
            <td>{{ $item->product_name }}</td>
            <td class="mono">{{ $item->sku }}</td>
            <td class="mono">{{ $item->quantity }}</td>
            <td class="mono">${{ number_format($item->unit_price, 2) }}</td>
            <td class="mono">${{ number_format($item->lineTotal(), 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <dl class="kv" style="margin-top:24px">
      <dt>Subtotal</dt>
      <dd class="mono">${{ number_format($order->subtotal, 2) }}</dd>
      <dt>Discount</dt>
      <dd class="mono">- ${{ number_format($order->discount_amount, 2) }} {{ $order->coupon ? '('.$order->coupon->code.')' : '' }}</dd>
      <dt>Shipping</dt>
      <dd class="mono">${{ number_format($order->shipping_fee, 2) }}</dd>
      <dt>Grand total</dt>
      <dd class="mono"><strong>${{ number_format($order->grand_total, 2) }}</strong></dd>
    </dl>
  </div>

  <div>
    <div class="admin-panel">
      <h2>Update status</h2>
      <form method="POST" action="{{ route('admin.orders.update', $order) }}">
        @csrf
        @method('PATCH')

        <label class="field">STATUS
          <select name="status" required>
            @foreach ($statuses as $status)
              <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ str_replace('_', ' ', $status) }}</option>
            @endforeach
          </select>
        </label>

        <div class="admin-grid-2">
          <label class="field">COURIER
            <input type="text" name="courier_code" value="{{ old('courier_code', $order->courier_code) }}" placeholder="jne / sicepat / spil">
          </label>

          <label class="field">TRACKING NUMBER
            <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="JNE123456789">
          </label>
        </div>

        <button class="admin-btn" type="submit">SAVE ORDER</button>
      </form>
    </div>

    <div class="admin-panel">
      <h2>Customer</h2>
      <dl class="kv">
        <dt>Name</dt>
        <dd>{{ $order->user?->name ?? 'Guest' }}</dd>
        <dt>Email</dt>
        <dd>{{ $order->user?->email ?? '—' }}</dd>
        <dt>Phone</dt>
        <dd>{{ $order->user?->phone ?? '—' }}</dd>
        <dt>Address</dt>
        <dd>{{ $order->address?->full_address ?? '—' }}</dd>
        <dt>Payment</dt>
        <dd>{{ $order->payment?->provider ?? '—' }} @if ($order->payment) ({{ $order->payment->status }}) @endif</dd>
      </dl>
    </div>

    <div class="admin-panel">
      <h2>Timeline</h2>
      <div class="timeline" style="padding:0">
        @forelse ($order->trackings->sortByDesc('occurred_at') as $tracking)
          <div class="done">
            <b>&check;</b>
            <section>
              <h3>{{ str_replace('_', ' ', $tracking->status) }}</h3>
              <p>{{ $tracking->description }}</p>
              <p class="muted">{{ $tracking->occurred_at?->format('d M Y, H:i') }}</p>
            </section>
          </div>
        @empty
          <p class="muted">No tracking events yet.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

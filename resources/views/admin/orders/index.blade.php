@extends('layouts.admin')

@section('title', 'Manajemen Pesanan — Goddess Club Admin')

@section('content')
<div class="admin-heading">
  <div>
    <div class="eyebrow muted">FULFILMENT &amp; LOGISTICS</div>
    <h1>Pesanan <em>pelanggan.</em></h1>
    <p>Pantau pembayaran, kemas pesanan merch, dan perbarui nomor resi pengiriman.</p>
  </div>
  <div class="admin-edition">
    <span>TOTAL ORDER</span><br>
    <b>{{ $orders->total() }}</b> TRANSAKSI
  </div>
</div>

<form class="admin-toolbar" method="GET" action="{{ route('admin.orders.index') }}">
  <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari invoice atau email pelanggan…">
  <select name="status">
    <option value="">Semua status</option>
    @foreach ($statuses as $status)
      <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
    @endforeach
  </select>
  <button class="primary" type="submit" style="padding:10px 18px;font-size:11px;">FILTER</button>
  @if (request()->hasAny(['search', 'status']))
    <a class="admin-link" href="{{ route('admin.orders.index') }}">RESET FILTER ↗</a>
  @endif
</form>

<div class="admin-panel">
  <div class="admin-panel-heading">
    <div>
      <span class="account-kicker">ORDERS LIST</span>
      <h2>Semua pesanan masuk</h2>
    </div>
  </div>

  <div class="admin-table-scroll">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pesanan</th>
          <th>Pelanggan</th>
          <th>Item</th>
          <th>Total</th>
          <th>Status</th>
          <th>No. Resi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($orders as $order)
          <tr>
            <td>
              <strong>#{{ $order->reference() }}</strong>
              <small>{{ $order->created_at->format('d M Y') }}</small>
            </td>
            <td>
              <div>{{ $order->user?->name ?? 'Guest Member' }}</div>
              <small style="color:#8e836e;">{{ $order->user?->email ?? ($order->address?->email ?? '—') }}</small>
            </td>
            <td class="admin-mono">{{ $order->items()->sum('quantity') }} pcs</td>
            <td class="admin-mono">${{ number_format($order->grand_total, 2) }}</td>
            <td>
              <span class="admin-status {{ in_array($order->status, ['completed', 'shipped']) ? 'success' : ($order->status === 'cancelled' ? 'muted' : ($order->status === 'pending_payment' ? 'muted' : '')) }}">
                {{ str_replace('_', ' ', ucfirst($order->status)) }}
              </span>
            </td>
            <td class="admin-mono">{{ $order->tracking_number ?? '—' }}</td>
            <td>
              <a class="admin-link" href="{{ route('admin.orders.show', $order) }}">Kelola &nearr;</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="admin-empty">Tidak ada pesanan yang cocok dengan kriteria filter.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($orders->hasPages())
    <div style="margin-top:16px;">
      {{ $orders->links() }}
    </div>
  @endif
</div>
@endsection

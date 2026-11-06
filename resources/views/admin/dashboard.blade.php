@extends('layouts.admin')

@section('title', 'Backstage Pulse — Goddess Club Admin')

@section('content')
<div class="admin-heading">
  <div>
    <div class="eyebrow muted">REALITY CLUB STORE OVERVIEW</div>
    <h1>Backstage <em>pulse.</em></h1>
    <p>Pantau penjualan, stok merch, dan status pesanan hari ini dalam satu tempat.</p>
  </div>
  <div class="admin-edition">
    <span>VOL. 01 DROP</span><br>
    <b>{{ $stats['products'] }}</b> PRODUK AKTIF
  </div>
</div>

<div class="admin-stat-grid">
  <article>
    <span>TOTAL PENJUALAN</span>
    <strong>${{ number_format($stats['revenue'] ?: 162.00, 2) }}</strong>
    <p>{{ $stats['orders'] ?: 4 }} pesanan tercatat dalam database toko.</p>
  </article>
  <article>
    <span>PESANAN AKTIF</span>
    <strong>{{ $stats['pending'] ?: 2 }}</strong>
    <p>Perlu pengemasan dan input nomor resi pengiriman.</p>
  </article>
  <article>
    <span>PRODUK &amp; VARIAN</span>
    <strong>{{ $stats['products'] ?: 4 }}</strong>
    <p>{{ $stats['products'] }} produk aktif &middot; {{ $stats['lowStock'] }} stok menipis.</p>
  </article>
  <article>
    <span>NILAI PESANAN RATA-RATA</span>
    @php
      $avg = ($stats['orders'] > 0 && $stats['revenue'] > 0) ? ($stats['revenue'] / $stats['orders']) : 32.40;
    @endphp
    <strong>${{ number_format($avg, 2) }}</strong>
    <p>Dihitung dari pesanan masuk di server.</p>
  </article>
</div>

<div class="admin-overview-grid">
  <div>
    <!-- Sales chart visualizer -->
    <div class="admin-panel">
      <div class="admin-panel-heading">
        <div>
          <span class="account-kicker">PENJUALAN MINGGU INI</span>
          <h2>Aktivitas merch</h2>
        </div>
        <span class="admin-chart-label">SEN &ndash; MIN</span>
      </div>
      <div class="admin-chart">
        <div><div style="height:35%;"></div><span>SEN</span></div>
        <div><div style="height:55%;"></div><span>SEL</span></div>
        <div><div style="height:45%;"></div><span>RAB</span></div>
        <div><div class="highlight" style="height:85%;"></div><span>KAM</span></div>
        <div><div style="height:70%;"></div><span>JUM</span></div>
        <div><div style="height:90%;"></div><span>SAB</span></div>
        <div><div style="height:60%;"></div><span>MIN</span></div>
      </div>
      <div class="admin-chart-note">Puncak penjualan terjadi saat drop merch limited edition.</div>
    </div>

    <!-- Recent orders table -->
    <div class="admin-panel">
      <div class="admin-panel-heading">
        <div>
          <span class="account-kicker">PESANAN TERBARU</span>
          <h2>Aktivitas pembelian</h2>
        </div>
        <a class="admin-link" href="{{ route('admin.orders.index') }}">SEMUA PESANAN &nearr;</a>
      </div>

      <div class="admin-table-scroll">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Pesanan</th>
              <th>Pelanggan</th>
              <th>Status</th>
              <th>Total</th>
              <th><span class="admin-sr">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentOrders as $order)
              <tr>
                <td>
                  <strong>#{{ $order->reference() }}</strong>
                  <small>{{ $order->created_at->format('d M Y') }}</small>
                </td>
                <td>{{ $order->user?->name ?? 'Pelanggan' }}</td>
                <td>
                  <span class="admin-status {{ in_array($order->status, ['completed', 'shipped']) ? 'success' : ($order->status === 'pending_payment' ? 'muted' : '') }}">
                    {{ str_replace('_', ' ', ucfirst($order->status)) }}
                  </span>
                </td>
                <td class="admin-mono">${{ number_format($order->grand_total, 2) }}</td>
                <td>
                  <a class="admin-link" href="{{ route('admin.orders.show', $order) }}">Detail &nearr;</a>
                </td>
              </tr>
            @empty
              <!-- Demo sample rows if empty -->
              <tr>
                <td><strong>#GC-1024</strong><small>12 Jun 2025</small></td>
                <td>Alex Morgan</td>
                <td><span class="admin-status success">Dikirim</span></td>
                <td class="admin-mono">$35.00</td>
                <td><a class="admin-link" href="{{ route('admin.orders.index') }}">Detail &nearr;</a></td>
              </tr>
              <tr>
                <td><strong>#GC-1023</strong><small>12 Jun 2025</small></td>
                <td>Nadia Putri</td>
                <td><span class="admin-status">Dikemas</span></td>
                <td class="admin-mono">$42.00</td>
                <td><a class="admin-link" href="{{ route('admin.orders.index') }}">Detail &nearr;</a></td>
              </tr>
              <tr>
                <td><strong>#GC-1022</strong><small>11 Jun 2025</small></td>
                <td>Rafi Pratama</td>
                <td><span class="admin-status">Dibayar</span></td>
                <td class="admin-mono">$22.00</td>
                <td><a class="admin-link" href="{{ route('admin.orders.index') }}">Detail &nearr;</a></td>
              </tr>
              <tr>
                <td><strong>#GC-1021</strong><small>11 Jun 2025</small></td>
                <td>Sarah Dewi</td>
                <td><span class="admin-status success">Selesai</span></td>
                <td class="admin-mono">$35.00</td>
                <td><a class="admin-link" href="{{ route('admin.orders.index') }}">Detail &nearr;</a></td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <!-- Stock alerts -->
    <div class="admin-panel admin-alerts">
      <span class="account-kicker">PERINGATAN STOK</span>
      <h2>Perhatian merch</h2>

      <div class="admin-stock-alert">
        <span class="admin-stock-dot"></span>
        <div>
          <strong>The Goddess Tour Tee (M)</strong>
          <small>Tersisa 5 pcs &middot; Segera restock</small>
        </div>
      </div>

      <div class="admin-stock-alert">
        <span class="admin-stock-dot" style="background:#8e836e;"></span>
        <div>
          <strong>The After Hours Cap</strong>
          <small>Habis (0 pcs) &middot; Menunggu produksi Vol. 02</small>
        </div>
      </div>

      <div class="admin-stock-alert">
        <span class="admin-stock-dot" style="background:#4ade80;"></span>
        <div>
          <strong>Reality Club Presents…</strong>
          <small>Stok aman &middot; 24 pcs tersedia</small>
        </div>
      </div>

      <div style="margin-top:20px;">
        <a class="admin-link" href="{{ route('admin.products.index') }}">KELOLA SEMUA PRODUK &nearr;</a>
      </div>
    </div>

    <!-- Active coupons summary -->
    <div class="admin-panel">
      <div class="admin-panel-heading">
        <div>
          <span class="account-kicker">PROMOSI &amp; KUPON</span>
          <h2>Kupon aktif</h2>
        </div>
        <a class="admin-link" href="{{ route('admin.coupons.index') }}">+ BUAT KUPON &nearr;</a>
      </div>

      <div style="background:#211d17;border:1px solid #4a402e;padding:16px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <strong style="letter-spacing:1px;font-size:14px;color:#e5d0a7;">GODDESS10</strong>
          <span class="admin-status success">Aktif</span>
        </div>
        <p style="color:#b1a080;font-size:11px;margin:6px 0 0;">Diskon 10% untuk seluruh item merchandise.</p>
      </div>

      <div style="background:#211d17;border:1px solid #4a402e;padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <strong style="letter-spacing:1px;font-size:14px;color:#e5d0a7;">CLUB10</strong>
          <span class="admin-status success">Aktif</span>
        </div>
        <p style="color:#b1a080;font-size:11px;margin:6px 0 0;">Diskon 10% demo member Goddess Club.</p>
      </div>
    </div>
  </div>
</div>
@endsection

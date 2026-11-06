@extends('layouts.admin')

@section('title', 'Kupon Promo — Goddess Club Admin')

@section('content')
<div class="admin-heading">
  <div>
    <div class="eyebrow muted">PROMOTIONS &amp; DISCOUNTS</div>
    <h1>Kupon <em>promo.</em></h1>
    <p>Kelola potongan harga spesial untuk komunitas dan anggota Goddess Club.</p>
  </div>
  <div class="admin-edition">
    <a class="admin-btn" href="{{ route('admin.coupons.create') }}" style="background:#e5a93b;color:#121212;padding:10px 18px;text-decoration:none;font-size:11px;font-weight:600;">+ BUAT KUPON BARU</a>
  </div>
</div>

<div class="admin-coupon-grid" style="margin-bottom: 32px;">
  @forelse ($coupons as $coupon)
    <article class="admin-coupon">
      <span>FOR THE ONES WHO FEEL IT ALL</span>
      <div>
        <h2>
          @if ($coupon->type === 'percentage')
            {{ rtrim(rtrim($coupon->value, '0'), '.') }}% <em>off.</em>
          @else
            ${{ number_format($coupon->value, 0) }} <em>off.</em>
          @endif
        </h2>
        <span class="admin-status {{ $coupon->is_active ? 'success' : 'muted' }}">
          {{ $coupon->is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
      </div>
      <strong>{{ $coupon->code }}</strong>
      <p>
        @if ($coupon->min_order_amount > 0)
          Min. pembelian ${{ number_format($coupon->min_order_amount, 2) }} &middot;
        @endif
        Digunakan {{ $coupon->used_count }} kali{{ $coupon->usage_limit ? ' dari max '.$coupon->usage_limit : '' }}.
      </p>
      <div style="display:flex;gap:12px;align-items:center;">
        <a class="admin-link" href="{{ route('admin.coupons.edit', $coupon) }}">EDIT KUPON &nearr;</a>
        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Hapus kupon {{ $coupon->code }}?');" style="display:inline;margin:0;">
          @csrf
          @method('DELETE')
          <button type="submit" class="admin-link" style="background:none;border:none;color:#f87171;cursor:pointer;">HAPUS &nearr;</button>
        </form>
      </div>
    </article>
  @empty
    <article class="admin-coupon">
      <span>FOR THE ONES WHO FEEL IT ALL</span>
      <div>
        <h2>10% <em>off.</em></h2>
        <span class="admin-status success">Aktif</span>
      </div>
      <strong>GODDESS10</strong>
      <p>Kupon promo contoh &middot; Diskon 10% untuk merch release.</p>
      <a class="admin-link" href="{{ route('admin.coupons.create') }}">BUAT KUPON PERTAMA &nearr;</a>
    </article>
  @endforelse
</div>

<div class="admin-panel">
  <div class="admin-panel-heading">
    <div>
      <span class="account-kicker">TABEL DATA</span>
      <h2>Daftar semua kupon</h2>
    </div>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Kode</th>
        <th>Tipe</th>
        <th>Nilai</th>
        <th>Min Order</th>
        <th>Terpakai</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($coupons as $coupon)
        <tr>
          <td class="admin-mono"><a href="{{ route('admin.coupons.edit', $coupon) }}" style="color:#f4ebd0;">{{ $coupon->code }}</a></td>
          <td>{{ ucfirst($coupon->type) }}</td>
          <td class="admin-mono">{{ $coupon->type === 'percentage' ? rtrim(rtrim($coupon->value, '0'), '.').'%' : '$'.number_format($coupon->value, 2) }}</td>
          <td class="admin-mono">${{ number_format($coupon->min_order_amount, 2) }}</td>
          <td class="admin-mono">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
          <td>
            <span class="admin-status {{ $coupon->is_active ? 'success' : 'muted' }}">{{ $coupon->is_active ? 'Aktif' : 'Nonaktif' }}</span>
          </td>
          <td>
            <a class="admin-link" href="{{ route('admin.coupons.edit', $coupon) }}">Edit &nearr;</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="admin-empty">Belum ada kupon yang dibuat.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if ($coupons->hasPages())
    <div style="margin-top:16px;">
      {{ $coupons->links() }}
    </div>
  @endif
</div>
@endsection

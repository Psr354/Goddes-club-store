@extends('layouts.admin')

@section('title', 'Produk & Stok — Goddess Club Admin')

@section('content')
<div class="admin-heading">
  <div>
    <div class="eyebrow muted">CATALOGUE &amp; INVENTORY</div>
    <h1>Produk &amp; <em>stok.</em></h1>
    <p>Kelola katalog merchandise, varian ukuran, dan ketersediaan stok fisik.</p>
  </div>
  <div class="admin-edition">
    <a class="admin-btn" href="{{ route('admin.products.create') }}" style="background:#e5a93b;color:#121212;padding:10px 18px;text-decoration:none;font-size:11px;font-weight:600;">+ TAMBAH PRODUK</a>
  </div>
</div>

<form class="admin-toolbar" method="GET" action="{{ route('admin.products.index') }}">
  <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau slug produk…">
  <select name="category">
    <option value="">Semua kategori</option>
    @foreach ($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  <button class="primary" type="submit" style="padding:10px 18px;font-size:11px;">FILTER</button>
  @if (request()->hasAny(['search', 'category']))
    <a class="admin-link" href="{{ route('admin.products.index') }}">RESET FILTER ↗</a>
  @endif
</form>

<div class="admin-panel">
  <div class="admin-panel-heading">
    <div>
      <span class="account-kicker">INVENTORY TABLE</span>
      <h2>Semua rilisan</h2>
    </div>
    <span class="admin-chart-label">{{ $products->total() }} ITEM</span>
  </div>

  <div class="admin-table-scroll">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Produk</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($products as $product)
          <tr>
            <td>
              <strong style="font-size:13px;color:#f4ebd0;">{{ $product->name }}</strong>
              <div style="color:#8e836e;font-size:11px;margin-top:2px;">/{{ $product->slug }}</div>
            </td>
            <td>{{ $product->category?->name ?? '—' }}</td>
            <td class="admin-mono">${{ number_format($product->price, 2) }}</td>
            <td>
              <span class="admin-mono {{ $product->stock <= 5 ? ($product->stock == 0 ? 'muted' : 'highlight') : '' }}">
                {{ $product->stock }} pcs
              </span>
            </td>
            <td>
              <span class="admin-status {{ $product->status === 'active' ? 'success' : 'muted' }}">
                {{ $product->status === 'active' ? 'Aktif' : 'Draft' }}
              </span>
            </td>
            <td>
              <div style="display:flex;gap:12px;align-items:center;">
                <a class="admin-link" href="{{ route('admin.products.edit', $product) }}">Edit &nearr;</a>
                <a class="admin-link" href="{{ route('product.show', $product->slug) }}" target="_blank" rel="noopener">Lihat &nearr;</a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk {{ $product->name }}?');" style="display:inline;margin:0;">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="admin-link" style="background:none;border:none;color:#f87171;cursor:pointer;">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="admin-empty">Tidak ada produk yang cocok dengan pencarian.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($products->hasPages())
    <div style="margin-top:16px;">
      {{ $products->links() }}
    </div>
  @endif
</div>
@endsection

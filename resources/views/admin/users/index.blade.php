@extends('layouts.admin')

@section('title', 'Kelola Pengguna — Goddess Club Admin')

@section('content')
<div class="admin-heading">
  <div>
    <div class="eyebrow muted">COMMUNITY &amp; ACCESS</div>
    <h1>Pengguna &amp; <em>anggota.</em></h1>
    <p>Kelola akses pengguna terdaftar dan otorisasi peran (Admin / Customer).</p>
  </div>
  <div class="admin-edition">
    <span>TOTAL MEMBER</span><br>
    <b>{{ $users->total() }}</b> PENGGUNA
  </div>
</div>

<form class="admin-toolbar" method="GET" action="{{ route('admin.users.index') }}">
  <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email pengguna…">
  <select name="role">
    <option value="">Semua peran (Role)</option>
    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
    <option value="customer" @selected(request('role') === 'customer')>Customer</option>
  </select>
  <button class="primary" type="submit" style="padding:10px 18px;font-size:11px;">FILTER</button>
  @if (request()->hasAny(['search', 'role']))
    <a class="admin-link" href="{{ route('admin.users.index') }}">RESET FILTER ↗</a>
  @endif
</form>

<div class="admin-panel">
  <div class="admin-panel-heading">
    <div>
      <span class="account-kicker">MEMBER DIRECTORY</span>
      <h2>Daftar pengguna terdaftar</h2>
    </div>
  </div>

  <div class="admin-table-scroll">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Pengguna</th>
          <th>Peran (Role)</th>
          <th>Pesanan</th>
          <th>Bergabung</th>
          <th>Ubah Peran</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $user)
          <tr>
            <td>
              <strong style="color:#f4ebd0;">{{ $user->name }}</strong>
              <div style="color:#8e836e;font-size:11px;">{{ $user->email }}</div>
            </td>
            <td>
              <span class="admin-status {{ $user->isAdmin() ? 'success' : '' }}">
                {{ $user->isAdmin() ? 'Admin' : 'Customer' }}
              </span>
            </td>
            <td class="admin-mono">{{ $user->orders_count }} pesanan</td>
            <td class="admin-mono">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
            <td>
              <form method="POST" action="{{ route('admin.users.role', $user) }}" style="display:flex;gap:8px;align-items:center;margin:0;">
                @csrf
                @method('PATCH')
                <select name="role" style="min-height:32px;padding:4px 8px;background:#171511;border:1px solid #574930;color:#f4ebd0;font-size:11px;">
                  <option value="customer" @selected($user->role === 'customer')>Customer</option>
                  <option value="admin" @selected($user->role === 'admin')>Admin</option>
                </select>
                <button type="submit" class="admin-link" style="background:none;border:none;cursor:pointer;">SIMPAN &nearr;</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="admin-empty">Tidak ada pengguna yang cocok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($users->hasPages())
    <div style="margin-top:16px;">
      {{ $users->links() }}
    </div>
  @endif
</div>
@endsection

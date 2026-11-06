<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Backstage Admin — Goddess Club Store')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('goddess.css') }}?v=2">
  <style>
    body {
      background: #151411;
      color: #f4ebd0;
      min-height: 100vh;
      margin: 0;
      font-family: 'DM Sans', sans-serif;
    }
    .admin-sidebar nav a, .admin-sidebar nav button {
      text-align: left;
      color: #a99e88;
      border-left: 2px solid transparent;
      align-items: center;
      gap: 14px;
      min-height: 46px;
      padding: 0 14px;
      font-size: 12px;
      display: flex;
      text-decoration: none;
      background: none;
      width: 100%;
      cursor: pointer;
      font-family: inherit;
    }
    .admin-sidebar nav a>span, .admin-sidebar nav button>span {
      color: #786d56;
      font: 9px monospace;
    }
    .admin-sidebar nav a>b, .admin-sidebar nav button>b {
      margin-left: auto;
      font-weight: 400;
    }
    .admin-sidebar nav a.active, .admin-sidebar nav button.active {
      color: #f4ebd0;
      background: #29241b;
      border-left-color: #d92323;
    }
    .admin-sidebar nav a.active>span, .admin-sidebar nav button.active>span {
      color: #e5a93b;
    }
    .admin-status.success {
      color: #4ade80;
    }
    .admin-status.muted {
      color: #9c917d;
    }
    .admin-topbar form button {
      background: none;
      border: none;
      color: #cbb58b;
      font-size: 10px;
      cursor: pointer;
      padding: 0;
      font-family: inherit;
    }
  </style>
</head>
<body>
  <div class="admin-shell">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
      <a class="brand" href="{{ route('home') }}">goddess <span>CLUB STORE</span></a>
      <div class="admin-sidebar-label">BACKSTAGE / ADMIN DEMO</div>

      <nav aria-label="Navigasi admin">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
          <span>01</span>Overview <b>&nearr;</b>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
          <span>02</span>Pesanan <b>&nearr;</b>
        </a>
        <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
          <span>03</span>Produk &amp; stok <b>&nearr;</b>
        </a>
        <a href="{{ route('admin.coupons.index') }}" class="{{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
          <span>04</span>Kupon <b>&nearr;</b>
        </a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
          <span>05</span>Pengguna <b>&nearr;</b>
        </a>
      </nav>

      <div class="admin-sidebar-bottom">
        <p>The music stays.<br>So do we.</p>
        <button type="button" onclick="window.location.href='{{ route('home') }}'">EXIT ADMIN / BACK TO STORE &nearr;</button>
      </div>

      <div class="admin-identity">
        <span>A</span>
        <div>
          <strong>Store Administrator</strong>
          <small>ADMIN DEMO ACCOUNT</small>
        </div>
      </div>
    </aside>

    <!-- Admin Main -->
    <main class="admin-main">
      <div class="admin-topbar">
        <div>
          <span>BACKSTAGE PANEL</span>
          <b>{{ date('d M Y') }} &middot; TOKO AKTIF</b>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
          <button type="button" onclick="window.location.href='{{ route('home') }}'">LIHAT TOKO &nearr;</button>
          <form method="POST" action="{{ route('logout') }}" style="display:inline;margin:0;">
            @csrf
            <button type="submit">KELUAR &nearr;</button>
          </form>
        </div>
      </div>

      <div class="admin-body">
        <div class="admin-preview">
          <strong>DEMO ADMIN DASHBOARD</strong>
          <span>Role diverifikasi di server Laravel (Akun Admin).</span>
        </div>

        @if (session('success'))
          <div class="admin-note success" style="margin-top:16px;">{{ session('success') }}</div>
        @endif

        @if (session('error'))
          <div class="admin-note error" style="margin-top:16px;">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
          <div class="admin-note error" style="margin-top:16px;">
            <strong>Periksa kembali isianmu:</strong>
            <ul style="margin:8px 0 0 16px">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @yield('content')

        <div class="admin-footer">
          <span>GODDESS CLUB STORE / BACKSTAGE</span>
          <span>FOR THE MUSIC. ALWAYS.</span>
        </div>
      </div>
    </main>
  </div>

  <script src="{{ asset('goddess.js') }}?v=2"></script>
  @stack('scripts')
</body>
</html>

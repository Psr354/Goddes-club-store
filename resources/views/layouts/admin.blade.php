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

    /* Admin Title & Actions */
    .admin-title {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      margin: 28px 0 24px;
      flex-wrap: wrap;
    }
    .admin-title h1 {
      font-size: 32px;
      margin: 8px 0 0;
      font-family: 'Playfair Display', serif;
      letter-spacing: -0.5px;
      color: #f4ebd0;
    }
    .admin-title p.muted {
      color: #a79a82;
      font-size: 11px;
      margin: 6px 0 0;
    }
    .admin-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* Admin Buttons */
    .admin-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 11px 22px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      text-decoration: none;
      border: 1px solid transparent;
      cursor: pointer;
      background: #d92323;
      color: #ffffff;
      border-radius: 2px;
      transition: background 0.2s, border-color 0.2s, color 0.2s;
      font-family: inherit;
    }
    .admin-btn:hover {
      background: #b51a1a;
      color: #ffffff;
    }
    .admin-btn.gold {
      background: #e5a93b;
      color: #121212;
    }
    .admin-btn.gold:hover {
      background: #cca92b;
    }
    .admin-btn.ghost {
      background: transparent;
      border: 1px solid #574930;
      color: #cbb58b;
    }
    .admin-btn.ghost:hover {
      border-color: #e5a93b;
      color: #f4ebd0;
      background: rgba(229, 169, 59, 0.06);
    }
    .admin-btn.small {
      padding: 7px 14px;
      font-size: 10px;
      letter-spacing: 0.8px;
    }
    .admin-btn.danger {
      background: #6e1c1c;
      color: #fca5a5;
      border: 1px solid #991b1b;
    }
    .admin-btn.danger:hover {
      background: #991b1b;
      color: #fff;
    }

    /* Admin Panels */
    .admin-panel {
      background: #191713;
      border: 1px solid #463d2d;
      padding: 24px;
      margin-bottom: 24px;
      border-radius: 2px;
    }
    .admin-panel h2 {
      font-size: 20px;
      font-family: 'Playfair Display', serif;
      color: #f4ebd0;
      margin: 0 0 16px;
      padding-bottom: 12px;
      border-bottom: 1px solid #332d22;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* Form Fields */
    form.admin-form {
      display: block;
      width: 100%;
    }
    .field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 18px;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.8px;
      color: #cbb58b;
      text-transform: uppercase;
    }
    .field .hint {
      font-size: 10px;
      font-weight: 400;
      text-transform: none;
      letter-spacing: normal;
      color: #8e836e;
      margin-top: 2px;
    }
    .field input,
    .field select,
    .field textarea,
    .admin-input {
      background: #14120f !important;
      border: 1px solid #4a402e !important;
      border-radius: 2px !important;
      color: #f4ebd0 !important;
      padding: 11px 14px !important;
      font-size: 13px !important;
      font-family: inherit !important;
      width: 100% !important;
      box-sizing: border-box !important;
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    .field input:focus,
    .field select:focus,
    .field textarea:focus,
    .admin-input:focus {
      border-color: #e5a93b !important;
      box-shadow: 0 0 0 1px #e5a93b !important;
      outline: none !important;
    }
    .field input::placeholder,
    .field textarea::placeholder {
      color: #635744;
    }
    .field select option {
      background: #191713;
      color: #f4ebd0;
    }
    .field textarea {
      resize: vertical;
      min-height: 80px;
      line-height: 1.5;
    }

    /* Fix Chrome autofill turning inputs white/light blue */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 30px #14120f inset !important;
      -webkit-text-fill-color: #f4ebd0 !important;
      caret-color: #f4ebd0 !important;
    }

    /* Grid Layouts */
    .admin-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .admin-grid-3 {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 640px) {
      .admin-grid-2, .admin-grid-3 {
        grid-template-columns: 1fr;
      }
    }

    /* Key-Value dl */
    dl.kv {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 10px 20px;
      margin: 0;
      font-size: 13px;
    }
    dl.kv dt {
      color: #a79a82;
      font-weight: 500;
    }
    dl.kv dd {
      color: #f4ebd0;
      margin: 0;
      text-align: right;
    }
  </style>
  @stack('styles')
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

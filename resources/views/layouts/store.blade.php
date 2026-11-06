<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="Goddess Club Store — official Reality Club merch for the ones who feel it all.">
  <title>@yield('title', 'Goddess Club Store')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('goddess.css') }}?v=1">
</head>
<body data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
  <div class="announcement">FOR THE MUSIC. FOR THE MEMORIES. FOR YOU. <span>WORLDWIDE SHIPPING &nearr;</span></div>

  <header>
    <a class="brand" href="{{ route('home') }}" aria-label="Goddess Club Store home">goddess <span>CLUB STORE</span></a>

    <nav aria-label="Main navigation">
      <button type="button" data-category-link="all">Shop all</button>
      <button type="button" data-category-link="apparel">Apparel</button>
      <button type="button" data-category-link="music">Music &amp; vinyl</button>
      <button type="button" data-category-link="accessories">Accessories</button>
    </nav>

    <div class="nav-right">
      @auth
        @if (auth()->user()->isAdmin())
          <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin <span>&nearr;</span></a>
        @else
          <a class="nav-link account-nav-link" href="{{ route('account') }}">My Club <span>&nearr;</span></a>
        @endif
        <a class="nav-link" href="{{ route('track') }}">Track order <span>&nearr;</span></a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;margin:0;">
          @csrf
          <button class="nav-link" type="submit" style="background:none;border:none;color:inherit;cursor:pointer;font:inherit;padding:0;">Log out</button>
        </form>
      @else
        <a class="nav-link account-nav-link" href="{{ route('login') }}">My Club <span>&nearr;</span></a>
        <a class="nav-link" href="{{ route('track') }}">Track order <span>&nearr;</span></a>
      @endauth
      <button class="bag" id="cart-button" aria-label="Open shopping bag">BAG <span id="bag-count">0</span></button>
    </div>
  </header>

  @if (session('success'))
    <div class="wrap" style="padding-top:16px">
      <div class="admin-note success">{{ session('success') }}</div>
    </div>
  @endif

  @if (session('error'))
    <div class="wrap" style="padding-top:16px">
      <div class="admin-note error">{{ session('error') }}</div>
    </div>
  @endif

  @yield('content')

  <footer>
    <a class="brand" href="{{ route('home') }}">goddess <span>CLUB STORE</span></a>
    <p>THE MUSIC STAYS. SO DO WE.</p>
    <a class="footer-track" href="{{ route('track') }}">Track your order &nearr;</a>
    <span>&copy; {{ date('Y') }} GODDESS CLUB STORE</span>
  </footer>

  @include('layouts.partials.cart')
  @include('layouts.partials.checkout')
  @include('layouts.partials.size-guide')

  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <script type="text/javascript"
    src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
    data-client-key="{{ config('services.midtrans.client_key') }}"></script>
  <script src="{{ asset('goddess.js') }}?v=2"></script>
  @stack('scripts')
</body>
</html>

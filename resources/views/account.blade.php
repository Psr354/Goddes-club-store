<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>My Club — Goddess Club Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('goddess.css') }}?v=3">
  <style>
    body {
      background: #121212;
      color: #f4ebd0;
      min-height: 100vh;
      font-family: 'DM Sans', sans-serif;
      margin: 0;
    }
    .account-sidebar nav button.current {
      color: #f4ebd0;
      background: #24201a;
      border-left: 2px solid #d92323;
    }
    .account-sidebar nav button.current .account-nav-number {
      color: #e5a93b;
    }
    .order-status.delivered, .order-status.paid, .order-status.completed {
      color: #4ade80;
    }
    .order-status.delivered i, .order-status.paid i, .order-status.completed i {
      background: #4ade80;
    }
    .order-status.cancelled {
      color: #f87171;
    }
    .order-status.cancelled i {
      background: #f87171;
    }
    .account-logout-btn {
      background: transparent;
      border: 0;
      color: #a79c88;
      cursor: pointer;
      font-family: inherit;
      text-align: left;
      width: 100%;
      letter-spacing: 1px;
      font-size: 8px;
      padding: 14px;
      transition: color 0.15s;
    }
    .account-logout-btn:hover {
      color: #d92323;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    .preference-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 0;
      border-top: 1px solid #363024;
      cursor: pointer;
    }
    .preference-row strong {
      display: block;
      font-size: 13px;
    }
    .preference-row small {
      color: #9c917d;
      font-size: 11px;
    }
    .address-card {
      background: #191713;
      border: 1px solid #463d2d;
      padding: 22px;
      margin-bottom: 16px;
      position: relative;
    }
    .address-card .badge {
      position: static;
      display: inline-block;
      margin-bottom: 8px;
    }
    .address-card h3 {
      font-size: 16px;
      margin: 0 0 8px;
    }
    .address-card p {
      color: #b7aa92;
      font-size: 12px;
      line-height: 1.7;
      margin: 0 0 6px;
    }
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.85);
      z-index: 999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-overlay.open {
      display: flex;
    }
    .modal-box {
      background: #191713;
      border: 1px solid #574930;
      max-width: 520px;
      width: 100%;
      padding: 32px;
      position: relative;
    }
    .modal-box h2 {
      margin-top: 0;
      font-size: 24px;
      color: #f4ebd0;
    }
    .modal-close {
      position: absolute;
      top: 16px;
      right: 16px;
      background: none;
      border: none;
      color: #a79c88;
      font-size: 20px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="announcement">FOR THE MUSIC. FOR THE MEMORIES. FOR YOU. <span>WORLDWIDE SHIPPING &nearr;</span></div>

  <header>
    <a class="brand" href="{{ route('home') }}">goddess <span>CLUB STORE</span></a>
    <nav aria-label="Main navigation">
      <button type="button" onclick="window.location.href='{{ route('home') }}'">Shop all</button>
      <button type="button" onclick="window.location.href='{{ route('home') }}#drop'">Apparel</button>
      <button type="button" onclick="window.location.href='{{ route('home') }}#drop'">Music &amp; vinyl</button>
      <button type="button" onclick="window.location.href='{{ route('home') }}#drop'">Accessories</button>
    </nav>
    <div class="nav-right">
      <button class="account-nav-link is-active" onclick="window.location.href='{{ route('account') }}'">My Club &nearr;</button>
      <button onclick="window.location.href='{{ route('track') }}'">Track order <span>&nearr;</span></button>
      <button class="bag" onclick="window.location.href='{{ route('home') }}'">STORE <span>&nearr;</span></button>
    </div>
  </header>

  <main class="account-page">
    <div class="account-breadcrumb">
      <button type="button" onclick="window.location.href='{{ route('home') }}'">Store</button>
      <span>/</span> My account
    </div>

    <div class="account-heading">
      <div>
        <div class="eyebrow muted">YOUR OWN LITTLE CORNER OF THE CLUB</div>
        <h1>Back for the <em>feeling.</em></h1>
        <p>The music brought you here. Make yourself at home.</p>
      </div>
      <div class="member-mark">
        <span>REALITY CLUB</span>
        <b>ONE OF US.</b>
        <span>THE GODDESS COMMUNITY</span>
      </div>
    </div>

    <div class="account-layout">
      <!-- Sidebar -->
      <aside class="account-sidebar">
        <div class="member-profile">
          <div class="member-avatar">
            @php
              $parts = explode(' ', $user->name ?? 'Demo Member');
              $initials = count($parts) >= 2 ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1)) : strtoupper(substr($parts[0], 0, 2));
            @endphp
            {{ $initials }}
          </div>
          <div>
            <strong>{{ $user->name ?? 'Demo Member' }}</strong>
            <span>MEMBER SINCE {{ $user->created_at ? $user->created_at->format('M Y') : 'JUNE 2025' }}</span>
          </div>
        </div>

        <nav aria-label="Account navigation">
          <button type="button" class="tab-btn current" data-tab="overview" onclick="switchTab('overview')">
            <span class="account-nav-number">01</span>Overview<span class="account-nav-arrow">&nearr;</span>
          </button>
          <button type="button" class="tab-btn" data-tab="orders" onclick="switchTab('orders')">
            <span class="account-nav-number">02</span>My orders<span class="account-nav-arrow">&nearr;</span>
          </button>
          <button type="button" class="tab-btn" data-tab="addresses" onclick="switchTab('addresses')">
            <span class="account-nav-number">03</span>Addresses<span class="account-nav-arrow">&nearr;</span>
          </button>
          <button type="button" class="tab-btn" data-tab="wishlist" onclick="switchTab('wishlist')">
            <span class="account-nav-number">04</span>Wishlist<span class="account-nav-arrow">&nearr;</span>
          </button>
          <button type="button" class="tab-btn" data-tab="settings" onclick="switchTab('settings')">
            <span class="account-nav-number">05</span>Account settings<span class="account-nav-arrow">&nearr;</span>
          </button>
        </nav>

        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
          @csrf
          <button type="submit" class="account-logout account-logout-btn">KELUAR / LOGOUT &nearr;</button>
        </form>

        <div class="sidebar-note">
          <span>GOOD MUSIC. GOOD COMPANY.</span>
          <p>You’re not just a customer.<br>You’re part of the chorus.</p>
          <button type="button" onclick="window.location.href='{{ route('home') }}'">BACK TO THE STORE &nearr;</button>
        </div>
      </aside>

      <!-- Content Area -->
      <section class="account-content" aria-label="Account details">
        <div class="account-demo">
          PREVIEW ACCOUNT <span>Sistem terhubung ke Database MySQL &amp; Event Payment</span>
        </div>

        @if (session('account_notice'))
          <div class="account-notice" role="status">
            {{ session('account_notice') }}
            <button type="button" onclick="this.parentElement.remove()" aria-label="Dismiss notification">×</button>
          </div>
        @endif

        <div id="ajax-notice" class="account-notice" role="status" style="display:none;">
          <span id="ajax-notice-text"></span>
          <button type="button" onclick="document.getElementById('ajax-notice').style.display='none'">×</button>
        </div>

        <!-- TAB 1: OVERVIEW -->
        <div id="tab-overview" class="tab-content active">
          <div class="account-welcome">
            <div>
              <span class="account-kicker">GOOD TO HAVE YOU BACK</span>
              <h2>Hey, {{ explode(' ', $user->name ?? 'Demo')[0] }}. <em>You belong here.</em></h2>
              <p>A few good things, all in one place.</p>
            </div>
            <span class="welcome-star" aria-hidden="true">✳</span>
          </div>

          <div class="account-stats">
            <button type="button" onclick="switchTab('orders')">
              <span>{{ str_pad(max(1, count($orders ?? [])), 2, '0', STR_PAD_LEFT) }}</span>
              <div>ORDERS PLACED <b>&nearr;</b></div>
            </button>
            <button type="button" onclick="switchTab('orders')">
              <span>{{ str_pad(collect($orders ?? [])->filter(fn($o) => in_array($o->status, ['pending_payment', 'paid', 'processing', 'shipped']))->count(), 2, '0', STR_PAD_LEFT) }}</span>
              <div>ON THE WAY <b>&nearr;</b></div>
            </button>
            <button type="button" onclick="switchTab('wishlist')">
              <span id="overview-wishlist-count">{{ str_pad(count($wishlists ?? []) ?: 1, 2, '0', STR_PAD_LEFT) }}</span>
              <div>SAVED FAVORITES <b>&nearr;</b></div>
            </button>
          </div>

          <div class="account-section-heading">
            <h2>Your latest <em>encore.</em></h2>
            <button type="button" onclick="switchTab('orders')">ALL ORDERS &nearr;</button>
          </div>

          @php
            $latestOrder = $orders->first();
          @endphp

          @if ($latestOrder)
            <article class="account-order">
              <div class="order-top">
                <div>
                  <strong>#{{ $latestOrder->invoice_number }}</strong>
                  <span>{{ $latestOrder->created_at->format('d F Y') }}</span>
                </div>
                <span class="order-status {{ in_array($latestOrder->status, ['paid', 'completed', 'shipped']) ? 'delivered' : ($latestOrder->status === 'cancelled' ? 'cancelled' : '') }}">
                  <i></i>{{ ucfirst(str_replace('_', ' ', $latestOrder->status)) }}
                </span>
              </div>
              <div class="order-product">
                @php
                  $firstItem = $latestOrder->items->first();
                  $itemImg = $firstItem?->product?->image ?? 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85';
                @endphp
                <img src="{{ $itemImg }}" alt="{{ $firstItem?->product_name ?? 'Merch Item' }}">
                <div>
                  <span class="account-kicker">THE GODDESS COLLECTION</span>
                  <h3>{{ $firstItem?->product_name ?? 'The Goddess Tour Tee' }}</h3>
                  <p>{{ $firstItem?->sku }} <span>· Qty {{ $firstItem?->quantity ?? 1 }}</span></p>
                </div>
                <strong>${{ number_format($latestOrder->grand_total, 2) }}</strong>
              </div>
              <div class="order-bottom">
                <span>{{ $latestOrder->status === 'completed' ? 'A little piece of the feeling. Yours now.' : 'Your next favorite thing is getting closer.' }}</span>
                <button type="button" onclick="showOrderDetail('{{ $latestOrder->invoice_number }}')">DETAIL &amp; TRACK &nearr;</button>
              </div>
            </article>
          @else
            <!-- Sample Order Fallback -->
            <article class="account-order">
              <div class="order-top">
                <div>
                  <strong>#GC-1024</strong>
                  <span>12 June 2025</span>
                </div>
                <span class="order-status"><i></i>On the way</span>
              </div>
              <div class="order-product">
                <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85" alt="The Goddess Tour Tee">
                <div>
                  <span class="account-kicker">THE GODDESS COLLECTION</span>
                  <h3>The Goddess Tour Tee</h3>
                  <p>Washed black / M <span>· Qty 1</span></p>
                </div>
                <strong>$35.00</strong>
              </div>
              <div class="order-bottom">
                <span>Your next favorite thing is getting closer.</span>
                <button type="button" onclick="showOrderDetail('GC-1024')">TRACK ORDER &nearr;</button>
              </div>
            </article>
          @endif

          <div class="account-bottom-grid">
            <div class="account-small-panel">
              <span class="account-kicker">YOUR DETAILS, YOUR WAY</span>
              <h3>A place to call home.</h3>
              <p>Keep your delivery details ready for the next drop.</p>
              <button type="button" onclick="switchTab('addresses')">MANAGE ADDRESSES &nearr;</button>
            </div>
            <div class="account-drop-panel">
              <span class="account-kicker">SMALL BATCHES. BIG FEELINGS.</span>
              <h3>Your next obsession<br>is waiting.</h3>
              <button type="button" onclick="window.location.href='{{ route('home') }}'">EXPLORE THE DROP &nearr;</button>
              <span class="drop-ring" aria-hidden="true">GC</span>
            </div>
          </div>
        </div>

        <!-- TAB 2: MY ORDERS -->
        <div id="tab-orders" class="tab-content">
          <div class="account-section-heading">
            <div>
              <span class="account-kicker">EVERY ORDER, A LITTLE MEMORY</span>
              <h2 id="orders-heading">Your orders.</h2>
            </div>
            <button type="button" id="back-all-orders-btn" style="display:none;" onclick="hideOrderDetail()">← ALL ORDERS</button>
          </div>

          <div id="order-detail-container" style="display:none;">
            <article class="account-order" id="detail-order-card">
              <div class="order-top">
                <div>
                  <strong id="detail-order-id">#GC-1024</strong>
                  <span id="detail-order-date">12 June 2025</span>
                </div>
                <span class="order-status" id="detail-order-status"><i></i>On the way</span>
              </div>
              <div class="order-product">
                <img id="detail-order-img" src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85" alt="Product image">
                <div>
                  <span class="account-kicker">THE GODDESS COLLECTION</span>
                  <h3 id="detail-order-name">The Goddess Tour Tee</h3>
                  <p id="detail-order-variant">Washed black / M <span>· Qty 1</span></p>
                </div>
                <strong id="detail-order-price">$35.00</strong>
              </div>
            </article>

            <!-- Interactive Payment Webhook Simulator Panel -->
            @if (app()->environment('local', 'testing'))
            <div class="account-small-panel" id="payment-sim-box" style="margin-top:16px;background:#241d14;border-color:#755c32;">
              <span class="account-kicker">SIMULASI PAYMENT GATEWAY &amp; WEBHOOK</span>
              <h3 style="margin:4px 0 8px;font-size:16px;">Uji Alur Pembayaran Nyata</h3>
              <p style="margin:0 0 12px;font-size:12px;color:#cbb58b;">
                Simulasikan callback webhook settlement dari payment gateway (Midtrans/DOKU) untuk memotong stok permanen dan mencatat pelacakan.
              </p>
              <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="primary" style="padding:8px 16px;font-size:10px;" onclick="simulatePayment('pay')">
                  SIMULASIKAN PEMBAYARAN SUKSES (LUNAS) &nearr;
                </button>
                <button type="button" class="wishlist-remove" style="padding:8px 16px;font-size:10px;" onclick="simulatePayment('cancel')">
                  BATALKAN PESANAN &nearr;
                </button>
              </div>
            </div>
            @endif

            <div class="account-small-panel" style="margin-top:20px;">
              <span class="account-kicker">DELIVERY JOURNEY · PROGRESS</span>
              <h3 id="delivery-heading">On its way to you.</h3>
              <p>Tracking Number: <code id="detail-tracking-number" style="color:#f4ebd0;background:#24201a;padding:2px 6px;">DEMO-JKT-1024</code></p>
              <ol class="account-steps" id="detail-steps">
                <li class="complete"><span>01</span>Order confirmed</li>
                <li class="complete"><span>02</span>Packed with care</li>
                <li class="complete"><span>03</span>On the way</li>
                <li><span>04</span>Delivered</li>
              </ol>
            </div>
          </div>

          <div id="orders-list">
            @forelse ($orders as $ord)
              <article class="account-order" data-invoice="{{ $ord->invoice_number }}">
                <div class="order-top">
                  <div>
                    <strong>#{{ $ord->invoice_number }}</strong>
                    <span>{{ $ord->created_at->format('d M Y') }}</span>
                  </div>
                  <span class="order-status {{ in_array($ord->status, ['paid', 'completed', 'shipped']) ? 'delivered' : ($ord->status === 'cancelled' ? 'cancelled' : '') }}">
                    <i></i>{{ ucfirst(str_replace('_', ' ', $ord->status)) }}
                  </span>
                </div>
                <div class="order-product">
                  @php
                    $firstItm = $ord->items->first();
                    $itmImg = $firstItm?->product?->image ?? 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85';
                  @endphp
                  <img src="{{ $itmImg }}" alt="{{ $firstItm?->product_name ?? 'Merch Item' }}">
                  <div>
                    <span class="account-kicker">THE GODDESS COLLECTION</span>
                    <h3>{{ $firstItm?->product_name ?? 'The Goddess Tour Tee' }}</h3>
                    <p>{{ $firstItm?->sku }} <span>· Qty {{ $firstItm?->quantity ?? 1 }}</span></p>
                  </div>
                  <strong>${{ number_format($ord->grand_total, 2) }}</strong>
                </div>
                <div class="order-bottom">
                  <span>{{ $ord->status === 'completed' ? 'A little piece of the feeling. Yours now.' : 'Pesanan tercatat di sistem kami.' }}</span>
                  <button type="button" onclick="loadRealOrderDetail('{{ $ord->invoice_number }}', '{{ $ord->status }}', '{{ number_format($ord->grand_total, 2) }}', '{{ $firstItm?->product_name }}', '{{ $itmImg }}', '{{ $firstItm?->sku }}', '{{ $ord->tracking_number ?? 'DEMO-JKT-' . substr($ord->invoice_number, -4) }}')">
                    KELOLA &amp; TRACK &nearr;
                  </button>
                </div>
              </article>
            @empty
              <!-- Demo Sample Order 1 -->
              <article class="account-order">
                <div class="order-top">
                  <div>
                    <strong>#GC-1024</strong>
                    <span>12 June 2025</span>
                  </div>
                  <span class="order-status"><i></i>On the way</span>
                </div>
                <div class="order-product">
                  <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85" alt="The Goddess Tour Tee">
                  <div>
                    <span class="account-kicker">THE GODDESS COLLECTION</span>
                    <h3>The Goddess Tour Tee</h3>
                    <p>Washed black / M <span>· Qty 1</span></p>
                  </div>
                  <strong>$35.00</strong>
                </div>
                <div class="order-bottom">
                  <span>Your next favorite thing is getting closer.</span>
                  <button type="button" onclick="showOrderDetail('GC-1024')">TRACK ORDER &nearr;</button>
                </div>
              </article>
            @endforelse
          </div>
        </div>

        <!-- TAB 3: ADDRESSES -->
        <div id="tab-addresses" class="tab-content">
          <div class="account-section-heading">
            <div>
              <span class="account-kicker">WHERE THE MUSIC ARRIVES</span>
              <h2>Saved addresses.</h2>
            </div>
            <button type="button" onclick="openAddressModal()">+ TAMBAH ALAMAT BARU &nearr;</button>
          </div>

          @forelse ($addresses as $addr)
            <div class="address-card">
              @if ($addr->is_primary)
                <span class="badge" style="background:#30291c;color:#e5a93b;border:1px solid #74603b;">PRIMARY / UTAMA</span>
              @endif
              <h3>{{ $addr->recipient_name }}</h3>
              <p>{{ $addr->full_address }}<br>Kode Pos: {{ $addr->postal_code }}</p>
              <p>Telepon: {{ $addr->phone }}</p>
              <div style="margin-top:14px;display:flex;gap:16px;">
                <form method="POST" action="{{ route('account.address.destroy', $addr) }}" onsubmit="return confirm('Hapus alamat ini?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="text-link" style="color:#f87171;font-size:11px;background:none;border:none;cursor:pointer;padding:0;">Hapus alamat &nearr;</button>
                </form>
              </div>
            </div>
          @empty
            <div class="address-card">
              <span class="badge" style="background:#30291c;color:#e5a93b;border:1px solid #74603b;">PRIMARY / HOME</span>
              <h3>{{ $user->name ?? 'Alex Morgan' }}</h3>
              <p>Jl. Melodi No. 16, Kebayoran Baru<br>Jakarta Selatan, DKI Jakarta 12160</p>
              <p>Phone: +62 812 3456 7890</p>
            </div>
          @endforelse
        </div>

        <!-- TAB 4: WISHLIST -->
        <div id="tab-wishlist" class="tab-content">
          <div class="account-section-heading">
            <div>
              <span class="account-kicker">KEEP THE GOOD STUFF CLOSE</span>
              <h2>Saved for <em>later.</em></h2>
            </div>
            <span class="account-kicker" id="wishlist-badge">{{ str_pad(count($wishlists ?? []) ?: 1, 2, '0', STR_PAD_LEFT) }} ITEMS</span>
          </div>

          <div id="wishlist-container">
            @forelse ($wishlists as $w)
              <div class="account-wishlist" id="wishlist-item-{{ $w->product_id }}">
                <img src="{{ $w->product->image }}" alt="{{ $w->product->name }}">
                <div>
                  <span class="account-kicker">{{ $w->product->category?->name ?? 'MERCH' }}</span>
                  <h3>{{ $w->product->name }}</h3>
                  <p>${{ number_format($w->product->price, 2) }}</p>
                  <button type="button" class="primary" onclick="window.location.href='{{ route('product.show', $w->product->slug) }}'">VIEW PRODUCT &nearr;</button>
                  <button type="button" class="wishlist-remove" onclick="toggleWishlistDb({{ $w->product_id }})">REMOVE FROM WISHLIST</button>
                </div>
              </div>
            @empty
              <!-- Sample Item -->
              <div id="wishlist-sample-item" class="account-wishlist">
                <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85" alt="The Goddess Tour Tee">
                <div>
                  <span class="account-kicker">APPAREL / VOL. 01</span>
                  <h3>The Goddess Tour Tee</h3>
                  <p>$35.00</p>
                  <button type="button" class="primary" onclick="window.location.href='{{ route('home') }}'">SHOP THE COLLECTION &nearr;</button>
                  <button type="button" class="wishlist-remove" onclick="removeWishlistSample()">REMOVE FROM WISHLIST</button>
                </div>
              </div>
            @endforelse
          </div>

          <div id="wishlist-empty" class="account-empty" style="display:none;">
            <h3>Room for your next favorite.</h3>
            <p>Your preview wishlist is empty.</p>
            <button type="button" class="text-link" onclick="restoreWishlistSample()">RESTORE SAMPLE ITEM &nearr;</button>
          </div>
        </div>

        <!-- TAB 5: ACCOUNT SETTINGS -->
        <div id="tab-settings" class="tab-content">
          <div class="account-section-heading">
            <div>
              <span class="account-kicker">THE PERSON BEHIND THE PLAYLIST</span>
              <h2>Make it <em>yours.</em></h2>
            </div>
          </div>

          <form class="account-form account-small-panel" method="POST" action="{{ route('account.profile') }}">
            @csrf
            <h3>Personal information</h3>
            <p>Your details stay in this demo session only.</p>

            <label>Full name
              <input name="name" required value="{{ old('name', $user->name ?? 'Demo Member') }}" autocomplete="name">
            </label>

            <label>Email address
              <input name="email" required type="email" value="{{ old('email', $user->email ?? 'demo@example.com') }}" autocomplete="email">
            </label>

            <label>Phone number
              <input name="phone" type="tel" value="{{ old('phone', $user->phone ?? '+62 812 3456 7890') }}" autocomplete="tel">
            </label>

            <button class="primary" type="submit" style="margin-top:16px;">SAVE CHANGES &nearr;</button>
          </form>

          <div class="account-small-panel notification-panel" style="margin-top:20px;">
            <h3>A little less noise.</h3>
            <p>Choose what you’d like to hear from us. Preview preferences only; no messages will be sent.</p>

            <label class="preference-row">
              <span>
                <strong>Order updates</strong>
                <small>Shipping progress and delivery news.</small>
              </span>
              <input type="checkbox" checked>
            </label>

            <label class="preference-row">
              <span>
                <strong>Letters from the club</strong>
                <small>New drops, band news, and little surprises.</small>
              </span>
              <input type="checkbox" checked>
            </label>
          </div>
        </div>
      </section>
    </div>

    <div class="account-signoff">
      <span>FOR THE MUSIC. FOR THE MEMORIES. FOR YOU.</span>
      <span>REALITY CLUB, FOREVER. &nearr;</span>
    </div>
  </main>

  <!-- Modal Tambah Alamat Baru -->
  <div id="address-modal" class="modal-overlay">
    <div class="modal-box">
      <button type="button" class="modal-close" onclick="closeAddressModal()">×</button>
      <span class="account-kicker">DELIVERY DESTINATION</span>
      <h2>Tambah Alamat Baru</h2>
      <form method="POST" action="{{ route('account.address.store') }}">
        @csrf
        <label style="display:block;margin:14px 0 6px;font-size:12px;">Nama Penerima
          <input name="recipient_name" required placeholder="Alex Morgan" style="width:100%;min-height:42px;padding:10px;background:#121212;border:1px solid #574930;color:#f4ebd0;margin-top:6px;">
        </label>
        <label style="display:block;margin:14px 0 6px;font-size:12px;">Nomor Telepon
          <input name="phone" required placeholder="+62 812 3456 7890" style="width:100%;min-height:42px;padding:10px;background:#121212;border:1px solid #574930;color:#f4ebd0;margin-top:6px;">
        </label>
        <label style="display:block;margin:14px 0 6px;font-size:12px;">Alamat Lengkap
          <textarea name="full_address" required rows="3" placeholder="Jl. Melodi No. 16, Kebayoran Baru, Jakarta Selatan" style="width:100%;padding:10px;background:#121212;border:1px solid #574930;color:#f4ebd0;margin-top:6px;font-family:inherit;"></textarea>
        </label>
        <label style="display:block;margin:14px 0 6px;font-size:12px;">Kode Pos
          <input name="postal_code" required placeholder="12160" maxlength="10" style="width:100%;min-height:42px;padding:10px;background:#121212;border:1px solid #574930;color:#f4ebd0;margin-top:6px;">
        </label>
        <label style="display:flex;align-items:center;gap:8px;margin:16px 0;font-size:12px;cursor:pointer;">
          <input type="checkbox" name="is_primary" value="1"> Jadikan sebagai alamat utama
        </label>
        <div style="display:flex;gap:12px;margin-top:20px;">
          <button type="submit" class="primary" style="flex:1;">SIMPAN ALAMAT &nearr;</button>
          <button type="button" class="wishlist-remove" onclick="closeAddressModal()">BATAL</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let currentActiveInvoice = 'GC-1024';

    function switchTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('current'));

      const target = document.getElementById('tab-' + tabId);
      if (target) target.classList.add('active');

      const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
      if (btn) btn.classList.add('current');

      hideOrderDetail();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showOrderDetail(id) {
      switchTab('orders');
      currentActiveInvoice = id;
      document.getElementById('orders-list').style.display = 'none';
      document.getElementById('order-detail-container').style.display = 'block';
      document.getElementById('back-all-orders-btn').style.display = 'inline-block';
      document.getElementById('orders-heading').textContent = 'Order #' + id;

      if (id === 'GC-0986') {
        document.getElementById('detail-order-id').textContent = '#GC-0986';
        document.getElementById('detail-order-date').textContent = '28 May 2025';
        document.getElementById('detail-order-status').className = 'order-status delivered';
        document.getElementById('detail-order-status').innerHTML = '<i></i>Delivered';
        document.getElementById('detail-order-img').src = 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=700&q=85';
        document.getElementById('detail-order-name').textContent = 'Reality Club Presents…';
        document.getElementById('detail-order-variant').innerHTML = '12-inch vinyl / Standard <span>· Qty 1</span>';
        document.getElementById('detail-order-price').textContent = '$42.00';
        document.getElementById('delivery-heading').textContent = 'Home, at last.';
        document.getElementById('payment-sim-box').style.display = 'none';
      } else {
        document.getElementById('detail-order-id').textContent = '#' + id;
        document.getElementById('detail-order-date').textContent = '12 June 2025';
        document.getElementById('detail-order-status').className = 'order-status';
        document.getElementById('detail-order-status').innerHTML = '<i></i>Pending Payment / On the way';
        document.getElementById('detail-order-img').src = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=700&q=85';
        document.getElementById('detail-order-name').textContent = 'The Goddess Tour Tee';
        document.getElementById('detail-order-variant').innerHTML = 'Washed black / M <span>· Qty 1</span>';
        document.getElementById('detail-order-price').textContent = '$35.00';
        document.getElementById('delivery-heading').textContent = 'Progress pesananmu.';
        document.getElementById('payment-sim-box').style.display = 'block';
      }
    }

    function loadRealOrderDetail(invoice, status, total, name, img, sku, tracking) {
      switchTab('orders');
      currentActiveInvoice = invoice;
      document.getElementById('orders-list').style.display = 'none';
      document.getElementById('order-detail-container').style.display = 'block';
      document.getElementById('back-all-orders-btn').style.display = 'inline-block';
      document.getElementById('orders-heading').textContent = 'Order #' + invoice;

      document.getElementById('detail-order-id').textContent = '#' + invoice;
      document.getElementById('detail-order-date').textContent = 'Hari ini';
      
      const statusEl = document.getElementById('detail-order-status');
      statusEl.className = 'order-status ' + (status === 'paid' || status === 'completed' ? 'delivered' : (status === 'cancelled' ? 'cancelled' : ''));
      statusEl.innerHTML = '<i></i>' + status.toUpperCase();

      document.getElementById('detail-order-img').src = img;
      document.getElementById('detail-order-name').textContent = name;
      document.getElementById('detail-order-variant').innerHTML = sku + ' <span>· Qty 1</span>';
      document.getElementById('detail-order-price').textContent = '$' + total;
      document.getElementById('detail-tracking-number').textContent = tracking;

      // Update stepper
      const steps = document.getElementById('detail-steps').children;
      for (let i = 0; i < steps.length; i++) steps[i].className = '';
      if (status === 'pending_payment') {
        steps[0].className = 'complete';
      } else if (status === 'paid' || status === 'processing') {
        steps[0].className = 'complete';
        steps[1].className = 'complete';
      } else if (status === 'shipped') {
        steps[0].className = 'complete';
        steps[1].className = 'complete';
        steps[2].className = 'complete';
      } else if (status === 'completed') {
        for (let i = 0; i < steps.length; i++) steps[i].className = 'complete';
      }

      const simBox = document.getElementById('payment-sim-box');
      simBox.style.display = (status === 'pending_payment') ? 'block' : 'none';
    }

    function hideOrderDetail() {
      const container = document.getElementById('order-detail-container');
      const list = document.getElementById('orders-list');
      const backBtn = document.getElementById('back-all-orders-btn');
      const heading = document.getElementById('orders-heading');

      if (container) container.style.display = 'none';
      if (list) list.style.display = 'block';
      if (backBtn) backBtn.style.display = 'none';
      if (heading) heading.textContent = 'Your orders.';
    }

    async function simulatePayment(action) {
      try {
        const res = await fetch('/api/payment/simulate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            invoice_number: currentActiveInvoice,
            action: action
          })
        });
        const data = await res.json();
        if (data.success) {
          alert(data.message);
          window.location.reload();
        } else {
          alert('Gagal: ' + data.message);
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    function openAddressModal() {
      document.getElementById('address-modal').classList.add('open');
    }

    function closeAddressModal() {
      document.getElementById('address-modal').classList.remove('open');
    }

    async function toggleWishlistDb(productId) {
      try {
        const res = await fetch('/api/wishlist/toggle', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.success) {
          const itemEl = document.getElementById('wishlist-item-' + productId);
          if (itemEl) itemEl.remove();
          document.getElementById('wishlist-badge').textContent = String(data.count).padStart(2, '0') + ' ITEMS';
          document.getElementById('overview-wishlist-count').textContent = String(data.count).padStart(2, '0');
          showAjaxNotice(data.message);
        }
      } catch (e) {
        showAjaxNotice('Gagal memperbarui wishlist.');
      }
    }

    function showAjaxNotice(msg) {
      const box = document.getElementById('ajax-notice');
      document.getElementById('ajax-notice-text').textContent = msg;
      box.style.display = 'block';
      setTimeout(() => box.style.display = 'none', 4000);
    }

    function removeWishlistSample() {
      const sample = document.getElementById('wishlist-sample-item');
      if (sample) sample.style.display = 'none';
      document.getElementById('wishlist-empty').style.display = 'block';
      document.getElementById('wishlist-badge').textContent = '00 ITEMS';
      document.getElementById('overview-wishlist-count').textContent = '00';
    }

    function restoreWishlistSample() {
      const sample = document.getElementById('wishlist-sample-item');
      if (sample) sample.style.display = 'flex';
      document.getElementById('wishlist-empty').style.display = 'none';
      document.getElementById('wishlist-badge').textContent = '01 ITEM';
      document.getElementById('overview-wishlist-count').textContent = '01';
    }
  </script>
</body>
</html>

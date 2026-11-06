<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk / Login — Goddess Club Store</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('goddess.css') }}?v=2">
  <style>
    body {
      background: #121212;
      color: #f4ebd0;
      min-height: 100vh;
      font-family: 'DM Sans', sans-serif;
      margin: 0;
    }
    .login-message code {
      color: #f4ebd0;
      background: #1c1a14;
      padding: 2px 6px;
      border: 1px solid #554832;
      font-family: monospace;
    }
    .demo-account-row {
      transition: background 0.15s ease;
      padding: 6px 8px;
      margin: 6px -8px;
      border-radius: 2px;
      cursor: pointer;
    }
    .demo-account-row:hover {
      background: rgba(229, 169, 59, 0.12);
    }
    .login-switch a {
      color: #f4ebd0;
      text-underline-offset: 5px;
      padding: 0;
      font-size: 11px;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <main class="login-page">
    <button type="button" class="login-back" onclick="window.location.href='{{ route('home') }}'">← KEMBALI KE TOKO</button>

    <div class="login-layout">
      <!-- Left side: Brand story & vinyl artwork -->
      <section class="login-story">
        <div class="eyebrow"><span></span> THE GODDESS COMMUNITY</div>
        <h1>Good music.<br>Better with<br><em>you here.</em></h1>
        <p>Satu tempat untuk semua favoritmu.<br>Dari drop pertama sampai encore terakhir.</p>

        <div class="login-record" aria-hidden="true">
          <div>
            <span>REALITY CLUB</span>
            <b>goddess</b>
            <small>SIDE A · FOR THE FEELING</small>
          </div>
        </div>

        <div class="login-story-footer">
          <span>EST. 2016 · JAKARTA</span>
          <span>YOU’RE ONE OF US.</span>
        </div>
      </section>

      <!-- Right side: Backstage pass login form -->
      <section class="login-form-panel" aria-labelledby="login-title">
        <div class="login-preview">
          PROTOTIPE UI <span>Belum terhubung ke autentikasi</span>
        </div>

        <div class="eyebrow muted">YOUR BACKSTAGE PASS</div>
        <h2 id="login-title">Welcome <em>back.</em></h2>
        <p class="login-description">Masuk ke sudut kecilmu di Goddess Club.</p>

        @if ($errors->any())
          <div class="login-message" role="status" style="border-color: #d92323; background: #2a1717; color: #fca5a5; margin-bottom: 20px;">
            {{ $errors->first() }}
          </div>
        @endif

        @if (session('success'))
          <div class="login-message" role="status" style="border-color: #22c55e; background: #15281e; color: #86efac; margin-bottom: 20px;">
            {{ session('success') }}
          </div>
        @endif

        <div id="reset-sim-alert" class="login-message" role="status" style="display:none; border-color: #e5a93b; color: #fde68a;">
          Simulasi saja: email reset tidak dikirim. Pemulihan password akan dihubungkan saat backend Laravel tersedia.
        </div>

        <form method="POST" action="{{ route('login') }}" id="login-form">
          @csrf

          <label for="auth-email">Alamat email
            <input id="auth-email" name="email" type="email" autocomplete="email" required placeholder="kamu@example.com" value="{{ old('email', 'demo@example.com') }}">
          </label>

          <div class="login-password-label">
            <label for="auth-password">Password</label>
            <button type="button" id="forgot-btn" onclick="toggleResetSimulation()">Lupa password?</button>
          </div>

          <div class="login-password">
            <input id="auth-password" name="password" type="password" autocomplete="current-password" minlength="8" required placeholder="Minimal 8 karakter" aria-describedby="auth-demo-note" value="Goddess123">
            <button type="button" id="toggle-pw-btn" aria-controls="auth-password" aria-pressed="false" onclick="togglePasswordVisibility()">LIHAT</button>
          </div>

          <button class="primary login-submit" type="submit">
            MASUK KE DEMO <span>↗</span>
          </button>

          <p id="auth-demo-note" class="login-demo-note">
            Gunakan email dan password contoh, bukan kredensial asli. Tidak ada akun dibuat atau password disimpan. Sesi demo berakhir saat halaman dimuat ulang.
          </p>
        </form>

        <div class="login-switch">
          Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang ↗</a>
        </div>

        <div class="login-message">
          <strong>Satu login, dua dashboard demo</strong>
          <p>Role ditentukan oleh akun contoh, bukan pilihan pada form.</p>

          <div class="demo-account-row" onclick="fillDemo('demo@example.com', 'Goddess123')" title="Klik untuk otomatis mengisi">
            <p style="margin:0;">
              <strong>User:</strong> demo@example.com<br>
              Password: <code>Goddess123</code>
              <small style="color: #e5a93b; font-size: 10px; margin-left: 6px;">(klik untuk isi)</small>
            </p>
          </div>

          <div class="demo-account-row" onclick="fillDemo('admin@example.com', 'Admin123')" title="Klik untuk otomatis mengisi">
            <p style="margin:0;">
              <strong>Admin:</strong> admin@example.com<br>
              Password: <code>Admin123</code>
              <small style="color: #e5a93b; font-size: 10px; margin-left: 6px;">(klik untuk isi)</small>
            </p>
          </div>

          <p style="margin-top: 10px; margin-bottom: 0;">
            Pendaftaran demo selalu masuk sebagai user. Ini bukan otorisasi aman; role produksi harus diverifikasi di server Laravel.
          </p>
        </div>

        <div class="login-panel-footer">FOR THE MUSIC. FOR THE MEMORIES. FOR YOU.</div>
      </section>
    </div>
  </main>

  <script>
    function togglePasswordVisibility() {
      const input = document.getElementById('auth-password');
      const btn = document.getElementById('toggle-pw-btn');
      if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'SEMBUNYIKAN';
        btn.setAttribute('aria-pressed', 'true');
      } else {
        input.type = 'password';
        btn.textContent = 'LIHAT';
        btn.setAttribute('aria-pressed', 'false');
      }
    }

    function fillDemo(email, password) {
      const emailInput = document.getElementById('auth-email');
      const passwordInput = document.getElementById('auth-password');
      emailInput.value = email;
      passwordInput.value = password;
      emailInput.focus();
    }

    function toggleResetSimulation() {
      const alert = document.getElementById('reset-sim-alert');
      alert.style.display = alert.style.display === 'none' ? 'block' : 'none';
      if (alert.style.display === 'block') {
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
  </script>
</body>
</html>

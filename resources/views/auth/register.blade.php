<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar / Register — Goddess Club Store</title>
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

      <!-- Right side: Backstage pass register form -->
      <section class="login-form-panel" aria-labelledby="login-title">
        <div class="login-preview">
          PROTOTIPE UI <span>Pendaftaran contoh masuk sebagai user</span>
        </div>

        <div class="eyebrow muted">YOUR BACKSTAGE PASS</div>
        <h2 id="login-title">Join the <em>club.</em></h2>
        <p class="login-description">Buat profil contoh dan temukan tempatmu di klub.</p>

        @if ($errors->any())
          <div class="login-message" role="status" style="border-color: #d92323; background: #2a1717; color: #fca5a5; margin-bottom: 20px;">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="register-form">
          @csrf

          <label for="auth-name">Nama lengkap
            <input id="auth-name" name="name" type="text" autocomplete="name" required placeholder="Nama kamu" maxlength="80" value="{{ old('name') }}">
          </label>

          <label for="auth-email">Alamat email
            <input id="auth-email" name="email" type="email" autocomplete="email" required placeholder="kamu@example.com" value="{{ old('email') }}">
          </label>

          <div class="login-password-label">
            <label for="auth-password">Password</label>
          </div>
          <div class="login-password">
            <input id="auth-password" name="password" type="password" autocomplete="new-password" minlength="8" required placeholder="Minimal 8 karakter">
            <button type="button" id="toggle-pw-btn" aria-controls="auth-password" aria-pressed="false" onclick="togglePasswordVisibility('auth-password', this)">LIHAT</button>
          </div>

          <label for="auth-confirmation" style="margin-top: 16px;">Konfirmasi password
            <div class="login-password" style="margin-top: 8px;">
              <input id="auth-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required placeholder="Ulangi password">
              <button type="button" id="toggle-pw-conf-btn" aria-controls="auth-confirmation" aria-pressed="false" onclick="togglePasswordVisibility('auth-confirmation', this)">LIHAT</button>
            </div>
          </label>

          <button class="primary login-submit" type="submit">
            COBA DAFTAR <span>↗</span>
          </button>

          <p id="auth-demo-note" class="login-demo-note">
            Pendaftaran demo selalu masuk sebagai user. Ini bukan otorisasi aman; role produksi harus diverifikasi di server Laravel.
          </p>
        </form>

        <div class="login-switch">
          Sudah punya akun? <a href="{{ route('login') }}">Kembali ke login ↗</a>
        </div>

        <div class="login-panel-footer">FOR THE MUSIC. FOR THE MEMORIES. FOR YOU.</div>
      </section>
    </div>
  </main>

  <script>
    function togglePasswordVisibility(fieldId, btn) {
      const input = document.getElementById(fieldId);
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
  </script>
</body>
</html>

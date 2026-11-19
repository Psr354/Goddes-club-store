<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password — Goddess Club Store</title>
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
  </style>
</head>
<body>
  <main class="login-page">
    <button type="button" class="login-back" onclick="window.location.href='{{ route('login') }}'">&larr; KEMBALI KE LOGIN</button>

    <div class="login-layout">
      <section class="login-story">
        <div class="eyebrow"><span></span> THE GODDESS COMMUNITY</div>
        <h1>Choose a<br>new password.<br><em>Back in time.</em></h1>
        <p>Amankan akunmu dengan kata sandi baru minimal 8 karakter.</p>
        <div class="login-story-footer">
          <span>EST. 2016 &middot; JAKARTA</span>
          <span>YOU&rsquo;RE ONE OF US.</span>
        </div>
      </section>

      <section class="login-form-panel" aria-labelledby="reset-title">
        <div class="eyebrow muted">NEW CREDENTIALS</div>
        <h2 id="reset-title">Set new <em>password.</em></h2>
        <p class="login-description">Masukkan kata sandi baru untuk akun Goddess Club kamu.</p>

        @if ($errors->any())
          <div class="login-message" role="status" style="border-color: #d92323; background: #2a1717; color: #fca5a5; margin-bottom: 20px;">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ Route::has('password.update') ? route('password.update') : url('/reset-password') }}" id="reset-form">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">

          <label for="reset-email">Alamat email
            <input id="reset-email" name="email" type="email" autocomplete="email" required placeholder="kamu@example.com" value="{{ old('email', $email) }}">
          </label>

          <label for="reset-password">Password baru
            <input id="reset-password" name="password" type="password" required minlength="8" placeholder="Minimal 8 karakter">
          </label>

          <label for="reset-password-confirm">Konfirmasi password baru
            <input id="reset-password-confirm" name="password_confirmation" type="password" required minlength="8" placeholder="Ulangi password baru">
          </label>

          <button class="primary login-submit" type="submit">
            SIMPAN PASSWORD BARU <span>&nearr;</span>
          </button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password — Goddess Club Store</title>
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
        <h1>Reset your<br>password.<br><em>Stay close.</em></h1>
        <p>Masukkan alamat email akunmu untuk menerima tautan pemulihan kata sandi.</p>
        <div class="login-story-footer">
          <span>EST. 2016 &middot; JAKARTA</span>
          <span>YOU&rsquo;RE ONE OF US.</span>
        </div>
      </section>

      <section class="login-form-panel" aria-labelledby="forgot-title">
        <div class="eyebrow muted">ACCOUNT RECOVERY</div>
        <h2 id="forgot-title">Forgot <em>password.</em></h2>
        <p class="login-description">Kami akan mengirimkan instruksi pemulihan ke email kamu.</p>

        @if ($errors->any())
          <div class="login-message" role="status" style="border-color: #d92323; background: #2a1717; color: #fca5a5; margin-bottom: 20px;">
            {{ $errors->first() }}
          </div>
        @endif

        @if (session('status'))
          <div class="login-message" role="status" style="border-color: #22c55e; background: #15281e; color: #86efac; margin-bottom: 20px;">
            {{ session('status') }}
          </div>
        @endif

        <form method="POST" action="{{ Route::has('password.email') ? route('password.email') : url('/forgot-password') }}" id="forgot-form">
          @csrf

          <label for="recovery-email">Alamat email
            <input id="recovery-email" name="email" type="email" autocomplete="email" required placeholder="kamu@example.com" value="{{ old('email') }}">
          </label>

          <button class="primary login-submit" type="submit">
            KIRIM LINK RESET <span>&nearr;</span>
          </button>
        </form>

        <div class="login-switch" style="margin-top: 24px;">
          Ingat password akunmu? <a href="{{ route('login') }}">Masuk sekarang &nearr;</a>
        </div>
      </section>
    </div>
  </main>
</body>
</html>

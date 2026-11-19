<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectAfterAuth();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Silakan tunggu {$seconds} detik sebelum mencoba lagi.",
            ]);
        }

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak cocok.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();
        if ($user) {
            app(CartController::class)->mergeSessionCartToUser($request, $user);
        }

        $request->session()->regenerate();

        return $this->redirectAfterAuth()
            ->with('success', 'Selamat datang kembali!');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectAfterAuth();
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => User::ROLE_CUSTOMER,
        ]);

        Auth::login($user);

        $user = Auth::user();
        if ($user) {
            app(CartController::class)->mergeSessionCartToUser($request, $user);
        }

        $request->session()->regenerate();

        return redirect()
            ->route('account')
            ->with('success', 'Welcome to the club! Your account has been created.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Admins land on the dashboard, customers back on the storefront.
     */
    protected function redirectAfterAuth(): RedirectResponse
    {
        return redirect()->intended(
            Auth::user()?->isAdmin()
                ? route('admin.dashboard')
                : route('account')
        );
    }
}

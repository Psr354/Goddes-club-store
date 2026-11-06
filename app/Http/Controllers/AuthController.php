<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

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

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak cocok.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectAfterAuth()
            ->with('success', 'Welcome back to the club!');
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

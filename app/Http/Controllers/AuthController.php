<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Batasi percobaan login: 5 kali per menit.
        $key = Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $field = str_contains($data['username'], '@') ? 'email' : 'username';
        $user = User::where($field, $data['username'])->first();

        if (! $user || ! Auth::attempt([$field => $data['username'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah.']);
        }

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['username' => 'Akun Anda dinonaktifkan. Hubungi Owner.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => Fmt::now()])->save();
        Audit::log('login', $user, 'Masuk ke aplikasi');

        return redirect()->intended($user->role === 'operator' ? route('production.index') : route('dashboard'));
    }

    public function logout(Request $request)
    {
        Audit::log('logout', $request->user(), 'Keluar dari aplikasi');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda sudah keluar.');
    }
}

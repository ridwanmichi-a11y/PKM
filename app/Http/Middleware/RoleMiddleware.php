<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hak akses per peran. Pemakaian di route: ->middleware('role:owner,admin')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['username' => 'Akun Anda tidak aktif. Hubungi Owner.']);
        }

        if ($roles && ! $user->hasRole(...$roles)) {
            abort(403, 'Halaman ini tidak tersedia untuk peran '.$user->roleLabel().'.');
        }

        return $next($request);
    }
}

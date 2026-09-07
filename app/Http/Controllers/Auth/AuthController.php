<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->isCashier() ? 'pos.index' : 'dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your staff account has been deactivated. Please contact your manager.',
            ]);
        }

        $request->session()->regenerate();

        ActivityLoggerService::log('auth.login', "User {$user->email} logged into the system", $user);

        // Direct cashiers directly to POS Terminal; managers/admins to Dashboard
        if ($user->isCashier() && ! $user->hasRole(['admin', 'manager'])) {
            return redirect()->intended(route('pos.index'))->with('success', "Welcome back, {$user->name}!");
        }

        return redirect()->intended(route('dashboard'))->with('success', "Welcome back, {$user->name}!");
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            ActivityLoggerService::log('auth.logout', "User {$user->email} logged out", $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been safely logged out.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        // If already logged in, redirect to correct dashboard
        if (auth()->check()) {
            return redirect($this->redirectTo(auth()->user()->role));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->withInput();
        }

        $request->session()->regenerate();

        $user = auth()->user();

        // Block deactivated accounts
        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated. Contact admin.']);
        }

        return redirect()->intended($this->redirectTo($user->role));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectTo(string $role): string
    {
        return match($role) {
            'admin'  => route('admin.dashboard'),
            'driver' => route('driver.dashboard'),
            default  => route('staff.trips.index'),
        };
    }
}

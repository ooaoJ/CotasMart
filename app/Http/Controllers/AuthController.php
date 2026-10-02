<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View { return view('auth.login'); }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        $request->session()->regenerate();
        return redirect()->intended(Auth::user()->isAdmin() ? route('admin.dashboard') : route('app.dashboard'));
    }

    public function showRegister(): View { return view('auth.register'); }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
            'role' => 'user', 'plan' => 'cotasmart', 'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(7),
        ]);
        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('app.dashboard')->with('success', 'Seu teste gratuito de 7 dias começou.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}


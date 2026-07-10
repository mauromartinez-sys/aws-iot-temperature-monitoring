<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->homeRouteName());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'El email no tiene un formato válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors([
                        'email' => 'Tu usuario está inactivo. Contactá a un administrador.',
                    ])
                    ->onlyInput('email');
            }

            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            $request->session()->regenerate();

            return redirect()->intended(route($user->homeRouteName()));
        }

        return back()
            ->withErrors([
                'email' => 'Las credenciales ingresadas no son correctas.',
            ])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
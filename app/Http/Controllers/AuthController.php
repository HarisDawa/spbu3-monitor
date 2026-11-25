<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const DEMO_ACCOUNTS = [
        ['email' => 'admin@spbumonitor.test', 'label' => 'Admin Pusat', 'role' => 'admin'],
        ['email' => 'petugas@spbumonitor.test', 'label' => 'Petugas Cilandak', 'role' => 'petugas'],
        ['email' => 'petugas2@spbumonitor.test', 'label' => 'Petugas Bekasi', 'role' => 'petugas'],
        ['email' => 'warga@spbumonitor.test', 'label' => 'Warga', 'role' => 'warga'],
    ];

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login', ['demoAccounts' => self::DEMO_ACCOUNTS]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun ini telah dinonaktifkan.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($user->role === 'warga' ? 'warga' : 'dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function switchUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'exists:users,id'],
        ]);

        /** @var User $user */
        $user = User::findOrFail($data['id']);
        abort_unless($user->is_active, 422, 'Akun tidak aktif.');

        Auth::login($user);

        return response()->json(['ok' => true]);
    }
}

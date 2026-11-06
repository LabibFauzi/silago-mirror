<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAuthController extends Controller
{
    public function login(): View
    {
        return view('admin.auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->input('login');
        
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            if (in_array($user->role, ['moderator', 'admin']) && $user->status === 'active') {
                Auth::login($user);
                $request->session()->regenerate();
                
                return redirect()->route('admin.dashboard');
            }
        }

        return back()->with('error', 'Kredensial tidak valid atau akun tidak aktif.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

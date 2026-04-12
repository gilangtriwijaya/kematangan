<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
        
            // Tambahkan flash message sukses
            session()->flash('success', 'Login berhasil. Selamat datang, ' . auth()->user()->name);
        
            return redirect()->intended('/dashboard');
        }


        return back()->withErrors([
            'email' => 'Login gagal. Cek email dan password.',
        ]);
    }

        public function logout(Request $request)
        {
            if (auth()->check()) {
                \App\Models\LogAktivitas::create([
                    'user_id'     => auth()->id(),
                    'role'        => auth()->user()->role ?? '-',
                    'aksi'        => 'logout',
                    'keterangan'  => 'Logout dari sistem',
                    'url'         => $request->fullUrl(),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'method'      => $request->method(),
                    'status_code' => 200,
                    'created_at'  => now()
                ]);
            }
        
            \Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        
            return redirect('/login');
        }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();            // ⇐ ambil dari Auth, bukan Session
        abort_unless($user, 401);        // jaga-jaga

        return view('profil.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();            // ⇐ konsisten dengan Auth
        abort_unless($user, 401);

        $request->validate([
            'nama'                  => 'required|string|max:255',
            'password'              => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $request->input('nama');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}

<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Request;

class LogLoginListener
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        try {
            LogAktivitas::create([
                'user_id'     => $user->id,
                'role'        => $user->role ?? '-',
                'aksi'        => 'login',
                'keterangan'  => 'Login ke sistem',
                'url'         => Request::fullUrl(),
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
                'method'      => Request::method(),
                'status_code' => 200,
                'created_at'  => now(), // ⬅️ Tambahkan ini
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Gagal mencatat log login: ' . $e->getMessage());
        }
    }
}

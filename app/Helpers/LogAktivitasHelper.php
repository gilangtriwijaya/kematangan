<?php

namespace App\Helpers;

use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class LogAktivitasHelper
{
    public static function catat(string $aksi, string $keterangan = null, int $statusCode = 200): void
    {
        LogAktivitas::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'role' => Auth::check() ? Auth::user()->role : null,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'method' => Request::method(),
            'status_code' => $statusCode,
        ]);
    }
}

<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\LogAktivitas;

if (!function_exists('log_aktivitas_manual')) {
    function log_aktivitas_manual($keterangan = '-', $aksi = null)
    {
        if (Auth::check()) {
            try {
                LogAktivitas::create([
                    'user_id'     => Auth::id(),
                    'role'        => Auth::user()->role ?? '-',
                    'aksi'        => $aksi ?? Request::route()->getName() ?? Request::path(),
                    'keterangan'  => $keterangan,
                    'url'         => Request::fullUrl(),
                    'ip_address'  => Request::ip(),
                    'user_agent'  => Request::userAgent(),
                    'method'      => Request::method(),
                    'status_code' => 200,
                ]);
            } catch (\Exception $e) {
                \Log::error('Gagal mencatat log manual: ' . $e->getMessage());
            }
        }
    }
}

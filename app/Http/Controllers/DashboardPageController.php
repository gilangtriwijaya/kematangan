<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KegiatanPenilaian;
use App\Services\SsoOpdService;

class DashboardPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Normalisasi role: lowercase + hilangkan spasi/underscore
        $rawRole  = (string) ($user?->role ?? 'opd');
        $role     = strtolower(trim($rawRole));
        $role     = str_replace([' ', '_', '-'], '', $role); // "SUPER ADMIN" -> "superadmin"

        // Daftar kegiatan untuk dropdown (minimal kolom yang dipakai di Blade)
        $daftarKegiatan = KegiatanPenilaian::orderByDesc('tahun')
            ->get(['id','nama','tahun','is_aktif']);

        // Tentukan kegiatan aktif (query > aktif > terbaru)
        $kegiatanId = (int) $request->query('kegiatan_id');
        if (!$kegiatanId) {
            $kegiatanId = (int) (
                KegiatanPenilaian::where('is_aktif', 1)->value('id')
                ?: KegiatanPenilaian::orderByDesc('tahun')->value('id')
            );
        }

        $kegiatanAktif = $kegiatanId ? KegiatanPenilaian::find($kegiatanId) : null;

        // Resolve allowed OPD-local user IDs (null = GLOBAL / no filter)
        $allowedOpdUserIds = null;
        try {
            $svc = app(SsoOpdService::class);
            $allowedOpdUserIds = $svc->getAllowedOpdUserIds($user, 'kematangan');
        } catch (\Throwable $e) {
            $allowedOpdUserIds = null; // be permissive on error
        }

        $data = [
            'daftarKegiatan'     => $daftarKegiatan,
            'kegiatanAktif'      => $kegiatanAktif,
            'kegiatan_id'        => $kegiatanAktif?->id ?? 0,
            'allowedOpdUserIds'  => $allowedOpdUserIds,
        ];

        // Pilih view berdasarkan role yang sudah dinormalisasi
        $view = match ($role) {
            'superadmin'  => 'dashboard.superadmin',
            'admin'       => 'dashboard.admin',
            'verifikator' => 'dashboard.verifikator',
            'opd'         => 'dashboard.opd',
            default       => 'dashboard.superadmin', // fallback aman
        };

        // If a role-specific view does not exist (e.g. dashboard.verifikator),
        // render admin view but keep allowed OPD filter data for the view.
        if (! view()->exists($view)) {
            $view = 'dashboard.admin';
        }

        return view($view, $data);
    }
}

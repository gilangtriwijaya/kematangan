<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\StatistikPublikService;

class StatistikPublikController extends Controller
{
    public function __construct(private StatistikPublikService $svc) {}

    public function show(Request $request)
    {
        // 1) Paksa cache 'file' (aman di shared hosting/cPanel)
        config(['cache.default' => 'file']);

        // 2) Tentukan kegiatan_id:
        //    - prioritas: ?kegiatan_id=  (opsional, int > 0)
        //    - fallback:  config('penilaian.kegiatan_id') atau yang aktif di service
        $kegiatanId = (int) $request->integer('kegiatan_id')
            ?: (int) config('penilaian.kegiatan_id', 0);

        if ($kegiatanId <= 0) {
            // biarkan service menentukan kegiatan aktif sendiri
            $kegiatanId = 0;
        }

        // 3) TTL & cache-busting opsional via ?refresh=1
        $ttl      = max(60, (int) config('penilaian.cache_ttl', 600));
        $refresh  = $request->boolean('refresh');

        // 4) Cache key yang stabil + versioning
        //    Ganti versi jika format payload berubah agar cache lama tidak dipakai
        $ver      = 'v2'; // bump kalau struktur output diubah
        $cacheKey = "statpub:{$ver}:k{$kegiatanId}";

        try {
            if ($refresh) {
                Cache::store('file')->forget($cacheKey);
            }

            $data = Cache::store('file')->remember($cacheKey, $ttl, function () use ($kegiatanId) {
                // Service mengerjakan perhitungan yang benar:
                // - total_nilai_kabupaten = (Σ total_poin OPD terbaru) / total_opd (34)
                // - distribusi kategori sesuai rentang yang sudah ditetapkan
                return $this->svc->buildPayload($kegiatanId > 0 ? $kegiatanId : null);
            });

            // Hardening kecil: pastikan field minimal ada
            $data ??= [];
            $data['updated_at']  = $data['updated_at']  ?? now()->toIso8601String();
            $data['ringkasan']   = $data['ringkasan']   ?? [
                'total_nilai_kabupaten' => 0,
                'persentase_pengisian'  => 0,
                'total_opd_mengisi'     => 0,
                'total_opd'             => 0,
            ];
            $data['error']       = (bool) ($data['error'] ?? false);

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error('statpub_api_error', [
                'kegiatan_id' => $kegiatanId,
                'msg'         => $e->getMessage(),
            ]);

            // Fallback aman supaya front-end tetap hidup
            return response()->json([
                'error'      => true,
                'message'    => 'Gagal membangun statistik.',
                'updated_at' => now()->toIso8601String(),
                'kegiatan_id'=> $kegiatanId ?: null,
                'ringkasan'  => [
                    'total_nilai_kabupaten' => 0,
                    'persentase_pengisian'  => 0,
                    'total_opd_mengisi'     => 0,
                    'total_opd'             => 0,
                ],
                'donut_kategori' => ['labels' => [], 'data' => []],
                'bar_opd'        => ['labels' => [], 'data' => []],
                'bar_variabel'   => ['labels' => [], 'data' => []],
            ], 200);
        }
    }
}

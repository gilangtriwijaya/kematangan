<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\KegiatanPenilaian;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Binding ini mendukung DUA mode:
     *  - Jika parameter {kegiatan} bernilai numerik → ambil BY ID (untuk rute admin: edit, update, destroy, aktifkan).
     *  - Jika parameter {kegiatan} string (slug) → ambil BY SLUG dgn prioritas aktif, lalu fallback terbaru
     *    (untuk rute penilaian-kematangan).
     *
     * Opsional disambiguasi: ?kid=ID atau ?tahun=YYYY (untuk admin/verifikator).
     */
    public function boot(): void
    {
        Route::bind('kegiatan', function ($value) {
            // 1) Rute berbasis ID → langsung by ID
            if (is_numeric($value)) {
                return KegiatanPenilaian::findOrFail((int) $value);
            }

            // 2) Rute berbasis slug (string)
            $slug = (string) $value;
            $q = KegiatanPenilaian::query()->where('slug', $slug);

            // Disambiguasi opsional
            if ($id = request()->query('kid')) {
                return $q->where('id', (int) $id)->firstOrFail();
            }
            if ($tahun = request()->query('tahun')) {
                return $q->where('tahun', (int) $tahun)->firstOrFail();
            }

            // Default: pilih yang AKTIF lebih dulu, fallback ke terbaru
            $aktif = (clone $q)->where('is_aktif', 1)->orderByDesc('tahun')->orderByDesc('id')->first();
            if ($aktif) {
                return $aktif;
            }

            return $q->orderByDesc('tahun')->orderByDesc('id')->firstOrFail();
        });
    }
}

<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardApiController;
use App\Http\Controllers\DashboardPageController;
use App\Http\Controllers\DownloadBuktiDukungController;
use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\IndikatorDokumenController;
use App\Http\Controllers\KegiatanPenilaianController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PenilaianKematanganController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\TingkatPenilaianController;
use App\Http\Controllers\VariabelPenilaianController;
use App\Http\Controllers\VerifikasiController;
use App\Http\Controllers\Api\StatistikPublikController;
use App\Http\Controllers\SsoLoginController;

// root app (secara publik di domain jadi /kematangan/)
Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

// PUBLIC
Route::get('/statistik-publik', [StatistikPublikController::class, 'show'])->name('statistik-publik');

// SSO “entry” & callback (tanpa sso.auth)
Route::get('/sso/login', [SsoLoginController::class, 'redirectToSso'])->name('sso.login');
Route::get('/sso/callback', [SsoLoginController::class, 'callback'])->name('sso.callback');
Route::post('/logout', [SsoLoginController::class, 'logout'])->name('logout');
Route::post('/sso/back', [SsoLoginController::class, 'backToSso'])->name('sso.back');

// PROTECTED
Route::middleware(['sso.auth'])->group(function () {

    Route::get('/dashboard', [DashboardPageController::class, 'index'])->name('dashboard');

    // API Dashboard
    Route::get('/api/dash/summary', [DashboardApiController::class, 'summary'])->name('api.dash.summary');
    Route::get('/api/dash/detail',  [DashboardApiController::class, 'detail'])->name('api.dash.detail');
    // Manual SSO sync endpoint (POST) - require auth + role
    Route::post('/sso/sync', [\App\Http\Controllers\SsoSyncController::class, 'sync'])
        ->middleware(['auth', 'role:superadmin,bagor-admin']);

    // Profil
    Route::get('/profil',  [ProfilController::class, 'edit'])->name('profil.edit');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');

    // Bukti dukung (menu: bukti-dukung.*)
    Route::get('/bukti-dukung', [DownloadBuktiDukungController::class, 'index'])->name('bukti-dukung.index');
    Route::get('/bukti-dukung/download', [DownloadBuktiDukungController::class, 'download'])->name('bukti-dukung.download');

    // ===================== ADMIN (admin + superadmin) =====================
    Route::middleware(['sso.type:admin,superadmin'])->group(function () {

        /**
         * MENU butuh: kegiatan.*, variabel.*, tingkat.*, indikator.*
         * Path kamu saat ini: /kegiatan-penilaian, /variabel, /tingkat, /indikator
         * Kita pertahankan PATH, tapi NAME kita samakan ke menu.
         */

        // Kegiatan Penilaian (PATH: /kegiatan-penilaian) tapi NAME: kegiatan.*
        Route::resource('kegiatan-penilaian', KegiatanPenilaianController::class)
            ->except(['show'])
            ->names([
                'index'   => 'kegiatan.index',
                'create'  => 'kegiatan.create',
                'store'   => 'kegiatan.store',
                'edit'    => 'kegiatan.edit',
                'update'  => 'kegiatan.update',
                'destroy' => 'kegiatan.destroy',
            ]);

        Route::get('/kegiatan-penilaian/{kegiatan}/konfirmasi-hapus',
            [KegiatanPenilaianController::class, 'konfirmasiHapus'])
            ->whereNumber('kegiatan')
            ->name('kegiatan.konfirmasi-hapus');

        Route::post('/kegiatan-penilaian/{kegiatan_id}/aktifkan',
            [KegiatanPenilaianController::class, 'setAktif'])
            ->whereNumber('kegiatan_id')
            ->name('kegiatan.aktifkan');

        // Variabel (NAME: variabel.*)
        Route::resource('variabel', VariabelPenilaianController::class)
            ->except(['show'])
            ->names([
                'index'   => 'variabel.index',
                'create'  => 'variabel.create',
                'store'   => 'variabel.store',
                'edit'    => 'variabel.edit',
                'update'  => 'variabel.update',
                'destroy' => 'variabel.destroy',
            ]);

        // Tingkat (NAME: tingkat.*)
        Route::resource('tingkat', TingkatPenilaianController::class)
            ->except(['show'])
            ->names([
                'index'   => 'tingkat.index',
                'create'  => 'tingkat.create',
                'store'   => 'tingkat.store',
                'edit'    => 'tingkat.edit',
                'update'  => 'tingkat.update',
                'destroy' => 'tingkat.destroy',
            ]);

        // Indikator (NAME: indikator.*)
        Route::resource('indikator', IndikatorController::class)
            ->except(['show'])
            ->names([
                'index'   => 'indikator.index',
                'create'  => 'indikator.create',
                'store'   => 'indikator.store',
                'edit'    => 'indikator.edit',
                'update'  => 'indikator.update',
                'destroy' => 'indikator.destroy',
            ]);

        // Indikator Dokumen (kalau menu nggak pakai, biarin name konsisten aja)
        Route::resource('indikator-dokumen', IndikatorDokumenController::class)
            ->except(['show'])
            ->names([
                'index'   => 'indikator-dokumen.index',
                'create'  => 'indikator-dokumen.create',
                'store'   => 'indikator-dokumen.store',
                'edit'    => 'indikator-dokumen.edit',
                'update'  => 'indikator-dokumen.update',
                'destroy' => 'indikator-dokumen.destroy',
            ]);

        // Verifikasi (moved out: allow verifikator role as well)
        // NOTE: routes are defined later under a separate middleware group
    });

    // ===================== VERIFIKATOR (admin + superadmin + verifikator) =====================
    Route::middleware(['sso.type:admin,superadmin,verifikator'])->group(function () {
        Route::get('/verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('/verifikasi/{user}/detail', [VerifikasiController::class, 'detail'])
            ->whereNumber('user')->name('verifikasi.detail');
        Route::post('/verifikasi/{user}/{kegiatan_id}/simpan', [VerifikasiController::class, 'simpan'])
            ->whereNumber('user')->whereNumber('kegiatan_id')->name('verifikasi.simpan');
        Route::post('/verifikasi/jawaban/{jawaban}/approve', [VerifikasiController::class, 'approve'])
            ->whereNumber('jawaban')->name('verifikasi.approve');
        Route::post('/verifikasi/jawaban/{jawaban}/reject',  [VerifikasiController::class, 'reject'])
            ->whereNumber('jawaban')->name('verifikasi.reject');
    });

    // ===================== SUPERADMIN =====================
    Route::middleware(['sso.type:superadmin'])->group(function () {
        Route::get('/log', [LogAktivitasController::class, 'index'])->name('log.index');

        // Kalau menu kamu punya Kelola Pengguna:
        // Saat ini controller Users belum kamu lampirkan di Kematangan.
        // Nanti kalau ada, tinggal aktifkan:
        // Route::resource('users', UserController::class)->except(['show'])->names([
        //     'index' => 'users.index', 'create'=>'users.create','store'=>'users.store',
        //     'edit'=>'users.edit','update'=>'users.update','destroy'=>'users.destroy'
        // ]);
    });

    // ===================== OPD / VERIFIKATOR =====================
    // Menu VERIFIKATOR pakai: penilaian-kematangan.active
    Route::get('/penilaian-kematangan/aktif', function () {
        $aktif = \App\Models\KegiatanPenilaian::where('is_aktif', 1)->orderByDesc('tahun')->first();
        if (!$aktif) return redirect()->route('dashboard')->with('warning', 'Belum ada kegiatan aktif.');
        return redirect()->route('penilaian-kematangan.index', ['kegiatan' => $aktif->slug]);
    })->name('penilaian-kematangan.active');

    // Menu OPD pakai: penilaian-kematangan.index
    Route::get('/penilaian-kematangan/{kegiatan:slug}', [PenilaianKematanganController::class, 'index'])
        ->name('penilaian-kematangan.index');

    Route::post('/penilaian-kematangan/{kegiatan:slug}/{variabel}/simpan', [PenilaianKematanganController::class, 'simpan'])
        ->whereNumber('variabel')->name('penilaian-kematangan.simpan');

    Route::post('/ajax/upload-temp', [PenilaianKematanganController::class, 'uploadTemp'])->name('ajax.upload-temp');
});

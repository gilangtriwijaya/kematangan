<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatistikPublikController;
use App\Http\Controllers\SsoConsumeController;
use App\Http\Controllers\SsoApiController;

/**
 * Healthcheck ringan
 * GET /sistagor/api/ping
 */
Route::get('/ping', function () {
    return response()
        ->json(['pong' => true, 'ts' => now()->toIso8601String()])
        ->setPublic()
        ->setMaxAge(60)
        ->setSharedMaxAge(60);
})->name('statpub.ping');

/**
 * Endpoint statistik publik untuk halaman grafik
 * GET /sistagor/api/statistik-publik[?kegiatan=1&tahun=2025]
 * - throttle ditingkatkan agar cukup longgar untuk halaman publik
 */
Route::middleware('throttle:120,1')->get('/statistik-publik', [StatistikPublikController::class, 'show'])
    ->name('statpub.show');

/**
 * (Opsional) alias/kompat lama bila sebelumnya ada path /api/statistik
 * GET /sistagor/api/statistik → 301 ke /sistagor/api/statistik-publik
 */
Route::get('/statistik', function () {
    return redirect()->route('statpub.show', request()->query(), 301);
})->name('statpub.legacy');

// SSO consume endpoint (SSO -> Kematangan)
Route::post('/sso/consume', [SsoConsumeController::class, 'consume'])->name('sso.consume');

// Optional pull endpoints for SSO to fetch data
Route::get('/sso/users', [SsoApiController::class, 'index'])->name('sso.api.users');
Route::get('/sso/users/{sso_id}', [SsoApiController::class, 'show'])->name('sso.api.users.show');
Route::get('/sso/opds/sso/{sso_id}', [SsoApiController::class, 'opdsLookup'])->name('sso.api.opds.lookup');
// Fetch authoritative payload from SSO provider (requires SSO_PULL_TOKEN and SSO_SYNC_URL_TEMPLATE configured)
Route::get('/sso/fetch/{sso_id}', [SsoApiController::class, 'fetchRemote'])->name('sso.api.fetch');

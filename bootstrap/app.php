<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Laravel 12 membaca alias & group di sini, bukan dari App\Http\Kernel.
         * - Tambahkan middleware custom ke grup 'web'
         * - Daftarkan alias untuk middleware route
         */

        // masuk ke group 'web' (jalan pada semua halaman web)
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LogAktivitasMenuMiddleware::class,
        ]);

        // alias untuk dipakai di routes: 'role:admin,superadmin' dan 'log.menu'
        $middleware->alias([
            'role'     => \App\Http\Middleware\RoleMiddleware::class,
            'log.menu' => \App\Http\Middleware\LogAktivitasMenuMiddleware::class,
            'active_user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'sso.auth' => \App\Http\Middleware\EnsureSsoAuthenticated::class,
            'sso.type'    => \App\Http\Middleware\EnsureSsoType::class,
            'sso.role' => \App\Http\Middleware\EnsureSsoRole::class,
            'api.publik.key' => \App\Http\Middleware\ValidasiApiKeyPublik::class,
        ]);

        // Catatan: global default (TrustProxies, HandleCors, TrimStrings, dsb) sudah diatur oleh framework.
        // Tidak perlu dipindahkan manual ke sini kecuali kamu punya custom tambahan lain.
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('statpub:rebuild')
            ->hourly()
            ->appendOutputTo(storage_path('logs/statpub-rebuild.log'))
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // biarkan default
    })
    ->create();

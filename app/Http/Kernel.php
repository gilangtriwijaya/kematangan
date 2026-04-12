<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use App\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use App\Http\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;   // alias throttle
use Illuminate\Http\Middleware\SetCacheHeaders;       // opsional

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\LogAktivitasMenuMiddleware;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     */
    protected $middleware = [
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        'sso.auth' => \App\Http\Middleware\EnsureSsoAuthenticated::class,
    ];

    /**
     * Middleware groups untuk web dan api.
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,

            // Logging akses halaman/menu GET
            LogAktivitasMenuMiddleware::class,
        ],

        'api' => [
            // Aktifkan kalau Sanctum terpasang:
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * Alias middleware untuk dipakai pada route.
     * (Laravel 10+ gunakan $middlewareAliases, bukan $routeMiddleware)
     */
    protected $middlewareAliases = [
        'auth'          => Authenticate::class,
        'role'          => RoleMiddleware::class,
        'log.menu'      => LogAktivitasMenuMiddleware::class,
        'throttle'      => ThrottleRequests::class,  // supaya 'throttle:*' dikenali
        'cache.headers' => SetCacheHeaders::class,   // opsional
    ];
}

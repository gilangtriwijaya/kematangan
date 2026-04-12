<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\LogAktivitas;

class LogAktivitasMenuMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Jejak very light supaya tahu middleware hidup, tapi jangan spam.
        if (config('app.debug')) {
            Log::info('🔥 [Middleware] LogAktivitasMenu aktif (light)');
        }

        $response = $next($request);

        // 1) Hanya GET & user terautentikasi
        if ($request->method() !== 'GET' || !Auth::check()) {
            return $response;
        }

        // 2) Skip request yang jelas bukan page-view HTML
        // - AJAX/JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return $response;
        }

        $path = trim($request->path(), '/'); // contoh: 'dashboard' atau 'api/dash/summary'

        // 3) Skip path/prefix yang tidak perlu dicatat
        $skipExact = [
            '',                // root
            'login',
            'logout',
            'favicon.ico',
            'up',              // health check
        ];

        $skipPrefixes = [
            'api/',            // semua endpoint api-* yang kebetulan di web group
            'ajax/',           // upload-temp dsb
            'storage/',        // file publik
            'vendor/', 'debugbar', 'telescope', 'horizon',
            '__admin', 'maintenance', // util internal
        ];

        if (in_array($path, $skipExact, true) || Str::startsWith($path, $skipPrefixes)) {
            return $response;
        }

        // 4) Skip juga kalau nama route bertipe API
        $route = $request->route();
        $routeName = $route?->getName();
        if ($routeName && Str::startsWith($routeName, ['api.', 'ajax.'])) {
            return $response;
        }

        // 5) Dedup: hindari spam log untuk URL yang sama dalam 5 detik
        $userId = Auth::id();
        $full   = $request->fullUrl();
        $key    = 'logmenu:' . $userId . ':' . md5($full);

        if (!Cache::add($key, 1, now()->addSeconds(5))) {
            // Sudah dicatat baru saja, lewati
            return $response;
        }

        // 6) Lakukan pencatatan yang efisien & aman
        try {
            $aksi = $routeName ?: $path;

            Log::info('✅ [GET MENU] ' . $full);

            $log = LogAktivitas::create([
                'user_id'     => $userId,
                'role'        => Auth::user()->role ?? '-',
                'aksi'        => $aksi,
                'keterangan'  => 'Mengakses halaman: ' . $aksi,
                'url'         => $full,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'method'      => $request->method(),
                'status_code' => $response->getStatusCode(),
                // biarkan timestamps model yang set created_at/updated_at
            ]);

            Log::info('📝 [Tersimpan ke DB] ID: ' . $log->id);
        } catch (\Throwable $e) {
            Log::error('❌ [Gagal Simpan Log Menu] ' . $e->getMessage());
        }

        return $response;
    }
}

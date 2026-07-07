<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatistikPublikService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StatistikPublikController extends Controller
{
    public function __construct(private StatistikPublikService $svc) {}

    public function show(Request $request): JsonResponse
    {
        config(['cache.default' => 'file']);

        $kegiatanId = (int) $request->integer('kegiatan_id') ?: (int) config('penilaian.kegiatan_id', 0);
        $ttl = max(60, (int) config('penilaian.cache_ttl', 600));
        $refresh = $request->boolean('refresh');
        $cacheKey = $this->svc->cacheKey($kegiatanId > 0 ? $kegiatanId : null);
        $cacheHit = Cache::store('file')->has($cacheKey);

        try {
            if ($refresh) {
                Cache::store('file')->forget($cacheKey);
                $cacheHit = false;
            }

            $payload = Cache::store('file')->remember($cacheKey, $ttl, function () use ($kegiatanId) {
                return [
                    'success' => true,
                    'app' => config('app.code', config('app.name')),
                    'resource' => 'statistik',
                    'generated_at' => now()->toIso8601String(),
                    'cache_ttl' => max(60, (int) config('penilaian.cache_ttl', 600)),
                    'cache_hit' => false,
                    'data' => $this->svc->buildPayload($kegiatanId > 0 ? $kegiatanId : null),
                ];
            });

            $payload['cache_hit'] = $cacheHit;
            $request->attributes->set('statpub.cache_hit', $cacheHit);

            return response()->json($payload);

        } catch (\Throwable $e) {
            Log::error('statpub_api_error', [
                'kegiatan_id' => $kegiatanId,
                'msg'         => $e->getMessage(),
            ]);
            $request->attributes->set('statpub.cache_hit', false);

            return response()->json([
                'success' => false,
                'app' => config('app.code', config('app.name')),
                'resource' => 'statistik',
                'message' => 'Layanan sementara tidak tersedia',
                'generated_at' => now()->toIso8601String(),
            ], 503);
        }
    }

    public function health(Request $request): JsonResponse
    {
        $cacheKey = $this->svc->cacheKey((int) config('penilaian.kegiatan_id', 0));

        return response()->json([
            'success' => true,
            'app' => config('app.code', config('app.name')),
            'cache_valid' => Cache::store('file')->has($cacheKey),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function ping(): JsonResponse
    {
        return response()
            ->json(['pong' => true, 'ts' => now()->toIso8601String()])
            ->setPublic()
            ->setMaxAge(60)
            ->setSharedMaxAge(60);
    }

    public function legacyRedirect(Request $request)
    {
        return redirect()->route('statpub.show', $request->query(), 301);
    }
}

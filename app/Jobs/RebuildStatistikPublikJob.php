<?php

namespace App\Jobs;

use App\Services\StatistikPublikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RebuildStatistikPublikJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(StatistikPublikService $service): void
    {
        // 1. Rebuild local cache
        $service->rebuildCache();

        // 2. Send webhook invalidate to website if configured
        $this->kirimWebhook($service);
    }

    private function kirimWebhook(StatistikPublikService $service): void
    {
        $url = config('app.website_invalidate_url');
        $secret = config('app.website_invalidate_secret');

        if (! $url || ! $secret) {
            return;
        }

        try {
            $payload = [
                'app' => config('app.code', config('app.name')),
                'resource' => 'statistik',
                'key' => $service->cacheKey(),
            ];

            Http::timeout(10)
                ->retry(2, 500)
                ->withHeaders([
                    'X-Invalidate-Secret' => $secret,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

        } catch (\Throwable $e) {
            Log::warning('statpub_webhook_gagal', ['url' => $url, 'msg' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('statpub_rebuild_job_gagal', ['msg' => $exception->getMessage()]);
    }
}

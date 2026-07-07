<?php

namespace App\Console\Commands;

use App\Services\StatistikPublikService;
use Illuminate\Console\Command;

class StatpubRebuild extends Command
{
    protected $signature = 'statpub:rebuild {--force : Paksa rebuild meski cache masih valid}';
    protected $description = 'Rebuild cache statistik publik';

    public function handle(StatistikPublikService $service): int
    {
        $this->info('Rebuilding cache statistik publik...');
        $start = microtime(true);

        try {
            $service->rebuildCache();
            $ms = (int) ((microtime(true) - $start) * 1000);
            $this->info("✓ Selesai dalam {$ms}ms. Cache key: {$service->cacheKey()}");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("✗ Gagal: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

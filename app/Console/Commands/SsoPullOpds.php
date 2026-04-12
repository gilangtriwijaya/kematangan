<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SsoClient;

class SsoPullOpds extends Command
{
    protected $signature = 'sso:pull-opds {--apply : persist OPD names into sso_opd_mappings} {--per_page=200 : results per page} {--updated_after= : only fetch updated after timestamp/ISO8601}';
    protected $description = 'Pull OPD list from SSO and optionally persist names into sso_opd_mappings for mapping.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $perPage = (int) $this->option('per_page');
        $updatedAfter = $this->option('updated_after') ?: null;

        $client = app(SsoClient::class);

        $page = 1;
        $total = 0;
        while (true) {
            $this->info("Fetching page {$page}...");
            try {
                $resp = $client->fetchOpdsPage($page, $perPage, $updatedAfter);
            } catch (\Throwable $e) {
                $this->error('Fetch failed: ' . $e->getMessage());
                return 2;
            }

            $rows = $resp['data'] ?? $resp;
            if (empty($rows) || count($rows) === 0) break;

            foreach ($rows as $r) {
                $ssoId = $r['id'] ?? null;
                $name = $r['name'] ?? $r['opd_name'] ?? $r['title'] ?? null;
                if (! $ssoId) continue;
                $this->line(" - sso_opd_id={$ssoId} name=" . ($name ?? '[no-name]'));
                if ($apply && $name) {
                    DB::table('sso_opd_mappings')->insertOrIgnore([
                        'sso_opd_id' => (int)$ssoId,
                        'local_user_id' => null,
                        'opd_name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $total++;
            }

            $page++;
        }

        $this->info("Done. processed={$total}");
        return 0;
    }
}

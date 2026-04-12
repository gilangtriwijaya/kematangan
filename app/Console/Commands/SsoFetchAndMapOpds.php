<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\SsoClient;

class SsoFetchAndMapOpds extends Command
{
    protected $signature = 'sso:fetch-map-opds {--apply : apply guessed mappings} {--threshold=70 : similarity threshold percent}';
    protected $description = 'Fetch OPD names from SSO for unmapped SSO OPD ids and attempt to map to local OPD users by name similarity.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $threshold = (int) $this->option('threshold');

        $ssoIds = DB::table('sso_allowed_opds')
            ->whereNotNull('opd_sso_id')
            ->distinct()
            ->pluck('opd_sso_id')
            ->map(fn($v)=>(int)$v)
            ->values()
            ->all();

        $mapped = DB::table('sso_opd_mappings')->pluck('sso_opd_id')->map(fn($v)=>(int)$v)->all();

        $candidates = array_filter($ssoIds, fn($id) => ! in_array($id, $mapped, true));
        if (empty($candidates)) {
            $this->info('No unmapped SSO OPD ids found.');
            return 0;
        }

        $client = app(SsoClient::class);

        foreach ($candidates as $ssoId) {
            $this->info("Evaluating SSO OPD id={$ssoId}");
            try {
                $payload = $client->fetchOpd($ssoId);
            } catch (\Throwable $e) {
                $this->line(' - Failed fetching from SSO: ' . $e->getMessage());
                continue;
            }

            // Try common keys for name
            $ssoName = $payload['name'] ?? $payload['opd_name'] ?? $payload['title'] ?? null;
            if (empty($ssoName)) {
                $this->line(' - No name available in SSO response.');
                continue;
            }

            $this->line(" - SSO name: {$ssoName}");

            $locals = DB::table('users')->whereRaw('LOWER(role)=?', ['opd'])->whereNotNull('opd_name')->select('id','opd_name')->get();
            $best = null;
            foreach ($locals as $l) {
                $a = strtolower(trim($ssoName));
                $b = strtolower(trim($l->opd_name));
                similar_text($a, $b, $perc);
                if ($best === null || $perc > $best['score']) {
                    $best = ['id' => $l->id, 'name' => $l->opd_name, 'score' => $perc];
                }
            }

            if ($best && $best['score'] >= $threshold) {
                $this->info(sprintf(' - Candidate match: local_user_id=%d name="%s" score=%.2f%%', $best['id'], $best['name'], $best['score']));
                if ($apply) {
                    DB::table('sso_opd_mappings')->insertOrIgnore([
                        'sso_opd_id' => $ssoId,
                        'local_user_id' => $best['id'],
                        'opd_name' => $ssoName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info('   -> applied mapping');
                }
            } else {
                $this->line(' - No sufficiently similar local OPD found (best: '.($best['score'] ?? 0).'%)');
            }
        }

        $this->info('Done');
        return 0;
    }
}

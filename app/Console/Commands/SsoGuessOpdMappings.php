<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SsoGuessOpdMappings extends Command
{
    protected $signature = 'sso:guess-opd-mappings {--apply : apply guessed mappings} {--threshold=80 : similarity threshold percent}';
    protected $description = 'Guess SSO OPD -> local OPD mappings by name similarity (dry-run by default)';

    public function handle()
    {
        $apply = $this->option('apply');
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

        foreach ($candidates as $ssoId) {
            $this->info("Evaluating SSO OPD id={$ssoId}");
            // try to find a stored name in sso_opd_mappings (maybe previous record)
            $nameRow = DB::table('sso_opd_mappings')->where('sso_opd_id', $ssoId)->first();
            $ssoName = $nameRow->opd_name ?? null;
            if (empty($ssoName)) {
                $this->line(" - No opd_name available for sso_opd_id={$ssoId}; cannot guess by name.");
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SsoReconcileOpdMappings extends Command
{
    protected $signature = 'sso:reconcile-opd-mappings {--dry-run : Do not persist changes} {--from-sso-id= : Copy mappings from owner with this SSO id} {--to-user-id= : Copy mappings to this local user id}';

    protected $description = 'Copy sso_allowed_opds from owner users to local users that match by email';

    public function handle()
    {
        $dry = $this->option('dry-run');

        $fromSso = $this->option('from-sso-id');
        $toUser = $this->option('to-user-id');

        if ($fromSso && $toUser) {
            // Targeted copy: find owner by sso_user_id
            $owner = DB::table('users')->where('sso_user_id', (int)$fromSso)->first();
            if (! $owner) {
                $this->error('Owner with sso_user_id ' . $fromSso . ' not found');
                return 1;
            }
            $owners = collect([(object)['user_id' => $owner->id, 'app_code' => 'kematangan']]);
        } else {
            $owners = DB::table('sso_allowed_opds')
                ->select('user_id','app_code')
                ->distinct()
                ->get();
        }

        $summary = ['processed' => 0, 'copied' => 0];

        foreach ($owners as $o) {
            $ownerUser = DB::table('users')->where('id', $o->user_id)->first();
            if (! $ownerUser) continue;

            $mappings = DB::table('sso_allowed_opds')
                ->where('user_id', $o->user_id)
                ->where('app_code', $o->app_code)
                ->pluck('opd_id')
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            if (empty($mappings)) continue;

            // If --to-user-id provided, copy specifically to that user; otherwise use email match
            if ($fromSso && $toUser) {
                $targets = DB::table('users')->where('id', (int)$toUser)->get(['id']);
            } else {
                if (empty($ownerUser->email)) continue;
                $targets = DB::table('users')
                    ->where('email', $ownerUser->email)
                    ->where('id', '!=', $ownerUser->id)
                    ->get(['id']);
            }

            foreach ($targets as $t) {
                $summary['processed']++;
                foreach ($mappings as $opdId) {
                    $exists = DB::table('sso_allowed_opds')
                        ->where('user_id', $t->id)
                        ->where('app_code', $o->app_code)
                        ->where('opd_id', $opdId)
                        ->exists();
                    if (! $exists) {
                        $this->info(($dry ? '[DRY] ' : '') . "Copying opd_id={$opdId} for owner={$o->user_id} -> target={$t->id}");
                        if (! $dry) {
                            DB::table('sso_allowed_opds')->insert([
                                'user_id' => $t->id,
                                'app_code' => $o->app_code,
                                'opd_id' => $opdId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        $summary['copied']++;
                    }
                }
            }
        }

        $this->info('Reconcile finished: ' . json_encode($summary));
        return 0;
    }
}

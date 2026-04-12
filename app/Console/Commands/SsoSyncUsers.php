<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SsoSyncService;

class SsoSyncUsers extends Command
{
    protected $signature = 'sso:sync-users {--user= : SSO user id to sync} {--file= : JSON file with payload to apply} {--limit= : limit number of users when syncing all} {--dry-run : do not persist changes}';

    protected $description = 'Sync users from SSO (manual or from file)';

    public function handle(SsoSyncService $svc)
    {
        $file = $this->option('file');
        $user = $this->option('user');
        $dry = $this->option('dry-run');
        $limit = $this->option('limit') ? (int)$this->option('limit') : null;

        if ($file) {
            if (!file_exists($file)) {
                $this->error('File not found: '.$file);
                return 1;
            }
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) {
                $this->error('Invalid JSON in file');
                return 1;
            }
            if ($dry) {
                $this->info('Dry-run: would apply payload for sso_user_id=' . ($json['user']['id'] ?? 'n/a'));
                return 0;
            }
            $userModel = $svc->applyPayload($json);
            $this->info('Applied payload -> local user id='.$userModel->id);
            return 0;
        }

        if ($user) {
            try {
                $res = $svc->syncUserBySsoId($user, $dry);
                if ($dry) {
                    $this->info('Dry-run result: ' . json_encode($res));
                } else {
                    $this->info('Synced user id='.$res->id);
                }
                return 0;
            } catch (\Throwable $e) {
                $this->error('Error: '.$e->getMessage());
                return 1;
            }
        }

        // default: sync all
        $results = $svc->syncAll($limit, $dry);
        $this->info('Sync finished: ' . count($results) . ' entries processed');
        return 0;
    }
}

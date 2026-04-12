<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = app(\App\Services\SsoSyncService::class);
$limit = getenv('LIMIT') ?: null;
echo "Starting full SSO resync (limit=" . ($limit ?: 'none') . ")...\n";
$results = $svc->syncAll($limit ? (int)$limit : null, false);
$success = 0;
$errors = 0;
foreach ($results as $localId => $res) {
    if (is_array($res) && array_key_exists('error', $res)) {
        echo "Error resync local_id={$localId}: {$res['error']}\n";
        $errors++;
        continue;
    }

    if ($res instanceof \App\Models\User) {
        echo "Resynced local_id={$localId} -> user_id={$res->id} sso_user_id={$res->sso_user_id}\n";
        $success++;
    } else {
        // unexpected result
        echo "Resync local_id={$localId} -> result=" . (is_scalar($res) ? (string)$res : json_encode($res)) . "\n";
    }
}

echo "Finished. success={$success} errors={$errors}\n";

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SsoSyncService;

$payload = [
    'user' => [
        'id' => 42,
        'username' => 'verifikatorglobal',
        'name' => 'Verifikator Global',
        'email' => 'verifglobal@anambaskab.go.id',
        'user_type_id' => 3,
        'opd_id' => null,
        'opd_code' => null,
        'opd_unit_id' => null,
        'opd_unit_code' => null,
        'is_active' => 1,
        'app_role' => 'verifikator global',
        // application sent slug with hyphen
        'app_role_slug' => 'verifikator-global',
        'is_opd_locked' => true,
        'allowed_opd_ids' => [33,34,35],
    ],
    // override app to kematangan so allowed_opd entries are applied to this app
    'app' => 'kematangan',
    'ticket' => '2kwyi1w-example',
];

echo "Applying SSO payload (app=kematangan) for sso_user_id={$payload['user']['id']}...\n";
$svc = app(SsoSyncService::class);
try {
    $user = $svc->applyPayload($payload);
    echo "Applied -> local user id={$user->id} sso_user_id={$user->sso_user_id} role={$user->role} sso_app_role_slug={$user->sso_app_role_slug}\n";
    $rows = \App\Models\SsoAllowedOpd::where('user_id', $user->id)->where('app_code','kematangan')->get();
    if ($rows->isEmpty()) {
        echo "No SsoAllowedOpd rows for kematangan created.\n";
    } else {
        echo "SsoAllowedOpd for kematangan:\n";
        foreach ($rows as $r) echo " - id={$r->id} opd_id={$r->opd_id} opd_sso_id={$r->opd_sso_id}\n";
    }
} catch (\Throwable $e) {
    echo "Error applying payload: {$e->getMessage()}\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SsoAllowedOpd;

$svc = app(\App\Services\SsoSyncService::class);
$users = User::whereNotNull('sso_user_id')->get();
foreach ($users as $u) {
    $rows = SsoAllowedOpd::where('user_id', $u->id)->where('app_code','kematangan')->get();
    $ssoOpds = $rows->pluck('opd_sso_id')->filter()->unique()->values()->all();
    $payload = [
        'ticket' => 'resync-'.$u->sso_user_id.'-'.time(),
        'app' => 'kematangan',
        'timestamp' => date('c'),
        'user' => [
            'id' => $u->sso_user_id,
            'username' => $u->email,
            'name' => $u->name,
            'email' => $u->email,
            'app_role_slug' => $u->sso_app_role_slug ?? $u->role,
            'is_opd_locked' => ! empty($ssoOpds),
            'allowed_opd_ids_by_app' => ['kematangan' => $ssoOpds],
        ],
    ];

    try {
        $res = $svc->applyPayload($payload);
        echo "Resynced sso_user_id={$u->sso_user_id} -> local_user_id={$res->id}\n";
    } catch (Throwable $e) {
        echo "Error resync sso_user_id={$u->sso_user_id}: {$e->getMessage()}\n";
    }
}

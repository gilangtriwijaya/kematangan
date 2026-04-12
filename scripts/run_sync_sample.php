<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payload = json_decode(file_get_contents(__DIR__.'/../storage/sso-samples/test-user-9001.json'), true);
$svc = app(\App\Services\SsoSyncService::class);
$user = $svc->applyPayload($payload);
echo "Applied payload -> local user id={$user->id} sso_user_id={$user->sso_user_id}\n";
$rows = \App\Models\SsoAllowedOpd::where('user_id', $user->id)->where('app_code','kematangan')->get();
foreach ($rows as $r) {
    echo " SsoAllowedOpd id={$r->id} opd_id={$r->opd_id} opd_sso_id={$r->opd_sso_id}\n";
}

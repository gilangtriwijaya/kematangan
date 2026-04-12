<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Configure default values
$ssoId = getenv('VERIFIKATOR_SSO_ID') ?: 99999;
$email = getenv('VERIFIKATOR_EMAIL') ?: 'verifikator.global@example.test';
$name = getenv('VERIFIKATOR_NAME') ?: 'Verifikator Global';

// Find existing by sso_user_id or email
$user = User::where('sso_user_id', $ssoId)->orWhere('email', $email)->first();
if (! $user) {
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt(str()->random(40)),
        'role' => 'verifikator',
        'sso_user_id' => $ssoId,
        'sso_app_role_slug' => 'verifikatorglobal',
    ]);
    echo "Created user id={$user->id} sso_user_id={$user->sso_user_id}\n";
} else {
    $user->name = $name;
    $user->email = $email;
    $user->role = 'verifikator';
    $user->sso_user_id = $ssoId;
    $user->sso_app_role_slug = 'verifikatorglobal';
    $user->save();
    echo "Updated user id={$user->id} sso_user_id={$user->sso_user_id}\n";
}

// Ensure sso_allowed_opds exists if any mapping present for global role
// (no-op here; local_resync_from_db will re-apply allowed opds)

// Run local resync to apply payload derived from DB using SsoSyncService
echo "Running local resync...\n";
$svc = app(\App\Services\SsoSyncService::class);
$rows = \App\Models\SsoAllowedOpd::where('user_id', $user->id)->where('app_code','kematangan')->get();
$ssoOpds = $rows->pluck('opd_sso_id')->filter()->unique()->values()->all();
$payload = [
    'ticket' => 'resync-'.$user->sso_user_id.'-'.time(),
    'app' => 'kematangan',
    'timestamp' => date('c'),
    'user' => [
        'id' => $user->sso_user_id,
        'username' => $user->email,
        'name' => $user->name,
        'email' => $user->email,
        'app_role_slug' => $user->sso_app_role_slug ?? $user->role,
        'is_opd_locked' => ! empty($ssoOpds),
        'allowed_opd_ids_by_app' => ['kematangan' => $ssoOpds],
    ],
];

try {
    $res = $svc->applyPayload($payload);
    echo "Resynced sso_user_id={$user->sso_user_id} -> local_user_id={$res->id}\n";
} catch (Throwable $e) {
    echo "Error resync sso_user_id={$user->sso_user_id}: {$e->getMessage()}\n";
}

echo "Done.\n";

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SsoAllowedOpd;

if ($argc < 2) {
    echo "Usage: php scripts/check_user.php <id>\n";
    echo " <id> can be local user id or sso_user_id.\n";
    exit(1);
}

$id = $argv[1];

$user = User::find($id);
if (! $user) {
    $user = User::where('sso_user_id', $id)->first();
}

if (! $user) {
    echo "User with id or sso_user_id={$id} not found\n";
    exit(1);
}

echo "Local user:\n";
echo " id={$user->id} sso_user_id={$user->sso_user_id} name={$user->name} email={$user->email} role={$user->role} sso_app_role_slug={$user->sso_app_role_slug}\n";

echo "\nSsoAllowedOpd rows for user {$user->id} (app=kematangan):\n";
$rows = SsoAllowedOpd::where('user_id', $user->id)->where('app_code','kematangan')->get();
if ($rows->isEmpty()) {
    echo " (none)\n";
} else {
    foreach ($rows as $r) {
        echo " - id={$r->id} opd_id=" . ($r->opd_id ?? '') . " opd_sso_id={$r->opd_sso_id}\n";
    }
}

echo "\nResolved allowed local OPD user IDs (SsoOpdService):\n";
$svc = app(\App\Services\SsoOpdService::class);
try {
    $allowed = $svc->getAllowedOpdUserIds($user, 'kematangan');
    if (is_array($allowed)) {
        echo "Allowed local user IDs: " . implode(',', $allowed) . "\n";
    } elseif (is_null($allowed)) {
        echo "Allowed: GLOBAL (no restriction)\n";
    } else {
        echo "Allowed: (unknown)\n";
    }
} catch (\Throwable $e) {
    echo "Error resolving allowed OPDs: " . $e->getMessage() . "\n";
}

echo "\nRoleOpdMapping for relevant roles (user role and 'global'):\n";
$roles = [$user->role, 'verifikator', 'verifikatorglobal', 'verifikator-global', 'global'];
$maps = \Illuminate\Support\Facades\DB::table('role_opd_mappings')->whereIn('role_name', $roles)->get();
foreach ($maps as $m) {
    echo " - id={$m->id} role_name={$m->role_name} opd_sso_id={$m->opd_sso_id} apply_to={$m->apply_to}\n";
}

echo "\nDone.\n";

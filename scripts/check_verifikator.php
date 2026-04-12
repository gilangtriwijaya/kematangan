<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SsoAllowedOpd;
use App\Models\SsoOpdMapping;
use App\Models\RoleOpdMapping;

$users = User::where('role','like','%verifikator%')
    ->orWhere('sso_app_role_slug','like','%verifikat%')
    ->get();

if ($users->isEmpty()) {
    echo "No verifikator users found\n";
    exit(0);
}

foreach ($users as $u) {
    echo "USER: id={$u->id} name={$u->name} email={$u->email} role={$u->role} sso_user_id={$u->sso_user_id} sso_app_role_slug={$u->sso_app_role_slug}\n";
    $rows = SsoAllowedOpd::where('user_id', $u->id)->where('app_code','kematangan')->get();
    echo " sso_allowed_opds:\n";
    foreach ($rows as $r) {
        echo "  - id={$r->id} opd_id={$r->opd_id} opd_sso_id={$r->opd_sso_id} app_code={$r->app_code}\n";
        $maps = SsoOpdMapping::where('opd_sso_id', $r->opd_sso_id)->get();
        foreach ($maps as $m) {
            echo "    -> mapping id={$m->id} opd_sso_id={$m->opd_sso_id} local_opd_user_id={$m->local_opd_user_id}\n";
        }
    }
    $roleRows = RoleOpdMapping::where('role_slug', $u->sso_app_role_slug)->get();
    echo " role_opd_mappings:\n";
    foreach ($roleRows as $rr) {
        echo "  - id={$rr->id} role_slug={$rr->role_slug} allowed_sso_opd_ids={$rr->allowed_sso_opd_ids}\n";
    }
    echo "---\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('users')->select('id','name','email','role','sso_user_id','sso_app_role_slug','sso_last_synced_at')->whereNotNull('sso_user_id')->get();
if ($rows->isEmpty()) {
    echo "No users with sso_user_id\n";
    exit(0);
}
foreach ($rows as $r) {
    echo "id={$r->id} sso_user_id={$r->sso_user_id} name={$r->name} email={$r->email} role={$r->role} sso_app_role_slug={$r->sso_app_role_slug} sso_last_synced_at={$r->sso_last_synced_at}\n";
}

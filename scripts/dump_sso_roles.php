<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('users')->select('sso_app_role_slug', DB::raw('count(*) as cnt'))->whereNotNull('sso_app_role_slug')->groupBy('sso_app_role_slug')->get();
if ($rows->isEmpty()) {
    echo "No sso_app_role_slug values found\n";
    exit(0);
}
foreach ($rows as $r) {
    echo "{$r->sso_app_role_slug} ({$r->cnt})\n";
}

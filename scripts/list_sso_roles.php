<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('users')->select('sso_app_role_slug')->distinct()->get();
echo "Distinct sso_app_role_slug values:\n";
foreach ($rows as $r) {
    echo " - " . ($r->sso_app_role_slug ?? '<NULL>') . "\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("SHOW COLUMNS FROM role_opd_mappings");
echo "Columns for role_opd_mappings:\n";
foreach ($cols as $c) echo " - {$c->Field}\n";

$rows = DB::table('role_opd_mappings')->get();
if ($rows->isEmpty()) {
    echo "No role_opd_mappings rows found\n";
    exit(0);
}
echo "Rows:\n";
foreach ($rows as $r) {
    // print all properties
    $props = [];
    foreach (get_object_vars($r) as $k => $v) $props[] = "$k=$v";
    echo implode(' ', $props) . "\n";
}

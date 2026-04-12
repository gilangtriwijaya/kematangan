<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('sso_opd_mappings')->get();
if ($rows->isEmpty()) {
    echo "(no sso_opd_mappings rows)\n";
    exit(0);
}

foreach ($rows as $r) {
    echo sprintf("id=%d sso_opd_id=%s local_user_id=%s opd_name=%s\n", $r->id, $r->sso_opd_id ?? 'NULL', $r->local_user_id ?? 'NULL', $r->opd_name ?? '');
}

echo "Done\n";

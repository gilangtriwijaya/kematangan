<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RoleOpdMapping;

// role_opd_mappings stores role names in `role_name`
$rows = RoleOpdMapping::where('role_name','like','%verifikat%')->get();
if ($rows->isEmpty()) {
    echo "No role_opd_mappings for verifikator-role found\n";
    exit(0);
}
foreach ($rows as $r) {
    echo "id={$r->id} role_slug={$r->role_slug} allowed_sso_opd_ids={$r->allowed_sso_opd_ids}\n";
}

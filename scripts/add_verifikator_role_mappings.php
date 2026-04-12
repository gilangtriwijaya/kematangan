<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RoleOpdMapping;

// Add role_opd_mappings for verifikator roles if not present.
$roles = ['verifikatorglobal','verifikator-global','verifikator'];
$added = 0;
foreach ($roles as $r) {
    $exists = RoleOpdMapping::where('role_name', $r)->first();
    if ($exists) continue;
    // create a permissive mapping with opd_sso_id = 0 meaning global (no specific OPD)
    RoleOpdMapping::create([
        'rule_id' => 'auto_verifikator_mapping_v1',
        'role_name' => $r,
        'opd_sso_id' => 0,
        'apply_to' => 'data_read,ui_display',
        'effective_from' => now(),
        'created_by' => 1,
    ]);
    echo "Added role_opd_mapping for role_name={$r}\n";
    $added++;
}
if ($added === 0) echo "No new mappings added\n";

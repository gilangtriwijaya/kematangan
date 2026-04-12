<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SsoAllowedOpd;
use Illuminate\Support\Facades\DB;

echo "=== User 42 ===\n";
$u = User::find(42);
if (!$u) { echo "User 42 not found\n"; exit(1); }
print_r($u->toArray());

echo "\n=== SsoAllowedOpd rows ===\n";
$rows = SsoAllowedOpd::where('user_id',42)->get();
foreach ($rows as $r) echo "id={$r->id} app={$r->app_code} opd_id={$r->opd_id} opd_sso_id={$r->opd_sso_id}\n";

echo "\n=== RoleOpdMapping table ===\n";
$m = DB::table('role_opd_mappings')->get();
foreach ($m as $row) echo json_encode($row) . "\n";

echo "\n=== SsoOpdService resolution ===\n";
$svc = app(\App\Services\SsoOpdService::class);
try { $allowed = $svc->getAllowedOpdUserIds($u,'kematangan'); var_export($allowed); echo "\n"; } catch (\Throwable $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n=== EnsureSsoType mapRole check for user role and sso payload ===\n";
$es = app(\App\Http\Middleware\EnsureSsoType::class);
$mappedLocal = (function($es,$v){ $m = new ReflectionMethod(get_class($es),'mapRole'); $m->setAccessible(true); return $m->invoke($es,$v); })($es, strtolower(trim($u->role)));
echo "Local role mapping: {$u->role} -> {$mappedLocal}\n";

echo "\n=== Recent SSO consume log lines (last 200 lines) ===\n";
echo shell_exec('tail -n 200 storage/logs/laravel.log | grep "SSO consume request\|sso:sync applied\|SSO payload applied during login\|SSO user not found" -n --line-buffered');

echo "\nDone.\n";

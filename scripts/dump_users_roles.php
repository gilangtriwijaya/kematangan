<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
$users = User::select('id','name','email','role','sso_app_role_slug','sso_user_id')->get();
foreach ($users as $u) {
    echo "id={$u->id}	name={$u->name}	email={$u->email}	role={$u->role}	sso_app_role_slug={$u->sso_app_role_slug}	sso_user_id={$u->sso_user_id}\n";
}

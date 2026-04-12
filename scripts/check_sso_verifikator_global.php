<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\SsoClient;

echo "Checking for users (local name and SSO) matching 'verifikator global'...\n";

$found = false;

// 1) Check local users by name (simple check)
$local = User::whereRaw('LOWER(name) LIKE ?', ['%verifikator global%'])
    ->orWhereRaw('LOWER(name) LIKE ?', ['%verifikatorglobal%'])
    ->get();

if (! $local->isEmpty()) {
    $found = true;
    echo "Local users with name like 'verifikator global':\n";
    foreach ($local as $u) {
        echo " - id={$u->id} name={$u->name} email={$u->email} sso_user_id={$u->sso_user_id}\n";
    }
} else {
    echo "No local users with name like 'verifikator global'.\n";
}

// 2) Check users that have an SSO id by fetching SSO payload and matching name
$ssoClient = new SsoClient();
$ssoMatches = [];

$usersWithSso = User::whereNotNull('sso_user_id')->get();
foreach ($usersWithSso as $u) {
    try {
        $payload = $ssoClient->fetchUser($u->sso_user_id);
    } catch (\Throwable $e) {
        // skip if SSO call fails for this user
        continue;
    }

    // Normalize various possible fields that may contain name
    $ssoName = '';
    if (isset($payload['name'])) $ssoName = $payload['name'];
    elseif (isset($payload['full_name'])) $ssoName = $payload['full_name'];
    elseif (isset($payload['displayName'])) $ssoName = $payload['displayName'];

    if ($ssoName !== '') {
        $low = mb_strtolower($ssoName);
        if (mb_strpos($low, 'verifikator global') !== false || mb_strpos($low, 'verifikatorglobal') !== false) {
            $found = true;
            $ssoMatches[] = [
                'local_id' => $u->id,
                'local_name' => $u->name,
                'sso_user_id' => $u->sso_user_id,
                'sso_name' => $ssoName,
                'email' => $u->email,
            ];
        }
    }
}

if (! empty($ssoMatches)) {
    echo "Users whose SSO record contains 'verifikator global':\n";
    foreach ($ssoMatches as $m) {
        echo " - local_id={$m['local_id']} local_name={$m['local_name']} sso_user_id={$m['sso_user_id']} sso_name={$m['sso_name']} email={$m['email']}\n";
    }
} else {
    echo "No users found via SSO whose name contains 'verifikator global'.\n";
}

if (! $found) {
    echo "Overall: no matching user found.\n";
    exit(1);
}

echo "Done.\n";

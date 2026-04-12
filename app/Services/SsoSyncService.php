<?php

namespace App\Services;

use App\Models\User;
use App\Models\SsoAllowedOpd;
use Illuminate\Support\Facades\Log;

class SsoSyncService
{
    protected SsoClient $client;

    public function __construct(SsoClient $client)
    {
        $this->client = $client;
    }

    /** Apply a full SSO payload (same shape as /api/sso/consume) into local DB. */
    public function applyPayload(array $payload): User
    {
        $ssoUser = $payload['user'] ?? [];
        $ssoUserId = $ssoUser['id'] ?? null;
        if (! $ssoUserId) {
            throw new \InvalidArgumentException('payload missing user.id');
        }

        $appCode = $payload['app'] ?? 'kematangan';

        // find existing by sso_user_id, then by email
        $user = User::where('sso_user_id', $ssoUserId)->first();
        if (! $user) {
            $email = $ssoUser['email'] ?? null;
            $user = $email ? User::where('email', $email)->first() : null;
        }

        if (! $user) {
            $user = User::create([
                'name' => $ssoUser['name'] ?? ('SSO '.$ssoUserId),
                'email' => $ssoUser['email'] ?? "sso+{$ssoUserId}@local",
                'password' => bcrypt(str()->random(40)),
                'role' => 'opd',
            ]);
            Log::info('sso:sync created local user', ['sso_user_id'=>$ssoUserId, 'user_id'=>$user->id]);
        }

        $user->sso_user_id = $ssoUserId;
        $user->sso_app_role_slug = $ssoUser['app_role_slug'] ?? ($ssoUser['role'] ?? null);
        // Map SSO role slug into canonical local role (do not store raw slug into role)
        if (! empty($user->sso_app_role_slug)) {
            $user->role = $this->mapSsoToLocalRole((string) $user->sso_app_role_slug);
        }
        $user->sso_last_synced_at = now();
        $user->save();

        // persist allowed OPDs for this app: clear existing and insert new
        SsoAllowedOpd::where('user_id', $user->id)->where('app_code', $appCode)->delete();
        $allowed = $ssoUser['allowed_opd_ids_by_app'][$appCode] ?? $ssoUser['allowed_opd_ids'] ?? [];
        if (is_array($allowed) && count($allowed)) {
            // IMPORTANT: SSO provides OPD identifiers in SSO space (sso_opd_id).
            // We must NOT assume those equal local user IDs. Use `sso_opd_mappings`
            // to resolve to local `opd_id`. If no mapping exists, preserve the
            // `opd_sso_id` so reconciliation can be performed later and do not
            // incorrectly link to a different local OPD.
            foreach (collect($allowed)->map(fn($v) => (int)$v)->filter()->values()->all() as $ssoOpdId) {
                $mapping = \App\Models\SsoOpdMapping::where('sso_opd_id', $ssoOpdId)->first();
                $linked = $mapping ? (int)$mapping->local_user_id : null;
                SsoAllowedOpd::create(['user_id' => $user->id, 'app_code' => $appCode, 'opd_id' => $linked, 'opd_sso_id' => $ssoOpdId]);
            }
        }

        Log::info('sso:sync applied payload', ['sso_user_id'=>$ssoUserId, 'user_id'=>$user->id, 'allowed'=>$allowed]);

        return $user;
    }

    public function syncUserBySsoId(mixed $ssoUserId, bool $dryRun = false)
    {
        $payload = $this->client->fetchUser($ssoUserId);
        if ($dryRun) {
            return $payload;
        }
        return $this->applyPayload($payload);
    }

    public function syncAll(int|null $limit = null, bool $dryRun = false): array
    {
        $query = User::whereNotNull('sso_user_id');
        if ($limit) $query->limit($limit);
        $users = $query->get();
        $results = [];
        foreach ($users as $u) {
            try {
                $results[$u->id] = $this->syncUserBySsoId($u->sso_user_id, $dryRun);
            } catch (\Throwable $e) {
                $results[$u->id] = ['error' => $e->getMessage()];
            }
        }
        return $results;
    }

    private function mapSsoToLocalRole(string $appRoleSlug): string
    {
        $slug = strtolower(trim($appRoleSlug));
        $slug = str_replace([' ', '_', '-'], '', $slug);
        $map = [
            'superadmin' => 'superadmin',
            'bagoradmin' => 'admin',
            'bagor-admin' => 'admin',
            'bagianorganisasi' => 'admin',
            'verifikatorglobal' => 'verifikator',
            'verifikator-global' => 'verifikator',
            'verifikator' => 'verifikator',
            'opd' => 'opd',
            'opdadmin' => 'opd',
            'adminopd' => 'opd',
            'orgadmin' => 'admin',
        ];

        return $map[$slug] ?? 'opd';
    }
}

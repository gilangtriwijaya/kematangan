<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SsoOpdService
{
    /**
     * Get allowed OPD user IDs for given user and app.
     * Returns null when GLOBAL (no restriction), or array of ints when restricted.
     */
    public function getAllowedOpdUserIds(User $user, string $appCode = 'kematangan'): ?array
    {
        // Determine which local user row owns the SSO mapping.
        // Prefer the provided user's own mapping (if they have `sso_user_id`).
        $ownerUserId = null;
        if (!empty($user->sso_user_id)) {
            $ownerUserId = $user->id;
        } else {
            // 1) Fallback: if there's an SSO session payload, try to find the local user
            //    that was created/updated for that SSO id.
            $sso = session('sso.user');
            if (is_array($sso) && !empty($sso['id'])) {
                $owner = DB::table('users')->where('sso_user_id', (int)$sso['id'])->first();
                if ($owner) $ownerUserId = (int) $owner->id;
            }

            // 2) If still not found, try to find a local user with same email that has
            //    an `sso_user_id` (most reliable when consume created a separate owner record).
            if ($ownerUserId === null && !empty($user->email)) {
                $ownerByEmail = DB::table('users')
                    ->where('email', $user->email)
                    ->whereNotNull('sso_user_id')
                    ->first();
                if ($ownerByEmail) $ownerUserId = (int) $ownerByEmail->id;
            }

            // 3) Final fallback: use the provided local user's id (no mapping -> GLOBAL)
            if ($ownerUserId === null) $ownerUserId = $user->id;
        }

            // Determine whether this owner user's SSO role requires OPD restriction.
            $ownerRecord = DB::table('users')->where('id', $ownerUserId)->first();

        Log::debug('[SsoOpdService] start resolve', ['user_id'=>$user->id, 'sso_user_id'=>$user->sso_user_id ?? null, 'app'=>$appCode]);
            $ownerRoleSlug = null;
            if ($ownerRecord) {
                $ownerRoleSlug = $ownerRecord->sso_app_role_slug ?? $ownerRecord->role ?? null;
            }

            Log::debug('[SsoOpdService] owner from current user sso_user_id', ['owner_user_id'=>$ownerUserId, 'sso_user_id'=>$user->sso_user_id]);
            $normOwnerRole = function ($v) {
                if (empty($v)) return '';
                $v = strtolower(trim((string)$v));
                return str_replace([' ', '_', '-'], '', $v);
            };

            $norm = $normOwnerRole($ownerRoleSlug);

            // Only enforce OPD restriction for SSO roles that imply global-admin/verifikator behavior.
            // Include common variants used by SSO (org-admin, bagor-admin, bagianorganisasi, verifikator-global, etc.).
            $enforceRoles = ['bagoradmin', 'bagor-admin', 'bagianorganisasi', 'orgadmin', 'org-admin', 'org-admins', 'verifikatorglobal', 'verifikator-global', 'verifikator'];

            // If owner has explicit SSO role slug and it matches enforce roles, enforce.
            $normalizedEnforce = array_map(fn($r)=>str_replace([' ', '_', '-'], '', strtolower($r)), $enforceRoles);
            $enforce = in_array($norm, $normalizedEnforce, true);

            // Additionally, if there is a role-level mapping defined for this exact SSO role slug,
            // we should enforce according to that mapping (even if the role slug isn't in the list above).
            try {
                $roleSvc = app(\App\Services\RoleOpdMappingService::class);
                $roleRule = $roleSvc->getAllowedSsoOpdIds($ownerRecord->sso_app_role_slug ?? ($ownerRecord->role ?? ''));
                if (is_array($roleRule) && !empty($roleRule)) {
                    // There's a role-level rule (array of sso ids means restricted)
                    $enforce = true;
                } else {
                    // If role slug contains 'global' (e.g. 'verifikator-global'), check whether
                    // a specific 'global' mapping exists (array) and enforce if so.
                    if (str_contains(strtolower((string)$ownerRecord->sso_app_role_slug ?? ''), 'global')) {
                        $globalRule = $roleSvc->getAllowedSsoOpdIds('global');
                        if (is_array($globalRule) && !empty($globalRule)) $enforce = true;
                    }
                }
            } catch (\Throwable $e) {
                // ignore role service failures and fall back to existing logic
            }

            // If owner doesn't have SSO slug but local role is 'admin' or 'verifikator', and there are sso_allowed_opds rows, enforce as well.
            if (!$enforce && $ownerRecord) {
                $localRole = strtolower((string)($ownerRecord->role ?? ''));
                if (in_array($localRole, ['admin', 'verifikator'], true)) {
                    Log::debug('[SsoOpdService] owner resolved by email', ['owner_user_id'=>$ownerUserId, 'email'=>$user->email, 'owner_sso_user_id'=>$ownerByEmail->sso_user_id ?? null]);
                    $hasRows = DB::table('sso_allowed_opds')->where('user_id', $ownerUserId)->where('app_code', $appCode)->exists();
                    if ($hasRows) $enforce = true;
                }
            }

            if (!$enforce) {
                return null; // GLOBAL
            }

            // collect explicit local opd_id rows
        Log::debug('[SsoOpdService] owner record', ['owner_user_id'=>$ownerUserId, 'owner_record'=> $ownerRecord ? ['id'=>$ownerRecord->id,'email'=>$ownerRecord->email,'role'=>$ownerRecord->role,'sso_app_role_slug'=>$ownerRecord->sso_app_role_slug] : null]);
            $localIds = DB::table('sso_allowed_opds')
                ->where('user_id', $ownerUserId)
                ->where('app_code', $appCode)
                ->pluck('opd_id')
                ->filter()
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            // collect SSO opd ids from per-user allowed rows and try to resolve via mapping table
            $ssoIds = DB::table('sso_allowed_opds')
                ->where('user_id', $ownerUserId)
                ->where('app_code', $appCode)
                ->pluck('opd_sso_id')
                ->filter()
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();

            if (!empty($ssoIds)) {
                $mapped = DB::table('sso_opd_mappings')
                    ->whereIn('sso_opd_id', $ssoIds)
                    ->pluck('local_user_id')
                    ->map(fn($v) => (int)$v)
                    ->unique()
                    ->values()
                    ->all();
                $localIds = array_values(array_unique(array_merge($localIds, $mapped)));
            }

            // Apply role-level mappings from RoleOpdMappingService for this owner's SSO role.
            try {
                $roleSvc = app(\App\Services\RoleOpdMappingService::class);

                // 1) Exact SSO role slug mapping (if present) has priority.
                $ownerRoleKey = $ownerRecord->sso_app_role_slug ?? $ownerRecord->role ?? '';
                $roleSsoIds = $roleSvc->getAllowedSsoOpdIds($ownerRoleKey);
                if (is_array($roleSsoIds) && !empty($roleSsoIds)) {
                    $mappedRole = DB::table('sso_opd_mappings')
                        ->whereIn('sso_opd_id', $roleSsoIds)
                        ->pluck('local_user_id')
                        ->map(fn($v) => (int)$v)
                        ->unique()
                        ->values()
                        ->all();
                    $localIds = array_values(array_unique(array_merge($localIds, $mappedRole)));
                } else {
                    // 2) Fallback: if role slug contains 'global', consult the 'global' rule.
                    if (str_contains(strtolower((string)$ownerRoleKey), 'global')) {
                        $globalSsoIds = $roleSvc->getAllowedSsoOpdIds('global');
                        if (is_array($globalSsoIds) && !empty($globalSsoIds)) {
                            $mappedRole = DB::table('sso_opd_mappings')
                                ->whereIn('sso_opd_id', $globalSsoIds)
                                ->pluck('local_user_id')
                                ->map(fn($v) => (int)$v)
                                ->unique()
                                ->values()
                                ->all();
                            $localIds = array_values(array_unique(array_merge($localIds, $mappedRole)));
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore and continue
            }

            // If no mapping rows found, treat as GLOBAL
            if (empty($localIds)) return null;

            return $localIds;
    }

    /**
     * Helper: apply OPD restriction to a query that has a user_id column.
     * If restricted, it will add whereIn('user_id', [...]). Otherwise no-op.
     */
    public function applyToQuery($query, User $user, string $appCode = 'kematangan')
    {
        $allowed = $this->getAllowedOpdUserIds($user, $appCode);
        if (is_array($allowed)) {
            return $query->whereIn('user_id', $allowed);
        }
        return $query;
    }
}

<?php

namespace App\Services;

use App\Models\RoleOpdMapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleOpdMappingService
{
    // cache key per role
    public function cacheKey(string $role): string
    {
        return 'role_opd_mapping:' . strtolower($role);
    }

    /**
     * Get allowed SSO OPD ids for a role. Returns null when unrestricted (no rows).
     * Cached for 300s by default.
     */
    public function getAllowedSsoOpdIds(string $role): ?array
    {
        $key = $this->cacheKey($role);
        return Cache::remember($key, 300, function () use ($role) {
            $rows = RoleOpdMapping::where('role_name', $role)->pluck('opd_sso_id')->map(fn($v)=> (int)$v)->unique()->values()->all();
            if (empty($rows)) return null;
            return $rows;
        });
    }

    public function setMapping(string $ruleId, string $role, array $opdSsoIds, array $applyTo = [], ?int $createdBy = null, ?string $effectiveFrom = null): void
    {
        DB::transaction(function () use ($ruleId,$role,$opdSsoIds,$applyTo,$createdBy,$effectiveFrom) {
            RoleOpdMapping::where('role_name', $role)->delete();
            foreach ($opdSsoIds as $ssoId) {
                RoleOpdMapping::create([
                    'rule_id' => $ruleId,
                    'role_name' => $role,
                    'opd_sso_id' => (int)$ssoId,
                    'apply_to' => empty($applyTo) ? null : implode(',', $applyTo),
                    'effective_from' => $effectiveFrom ? date('Y-m-d H:i:s', strtotime($effectiveFrom)) : null,
                    'created_by' => $createdBy,
                ]);
            }
            Cache::forget($this->cacheKey($role));
        });
    }
}

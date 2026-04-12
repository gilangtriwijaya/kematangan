<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureSsoType
{
    /**
     * Pakai: middleware('sso.type:superadmin,admin,opd')
     */
    public function handle(Request $request, Closure $next, ...$allowed): mixed
    {
        // Normalisasi allowed list and map to canonical local roles
        $allowed = array_values(array_filter(array_map(function ($v) {
            return $this->mapRole($this->norm($v));
        }, $allowed)));

        // Kalau tidak ada parameter allowed, lolos
        if (count($allowed) === 0) {
            return $next($request);
        }

        // 1) PRIORITAS: user lokal (Auth session Laravel)
        $localUser = $request->user();
        if ($localUser) {
            $role = $this->mapRole($this->norm((string)($localUser->role ?? '')));
            if ($role !== '' && in_array($role, $allowed, true)) {
                Log::debug('EnsureSsoType: local user allowed', ['user_id' => $localUser->id, 'local_role' => $localUser->role, 'mapped_role' => $role, 'allowed' => $allowed]);
                return $next($request);
            }

            // Jika local role tidak cocok, coba fallback ke payload SSO (app_role_slug / app_roles)
            $sso = session('sso.user');
            if (is_array($sso)) {
                // prefer explicit app_role_slug
                $candidates = [];
                if (!empty($sso['app_role_slug'])) {
                    $candidates[] = (string) $sso['app_role_slug'];
                }
                // app_role or role fields
                if (!empty($sso['app_role'])) {
                    $candidates[] = (string) $sso['app_role'];
                }
                if (!empty($sso['role'])) {
                    $candidates[] = (string) $sso['role'];
                }
                // app_roles array
                if (!empty($sso['app_roles']) && is_array($sso['app_roles'])) {
                    foreach ($sso['app_roles'] as $r) $candidates[] = (string) $r;
                }

                foreach ($candidates as $raw) {
                    $norm = $this->norm((string)$raw);
                    $mapped = $this->mapRole($norm);
                    Log::debug('EnsureSsoType: checking SSO candidate role', ['user' => $localUser->id ?? null, 'candidate_raw' => $raw, 'norm' => $norm, 'mapped' => $mapped, 'allowed' => $allowed]);
                    if ($mapped !== '' && in_array($mapped, $allowed, true)) {
                        Log::debug('EnsureSsoType: SSO candidate allowed', ['user' => $localUser->id ?? null, 'candidate' => $mapped]);
                        return $next($request);
                    }
                }
            }

            abort(403, 'Forbidden (role not allowed)');
        }

        // 2) FALLBACK: payload SSO di session
        $sso = session('sso.user');
        if (!is_array($sso)) {
            abort(401, 'Unauthenticated (SSO)');
        }

        $raw = (string)($sso['role'] ?? $sso['type'] ?? $sso['user_type'] ?? '');
        $role = $this->mapRole($this->norm($raw));
        Log::debug('EnsureSsoType: fallback SSO session role check', ['sso_raw' => $raw, 'mapped' => $role, 'allowed' => $allowed]);

        if ($role === '') {
            abort(403, 'Forbidden (role unknown)');
        }

        if (!in_array($role, $allowed, true)) {
            abort(403, 'Forbidden (role not allowed)');
        }

        return $next($request);
    }

    private function norm(string $v): string
    {
        $v = strtolower(trim($v));
        $v = str_replace([' ', '_', '-'], '', $v);
        return $v;
    }

    private function mapRole(string $role): string
    {
        // Mapping dari berbagai istilah SSO -> enum role kematangan
        $map = [
            'superadmin'   => 'superadmin',
            'super_admin'  => 'superadmin',
            'super'        => 'superadmin',

            'admin'        => 'admin',
            'bagoradmin'   => 'admin',
            'bagor_admin'  => 'admin',
            'bagianorganisasi' => 'admin',

            'verifikator'  => 'verifikator',
            'verifier'     => 'verifikator',
            'validator'    => 'verifikator',

            'opd'          => 'opd',
            'opdadmin'     => 'opd',
            'opd_admin'    => 'opd',
        ];

        // karena norm() menghapus underscore/dash, kita siapkan kunci map yg sudah “dinorm”
        $mapNorm = [];
        foreach ($map as $k => $v) {
            $mapNorm[$this->norm($k)] = $v;
        }

        return $mapNorm[$role] ?? $role; // kalau sudah cocok (superadmin/admin/verifikator/opd) akan lewat
    }
}

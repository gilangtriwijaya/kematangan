<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        // Normalize incoming roles and map to canonical values
        $norm = function (string $v): string {
            $v = strtolower(trim($v));
            $v = str_replace([' ', '_', '-'], '', $v);
            return $v;
        };

        $mapRole = function (string $role): string {
            $map = [
                'superadmin' => 'superadmin',
                'super_admin' => 'superadmin',
                'super' => 'superadmin',

                'admin' => 'admin',
                'bagoradmin' => 'admin',
                'bagor_admin' => 'admin',
                'bagianorganisasi' => 'admin',

                'verifikator' => 'verifikator',
                'verifier' => 'verifikator',

                'opd' => 'opd',
                'opdadmin' => 'opd',
                'opd_admin' => 'opd',
            ];
            $key = $norm($role);
            return $map[$key] ?? $key;
        };

        $allowed = array_map(fn($r) => $mapRole($r), array_filter($roles));

        if ($user) {
            $userRole = $mapRole($user->role ?? '');
            Log::debug('RoleMiddleware: checking', ['user_id' => $user->id ?? null, 'user_role' => $user->role, 'mapped_user_role' => $userRole, 'allowed' => $allowed]);
            if (in_array($userRole, $allowed, true)) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak.');
    }
}

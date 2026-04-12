<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSsoRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $userRole = $this->mapRole($this->norm((string) ($user->role ?? '')));

        $allowed = array_values(array_filter(array_map(function ($r) {
            return $this->mapRole($this->norm((string) $r));
        }, $roles)));

        // kalau tidak ada roles parameter, biarkan lewat (biar tidak “ngunci semua”)
        if (count($allowed) === 0) {
            return $next($request);
        }

        if ($userRole === '' || !in_array($userRole, $allowed, true)) {
            abort(403, 'FORBIDDEN (ROLE NOT ALLOWED)');
        }

        return $next($request);
    }

    private function norm(string $v): string
    {
        $v = strtolower(trim($v));
        return str_replace([' ', '_', '-'], '', $v);
    }

    private function mapRole(string $role): string
    {
        // hasil akhir HARUS salah satu enum Kematangan: superadmin|admin|verifikator|opd
        $map = [
            'superadmin'  => 'superadmin',
            'superadmin'  => 'superadmin',
            'superadmin'  => 'superadmin',

            'admin'       => 'admin',
            'bagoradmin'  => 'admin',
            'bagor_admin' => 'admin',
            'bagianorganisasi' => 'admin',

            'verifikator' => 'verifikator',
            'verifier'    => 'verifikator',
            'validator'   => 'verifikator',

            'opd'         => 'opd',
            'opdadmin'    => 'opd',
        ];

        // karena norm() menghapus underscore/dash, kita pakai key yang sudah dinorm juga
        $k = $this->norm($role);
        return $map[$k] ?? $k;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\SsoAllowedOpd;
use App\Models\User;
use Carbon\Carbon;

class SsoConsumeController extends Controller
{
    public function consume(Request $request)
    {
        // Require TLS in production; allow non-TLS in debug/dev for testing.
        if (!$request->secure() && !config('app.debug')) {
            return Response::json(['error' => 'TLS required'], 400);
        }

        $signature = $request->header('X-SSO-Signature');
        $tsHeader = $request->header('X-SSO-Timestamp');
        $payload = $request->json()->all();
        $ticket = $payload['ticket'] ?? null;
        $appCode = $payload['app'] ?? 'kematangan';

        if (empty($ticket) || empty($signature)) {
            Log::warning('SSO consume missing ticket/signature', ['ip' => $request->ip()]);
            return Response::json(['error' => 'Missing ticket or signature'], 400);
        }

        $secret = env('SSO_TICKET_SECRET');

        // Support two signature methods for backward compatibility:
        // 1) New timestamp-based: header X-SSO-Timestamp + X-SSO-Signature: sha256=<hex>
        //    Signed over: "{timestamp}.{rawBody}" using HMAC-SHA256.
        // 2) Legacy: signature is HMAC-SHA256 of "<ticket>|<app>" (hex)
        $validSig = false;
        // try timestamp-based first when header present
        if (!empty($tsHeader) && !empty($signature)) {
            $maxSkew = (int) env('SSO_SIGNATURE_MAX_SKEW', 300);
            $now = time();
            $ts = (int) $tsHeader;
            if (abs($now - $ts) > $maxSkew) {
                Log::warning('SSO timestamp skew too large', ['now' => $now, 'ts' => $ts, 'skew' => $maxSkew]);
            } else {
                $raw = $request->getContent();
                // expected header format: sha256=<hex>
                $sig = $signature;
                if (str_starts_with($sig, 'sha256=')) $sig = substr($sig, 7);
                $calc = hash_hmac('sha256', $ts . '.' . $raw, (string) $secret);
                if (hash_equals($calc, $sig)) {
                    $validSig = true;
                } else {
                    Log::warning('SSO timestamp-based signature mismatch', ['ticket' => $ticket, 'ip' => $request->ip()]);
                }
            }
        }

        // fallback to legacy signature for compatibility
        if (! $validSig) {
            $calc = hash_hmac('sha256', $ticket.'|'.$appCode, (string) $secret);
            if (hash_equals($calc, (string) $signature)) {
                $validSig = true;
            }
        }

        if (! $validSig) {
            Log::warning('SSO invalid signature attempt', ['ticket' => $ticket, 'ip' => $request->ip()]);
            return Response::json(['error' => 'Invalid signature'], 401);
        }

        $userPayload = $payload['user'] ?? null;
        if (!$userPayload || !isset($userPayload['id'])) {
            Log::warning('SSO missing user payload', ['ticket' => $ticket]);
            return Response::json(['error' => 'Missing user payload'], 400);
        }

        $ssoUserId = (int) $userPayload['id'];

        // determine role slug (prefer app_role_slug then app_roles)
        $roleSlug = $userPayload['app_role_slug'] ?? null;
        if (empty($roleSlug) && !empty($userPayload['app_roles']) && is_array($userPayload['app_roles'])) {
            foreach ($userPayload['app_roles'] as $r) {
                $norm = $this->norm($r);
                if (in_array($norm, ['bagoradmin', 'verifikatorglobal', 'bagor-admin', 'verifikator-global'], true)) {
                    $roleSlug = $r;
                    break;
                }
            }
        }

        $allowedByApp = $userPayload['allowed_opd_ids_by_app'] ?? null;
        if (is_array($allowedByApp) && array_key_exists($appCode, $allowedByApp)) {
            $allowed = $allowedByApp[$appCode];
        } else {
            $allowed = $userPayload['allowed_opd_ids'] ?? null;
        }

        if (array_key_exists('is_opd_locked', $userPayload) && $userPayload['is_opd_locked'] === false) {
            $allowed = null; // GLOBAL
        }

        // Persist per-user allowed OPDs whenever SSO explicitly provides them.
        // Previously only certain canonical slugs were accepted; accept any SSO payload that includes allowed_opd_ids.
        $allowedToApply = null;
        if (is_array($allowed)) {
            // normalize empty array -> GLOBAL
            if (count($allowed) === 0) {
                $allowed = null;
            }
            $allowedToApply = $allowed; // null => GLOBAL, array => restrict
        } else {
            // legacy behavior: if no explicit list but role is known canonical, allow applying explicit null/global
            $normRole = $this->norm((string) ($roleSlug ?? ''));
            if (in_array($normRole, ['bagoradmin', 'verifikatorglobal'], true)) {
                $allowedToApply = $allowed;
            }
        }

        // --- SSO is source-of-truth: sync local user record (create/update) ---
        // Find local user by sso_user_id or by email
        $localUser = null;
        $email = $userPayload['email'] ?? null;
        if ($ssoUserId) {
            $localUser = User::where('sso_user_id', $ssoUserId)->first();
        }
        if (!$localUser && $email) {
            $localUser = User::where('email', $email)->first();
        }

        $mappedRole = $this->mapSsoToLocalRole($roleSlug ?? '', $userPayload);
        // Prefer to store the SSO-provided role slug in local `role` so app aligns with SSO.
        $desiredLocalRole = $userPayload['app_role_slug'] ?? $userPayload['app_role'] ?? $mappedRole;

        if (!$localUser) {
            // create a local user to mirror SSO
            $localUser = User::create([
                'name' => $userPayload['name'] ?? ($userPayload['username'] ?? 'SSO User'),
                'email' => $email ?? null,
                'password' => Hash::make(Str::random(40)),
                'role' => $desiredLocalRole,
                'opd_name' => $userPayload['opd_name'] ?? null,
                'sso_user_id' => $ssoUserId,
                'sso_app_role_slug' => $roleSlug ?? null,
                'sso_last_synced_at' => Carbon::now(),
            ]);
            Log::info('SSO created local user', ['sso_user_id' => $ssoUserId, 'local_user_id' => $localUser->id, 'email' => $email, 'role' => $mappedRole]);
        } else {
            // update existing local user metadata if changed
            $updates = [];
            if ($localUser->sso_user_id !== $ssoUserId) $updates['sso_user_id'] = $ssoUserId;
            if (($localUser->sso_app_role_slug ?? null) !== ($roleSlug ?? null)) $updates['sso_app_role_slug'] = $roleSlug ?? null;
            if (($localUser->name ?? null) !== ($userPayload['name'] ?? null)) $updates['name'] = $userPayload['name'] ?? $localUser->name;
            if ($email && ($localUser->email ?? null) !== $email) $updates['email'] = $email;
            if (($localUser->role ?? null) !== $desiredLocalRole) $updates['role'] = $desiredLocalRole;
            if (($localUser->opd_name ?? null) !== ($userPayload['opd_name'] ?? null)) $updates['opd_name'] = $userPayload['opd_name'] ?? $localUser->opd_name;
            if (!empty($updates)) {
                $updates['sso_last_synced_at'] = Carbon::now();
                $localUser->update($updates);
                Log::info('SSO updated local user', ['sso_user_id' => $ssoUserId, 'local_user_id' => $localUser->id, 'updates' => $updates]);
            } else {
                // touch last_synced
                $localUser->update(['sso_last_synced_at' => Carbon::now(), 'sso_app_role_slug' => $roleSlug ?? $localUser->sso_app_role_slug]);
            }
        }

        // Persist mapping: replace existing rows for (local_user_id, app_code)
        DB::transaction(function () use ($localUser, $appCode, $allowedToApply, $userPayload, $ssoUserId) {
            DB::table('sso_allowed_opds')->where('user_id', $localUser->id)->where('app_code', $appCode)->delete();

            if (is_array($allowedToApply)) {
                $candidate = collect($allowedToApply)->map(fn($v) => (int)$v)->filter()->values()->all();

                // Try best-effort: if candidate values match local user ids, store them as opd_id.
                $existing = DB::table('users')->whereIn('id', $candidate)->whereRaw('LOWER(role) = ?', ['opd'])->pluck('id')->map(fn($v) => (int)$v)->all();
                $unknown = array_diff($candidate, $existing);

                if (count($unknown) > 0) {
                    Log::info('SSO allowed_opd_ids have unknown local ids — saving as opd_sso_id for later mapping', ['sso_user_id' => $ssoUserId, 'local_user_id' => $localUser->id, 'app' => $appCode, 'unknown' => $unknown]);
                }

                // Insert rows: if we have a matching local user id, set opd_id; always set opd_sso_id to preserve SSO original id.
                foreach ($candidate as $ssoOpdId) {
                    // prefer to link to local user when possible
                    $linked = in_array($ssoOpdId, $existing, true) ? (int)$ssoOpdId : null;
                    SsoAllowedOpd::create([
                        'user_id' => $localUser->id,
                        'app_code' => $appCode,
                        'opd_id' => $linked,
                        'opd_sso_id' => $ssoOpdId,
                    ]);
                }
            }
            // if $allowedToApply is null => GLOBAL -> no rows left
        });

        // Also support role-level mapping rules (SSO may send a 'rule' object)
        if (!empty($payload['rule']) && is_array($payload['rule'])) {
            try {
                $rule = $payload['rule'];
                $ruleId = $rule['rule_id'] ?? null;
                $roleName = $rule['role'] ?? null;
                $allowedOpds = $rule['allowed_opd_ids'] ?? null; // may be empty array or null
                $applyTo = $rule['apply_to'] ?? [];
                $effectiveFrom = $rule['effective_from'] ?? null;

                if ($roleName) {
                    $svc = app(\App\Services\RoleOpdMappingService::class);
                    // if allowedOpds is empty array or null -> leave DB with no rows => interpreted as unrestricted
                    $opds = is_array($allowedOpds) ? array_values(array_map(fn($v)=>(int)$v, $allowedOpds)) : [];
                    $svc->setMapping($ruleId, $roleName, $opds, $applyTo, $localUser->id ?? null, $effectiveFrom);
                    Log::info('SSO role mapping applied', ['rule' => $ruleId, 'role' => $roleName, 'allowed_count' => count($opds)]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed applying role mapping from SSO', ['err' => $e->getMessage()]);
            }
        }

        // Audit/log successful consume
        Log::info('SSO consume applied', ['sso_user_id' => $ssoUserId, 'local_user_id' => $localUser->id ?? null, 'app' => $appCode, 'applied' => $allowedToApply]);

        // Trigger an immediate per-user sync attempt (best-effort) so mappings are resolved promptly.
        try {
            // apply the same payload we just validated to ensure SSO sync logic runs
            $svc = app(\App\Services\SsoSyncService::class);
            $svc->applyPayload($payload);
        } catch (\Throwable $e) {
            Log::warning('SSO post-consume applyPayload failed', ['sso_user_id'=>$ssoUserId,'error'=>$e->getMessage()]);
        }

        return Response::json(['ok' => true]);
    }

    private function norm(string $v): string
    {
        $v = strtolower(trim($v));
        return str_replace([' ', '_', '-'], '', $v);
    }

    private function mapSsoToLocalRole(string $appRoleSlug, array $payload): string
    {
        $slug = $this->norm($appRoleSlug ?: ($payload['app_role'] ?? $payload['role'] ?? ''));

        // canonical mapping: map SSO canonical slugs to local roles
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
        ];

        return $map[$slug] ?? ($payload['role'] ?? 'opd');
    }
}

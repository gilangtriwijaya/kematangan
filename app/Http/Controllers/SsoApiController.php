<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Services\SsoClient;
use App\Services\SsoSyncService;

class SsoApiController extends Controller
{
    private function checkToken(Request $request)
    {
        $token = env('SSO_PULL_TOKEN');
        $hdr = $request->bearerToken();
        return !empty($token) && hash_equals((string)$token, (string)$hdr);
    }

    // GET /api/sso/users?app=kematangan&per_page=10&page=1
    public function index(Request $request)
    {
        if (! $this->checkToken($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        $app = $request->query('app', 'kematangan');
        $per = (int) $request->query('per_page', 50);
        $page = (int) $request->query('page', 1);
        $query = DB::table('users')->whereNotNull('sso_user_id');
        // optional filter by app via role slug presence
        if ($app) {
            // no strict filtering; the client can filter on role later
        }
        $total = $query->count();
        $items = $query->offset(($page - 1) * $per)->limit($per)->get();
        return Response::json(['total' => $total, 'page' => $page, 'per_page' => $per, 'items' => $items]);
    }

    // GET /api/sso/users/{sso_id}
    public function show(Request $request, $ssoId)
    {
        if (! $this->checkToken($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        $user = DB::table('users')->where('sso_user_id', (int)$ssoId)->first();
        if (! $user) return Response::json(['error' => 'Not found'], 404);
        // include allowed_opd rows (sso_opd_id + local opd_id when present)
        $opds = DB::table('sso_allowed_opds')->where('user_id', $user->id)->get();
        $user->allowed_opds = $opds;
        return Response::json($user);
    }

    // GET /api/sso/opds/sso/{sso_id}
    public function opdsLookup(Request $request, $ssoId)
    {
        if (! $this->checkToken($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        $rows = DB::table('sso_opd_mappings')->where('sso_opd_id', (int)$ssoId)->get();
        if ($rows->isEmpty()) return Response::json(['error' => 'Not found'], 404);
        return Response::json($rows);
    }

    // GET /api/sso/fetch/{sso_id}?apply=1
    // Fetch the authoritative SSO payload from the SSO provider using configured template and token.
    // If ?apply=1 provided, apply it locally via SsoSyncService::applyPayload().
    public function fetchRemote(Request $request, $ssoId)
    {
        if (! $this->checkToken($request)) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $client = app(SsoClient::class);
            $payload = $client->fetchUser((int)$ssoId);
        } catch (\Throwable $e) {
            return Response::json(['error' => 'Failed fetching from SSO', 'msg' => $e->getMessage()], 502);
        }

        if ($request->query('apply')) {
            try {
                $svc = app(SsoSyncService::class);
                $user = $svc->applyPayload($payload);
                return Response::json(['applied' => true, 'local_user_id' => $user->id]);
            } catch (\Throwable $e) {
                return Response::json(['error' => 'Failed applying payload', 'msg' => $e->getMessage()], 500);
            }
        }

        return Response::json($payload);
    }
}

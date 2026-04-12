<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SsoClient
{
    protected string|null $template;
    protected string|null $token;

    public function __construct()
    {
        $this->template = config('services.sso.sync_url_template') ?: env('SSO_SYNC_URL_TEMPLATE');
        $this->token = config('services.sso.token') ?: env('SSO_API_TOKEN');
    }

    /**
     * Fetch SSO user payload by SSO user id using configured template.
     * Template must contain `{id}` which will be replaced.
     * @return array
     */
    public function fetchUser(mixed $ssoUserId): array
    {
        if (empty($this->template)) {
            throw new \RuntimeException('SSO sync URL template not configured (SSO_SYNC_URL_TEMPLATE)');
        }

        $url = str_replace('{id}', (string) $ssoUserId, $this->template);
        $req = Http::withToken($this->token)->get($url);

        if (! $req->successful()) {
            throw new \RuntimeException('SSO fetch failed: ' . $req->status());
        }

        return $req->json() ?: [];
    }

    /**
     * Fetch a single OPD record from SSO by its SSO OPD id.
     * Requires `services.sso.opd_url_template` or env `SSO_OPD_URL_TEMPLATE`.
     * Template must include `{id}`.
     * Returns array payload or empty array on missing/null response.
     */
    public function fetchOpd(mixed $ssoOpdId): array
    {
        $template = config('services.sso.opd_url_template') ?: env('SSO_OPD_URL_TEMPLATE');
        if (empty($template)) {
            throw new \RuntimeException('SSO OPD URL template not configured (SSO_OPD_URL_TEMPLATE)');
        }

        $url = str_replace('{id}', (string) $ssoOpdId, $template);
        $req = Http::withToken($this->token)->get($url);

        if (! $req->successful()) {
            throw new \RuntimeException('SSO fetch OPD failed: ' . $req->status());
        }

        return $req->json() ?: [];
    }

    /**
     * Fetch one page of OPDs from SSO pull endpoint.
     * Returns associative array with keys: data (array), meta (if present)
     */
    public function fetchOpdsPage(int $page = 1, int $perPage = 50, string|int|null $updatedAfter = null): array
    {
        $base = rtrim(config('services.sso.base_url', env('SSO_BASE_URL', '')), '/');
        $endpoint = config('services.sso.opds_endpoint', env('SSO_OPDS_ENDPOINT', '/api/sso/opds'));

        $url = $base . $endpoint;

        $params = ['page' => $page, 'per_page' => $perPage];
        if (!is_null($updatedAfter) && $updatedAfter !== '') $params['updated_after'] = $updatedAfter;

        // Prefer Pull Token if configured
        $pullToken = config('services.sso.pull_token') ?: env('SSO_PULL_TOKEN');
        $pullSecret = config('services.sso.pull_secret') ?: env('SSO_PULL_SECRET');

        $client = Http::timeout(30)->retry(1, 200);

        if (!empty($pullToken)) {
            $req = $client->withToken($pullToken)->acceptJson()->get($url, $params);
        } else {
            // HMAC signed request
            $ts = time();
            $body = '';
            $sig = '';
            if (empty($pullSecret)) {
                throw new \RuntimeException('No SSO pull auth configured (SSO_PULL_TOKEN or SSO_PULL_SECRET)');
            }
            $sig = hash_hmac('sha256', $ts . '.' . $body, $pullSecret);
            $req = $client->withHeaders([
                'X-SSO-Timestamp' => $ts,
                'X-SSO-Signature' => 'sha256=' . $sig,
                'Accept' => 'application/json',
            ])->get($url, $params);
        }

        if (! $req->successful()) {
            throw new \RuntimeException('SSO fetch OPDs failed: ' . $req->status() . ' ' . substr($req->body(), 0, 400));
        }

        return $req->json() ?: ['data' => []];
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSsoAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('sso.user')) {
            return $next($request);
        }

        // simpan intended url agar balik ke halaman yang diminta
        session(['sso.intended' => $request->fullUrl()]);

        $ssoBase = rtrim(env('SSO_BASE_URL'), '/');
        $appCode = env('SSO_APP_CODE', 'kematangan');

        $redirectUri = url('/kematangan/sso/callback');

        $authorizeUrl = $ssoBase . '/sso/authorize'
            . '?app=' . urlencode($appCode)
            . '&redirect_uri=' . urlencode($redirectUri);

        return redirect()->away($authorizeUrl);
    }
}

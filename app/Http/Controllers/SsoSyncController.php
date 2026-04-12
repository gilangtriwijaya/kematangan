<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SsoSyncService;

class SsoSyncController extends Controller
{
    public function sync(Request $request, SsoSyncService $svc)
    {
        $this->authorize('admin-only');

        $user = $request->input('user');
        $file = $request->file('file');

        if ($file) {
            $json = json_decode(file_get_contents($file->getRealPath()), true);
            $svc->applyPayload($json);
            return response()->json(['ok'=>true]);
        }

        if ($user) {
            $svc->syncUserBySsoId($user, false);
            return response()->json(['ok'=>true]);
        }

        $svc->syncAll();
        return response()->json(['ok'=>true]);
    }
}

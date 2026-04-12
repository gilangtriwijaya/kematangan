<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $path = storage_path('logs/laravel.log');

        if (!File::exists($path)) {
            return view('log.index', ['logs' => [], 'query' => '']);
        }

        $lines = array_reverse(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        $logs = array_slice($lines, 0, 100);

        $query = $request->input('q');

        if ($query) {
            $logs = array_filter($logs, function ($line) use ($query) {
                return str_contains(strtolower($line), strtolower($query));
            });
        }

        return view('log.index', [
            'logs' => $logs,
            'query' => $query
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogAktivitas;
use App\Models\User;

class LogAktivitasController extends Controller
{

    public function index(Request $request)
    {
        $query = LogAktivitas::with('user')->latest();
    
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->role) {
            $query->where('role', $request->role);
        }
    
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
    
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    
        $logs = $query->paginate(15); // ⬅️ Ubah dari get() ke paginate()
        $users = User::orderBy('name')->get();
        $roles = User::select('role')->distinct()->pluck('role');
    
        return view('log.index', compact('logs', 'users', 'roles'));
    }

}

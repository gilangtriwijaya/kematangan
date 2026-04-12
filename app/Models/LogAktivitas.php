<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id', 'role', 'aksi', 'keterangan',
        'url', 'ip_address', 'user_agent',
        'method', 'status_code', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false; // 🔧 Ini penting

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

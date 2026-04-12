<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use App\Models\SsoAllowedOpd;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'opd_name',
        'sso_user_id',
        'sso_app_role_slug',
        'sso_last_synced_at',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
    ];
}

    /**
     * Relation to SSO allowed opd rows
     */
    public function ssoAllowedOpds()
    {
        return $this->hasMany(SsoAllowedOpd::class, 'user_id', 'id');
    }

    /**
     * Get allowed OPD ids for a given app (null => GLOBAL)
     * Returns array of ints (may be empty)
     */
    public function getSsoAllowedOpdIds(string $appCode = 'kematangan'): array
    {
        $rows = DB::table('sso_allowed_opds')
            ->where('user_id', $this->id)
            ->where('app_code', $appCode)
            ->pluck('opd_id')
            ->filter()
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        return $rows;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoAllowedOpd extends Model
{
    use HasFactory;

    protected $table = 'sso_allowed_opds';

    protected $fillable = ['user_id', 'app_code', 'opd_id', 'opd_sso_id'];

    public $timestamps = true;
}

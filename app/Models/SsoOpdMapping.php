<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoOpdMapping extends Model
{
    use HasFactory;

    protected $table = 'sso_opd_mappings';

    protected $fillable = ['sso_opd_id', 'local_user_id', 'opd_name'];
}

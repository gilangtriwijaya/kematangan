<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleOpdMapping extends Model
{
    use HasFactory;

    protected $table = 'role_opd_mappings';

    protected $fillable = ['rule_id','role_name','opd_sso_id','apply_to','effective_from','created_by'];

    public $timestamps = true;
}

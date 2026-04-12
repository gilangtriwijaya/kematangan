<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TingkatPenilaian extends Model
{
    use HasFactory;

    protected $table = 'tingkat_penilaian';

    protected $fillable = [
        'kegiatan_id',
        'kode',
        'label',
        'poin',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(KegiatanPenilaian::class, 'kegiatan_id');
    }

    public function indikator()
    {
        return $this->hasMany(\App\Models\IndikatorVariabel::class, 'tingkat_id')->orderBy('id');
    }

    public function bukti()
    {
        return $this->hasMany(\App\Models\IndikatorDokumen::class, 'tingkat_id')->orderBy('urutan');
    }
}

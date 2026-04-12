<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndikatorDokumen extends Model
{
    use HasFactory;

    protected $table = 'indikator_dokumen';

    protected $fillable = [
        'indikator_id',
        'tingkat_id',
        'nama_dokumen',
        'urutan',
    ];

    /**
     * Relasi ke tabel indikator_penilaian
     */
    public function indikator()
    {
        return $this->belongsTo(\App\Models\IndikatorVariabel::class, 'indikator_id');
    }

    /**
     * Relasi ke tingkat_penilaian
     */
    public function tingkat()
    {
        return $this->belongsTo(\App\Models\TingkatPenilaian::class, 'tingkat_id');
    }
}

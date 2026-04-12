<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariabelPenilaian extends Model
{
    use HasFactory;

    protected $table = 'variabels'; // sesuaikan dengan nama tabel di DB Anda

    protected $fillable = [
        'kegiatan_id',
        'kode',
        'nama',
        'urutan',
    ];

    // Relasi ke kegiatan penilaian
    public function kegiatan()
    {
        return $this->belongsTo(KegiatanPenilaian::class, 'kegiatan_id');
    }

    public function tingkat()
    {
        return $this->hasMany(TingkatPenilaian::class, 'variabel_id');
    }



    // (Opsional) relasi ke indikator
    public function indikator()
    {
        return $this->hasMany(IndikatorVariabel::class, 'variabel_id');
    }
}

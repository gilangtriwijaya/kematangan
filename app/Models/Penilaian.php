<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penilaian extends Model
{
    use HasFactory;

    protected $table = 'penilaian';

    protected $fillable = [
        'user_id', 'kegiatan_id', 'variabel_id', 'tingkat_id', 'tahun'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kegiatan()
    {
        return $this->belongsTo(KegiatanPenilaian::class, 'kegiatan_id');
    }

    public function variabel()
    {
        return $this->belongsTo(VariabelPenilaian::class, 'variabel_id');
    }

    public function tingkat()
    {
        return $this->belongsTo(TingkatPenilaian::class, 'tingkat_id');
    }

    // Relasi ke penilaian_detail
    public function bukti()
    {
        return $this->hasMany(PenilaianDetail::class, 'penilaian_id');
    }

    // 🔧 Tambahan: akses jawaban indikator lewat penilaian_detail
    public function jawabanIndikator()
    {
        return $this->hasManyThrough(
            JawabanIndikator::class,     // Target model
            PenilaianDetail::class,      // Intermediate model
            'penilaian_id',              // FK di PenilaianDetail
            'penilaian_detail_id',       // FK di JawabanIndikator
            'id',                        // PK di Penilaian
            'id'                         // PK di PenilaianDetail
        );
    }
}

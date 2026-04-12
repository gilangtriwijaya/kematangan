<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JawabanIndikator extends Model
{
    use HasFactory;

    protected $table = 'jawaban_indikator';

    protected $fillable = [
        'penilaian_detail_id',
        'indikator_id',
        'is_latest',
        'status',
        'komentar'
    ];

    protected $casts = [
        'is_latest' => 'boolean',
    ];

    // Relasi ke detail penilaian
    public function penilaianDetail()
    {
        return $this->belongsTo(PenilaianDetail::class, 'penilaian_detail_id');
    }

    // Relasi ke indikator
    public function indikator()
    {
        return $this->belongsTo(IndikatorVariabel::class, 'indikator_id');
    }

    // Relasi ke file dokumen (bisa lebih dari 1)
    public function dokumen()
    {
        return $this->hasMany(DokumenIndikator::class, 'jawaban_id');
    }

    // app/Models/JawabanIndikator.php
    
}

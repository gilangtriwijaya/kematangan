<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PenilaianDetail extends Model
{
    use HasFactory;

    protected $table = 'penilaian_detail';

    // Perluas agar aman jika nanti pakai create()/update()
    protected $fillable = [
        'penilaian_id',
        'variabel_id',
        'tingkat_id',
        'indikator_id',
        'status',
        'poin',
    ];

    public $timestamps = false;

    protected $casts = [
        'penilaian_id' => 'integer',
        'variabel_id'  => 'integer',
        'tingkat_id'   => 'integer',
        'indikator_id' => 'integer',
        'poin'         => 'integer',
    ];

    /* ===================== Relasi ===================== */

    // Induk header
    public function penilaian()
    {
        return $this->belongsTo(Penilaian::class);
    }

    // Master variabel
    public function variabel()
    {
        return $this->belongsTo(VariabelPenilaian::class, 'variabel_id');
    }

    // Master tingkat
    public function tingkat()
    {
        return $this->belongsTo(TingkatPenilaian::class, 'tingkat_id');
    }

    // Master indikator (deskripsi)
    public function indikator()
    {
        return $this->belongsTo(IndikatorVariabel::class, 'indikator_id');
    }

    // ===== History jawaban (banyak); gunakan ini jika perlu semua versi
    public function jawaban()
    {
        return $this->hasMany(JawabanIndikator::class, 'penilaian_detail_id');
    }

    // ===== Jawaban terbaru (yang aktif untuk verifikasi)
    public function jawabanLatest()
    {
        return $this->hasOne(JawabanIndikator::class, 'penilaian_detail_id')
            ->where('is_latest', 1)
            ->latest('id'); // kalau ada dua flag is_latest karena data lama, ambil id terbesar
    }

    // Semua dokumen (seluruh history jawaban)
    public function dokumen()
    {
        return $this->hasManyThrough(
            DokumenIndikator::class,
            JawabanIndikator::class,
            'penilaian_detail_id', // FK di jawaban
            'jawaban_id',          // FK di dokumen
            'id',                  // local key di detail
            'id'                   // local key di jawaban
        );
    }

    // Dokumen dari jawaban terbaru saja (paling sering dibutuhkan)
    public function dokumenLatest()
    {
        return $this->hasManyThrough(
            DokumenIndikator::class,
            JawabanIndikator::class,
            'penilaian_detail_id',
            'jawaban_id',
            'id',
            'id'
        )->whereHas('jawaban', function ($q) {
            $q->where('is_latest', 1);
        });
    }
}

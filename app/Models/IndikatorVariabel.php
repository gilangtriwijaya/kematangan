<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndikatorVariabel extends Model
{
    use HasFactory;

    protected $table = 'indikator_variabel';

    protected $fillable = [
        'variabel_id',
        'tingkat_id',
        'deskripsi',
        'jumlah_bukti',
    ];

    /**
     * Relasi ke variabel penilaian (satu indikator milik satu variabel)
     */
    public function variabel()
    {
        return $this->belongsTo(VariabelPenilaian::class, 'variabel_id');
    }

    /**
     * Relasi ke tingkat penilaian (satu indikator milik satu tingkat)
     */
    public function tingkat()
    {
        return $this->belongsTo(\App\Models\TingkatPenilaian::class, 'tingkat_id');
    }

    /**
     * Relasi ke dokumen bukti (satu indikator bisa punya banyak dokumen bukti)
     */
    public function bukti()
    {
        return $this->hasMany(\App\Models\IndikatorDokumen::class, 'indikator_id')->orderBy('urutan');
    }
}

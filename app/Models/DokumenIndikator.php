<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage; // <— penting
use Illuminate\Support\Str;             // <— untuk afterLast()


class DokumenIndikator extends Model
{
    use HasFactory;

    protected $table = 'dokumen_indikator';

    protected $fillable = [
        'jawaban_id',
        'nama_dokumen',
        'file_path',
        'status'
    ];

    public $timestamps = true;

    // Relasi ke jawaban indikator
    public function jawaban()
    {
        return $this->belongsTo(JawabanIndikator::class, 'jawaban_id');
    }
    
    protected $appends = ['filename', 'url'];

    public function getFilenameAttribute() {
        return $this->nama_dokumen ?: Str::afterLast($this->file_path, '/');
    }
    
    public function getUrlAttribute() {
        return Storage::disk('public')->url($this->file_path);
    }
}

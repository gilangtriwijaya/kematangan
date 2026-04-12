<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KegiatanPenilaian extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_penilaian';

    /**
     * Disarankan kolom: id, nama, slug, tahun, deskripsi, tanggal_mulai, tanggal_selesai, is_aktif, created_at, updated_at
     */
    protected $fillable = [
        'nama',
        'slug',            // <— penting untuk "menu per jenis"
        'tahun',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif'        => 'boolean',
        'tahun'           => 'integer',
        'tanggal_mulai'   => 'date',   // gunakan 'immutable_datetime' jika perlu
        'tanggal_selesai' => 'date',
    ];

    /* ==========================
     *          SCOPES
     * ========================== */

    /** Ambil yang is_aktif = 1 */
    public function scopeAktif($q)
    {
        return $q->where('is_aktif', true);
    }

    /** Filter berdasarkan slug (jenis kegiatan) */
    public function scopeBySlug($q, string $slug)
    {
        return $q->where('slug', $slug);
    }

    /** Filter berdasarkan tahun tertentu */
    public function scopeTahun($q, int $tahun)
    {
        return $q->where('tahun', $tahun);
    }

    /** Urut terbaru (tahun desc, lalu id desc untuk deterministik) */
    public function scopeTerbaru($q)
    {
        return $q->orderByDesc('tahun')->orderByDesc('id');
    }

    /** Sedang berjalan (hari ini berada di antara tanggal_mulai & tanggal_selesai) */
    public function scopeBerjalan($q)
    {
        $today = now()->startOfDay();
        return $q->whereDate('tanggal_mulai', '<=', $today)
                 ->whereDate('tanggal_selesai', '>=', $today);
    }

    /* ==========================
     *        RELATIONSHIPS
     * ========================== */

    public function variabels()
    {
        return $this->hasMany(VariabelPenilaian::class, 'kegiatan_id');
    }

    public function penilaians()
    {
        return $this->hasMany(Penilaian::class, 'kegiatan_id');
    }

    /* ==========================
     *        ACCESSORS / MUTATORS
     * ========================== */

    /**
     * Pastikan slug terisi otomatis ketika nama di-set dan slug kosong.
     * – Tidak akan menimpa slug jika sudah ada (agar bisa dikontrol admin).
     */
    public function setNamaAttribute($value): void
    {
        $this->attributes['nama'] = $value;

        // Jika slug belum diisi, auto-generate dari nama
        if (empty($this->attributes['slug']) && !empty($value)) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Sanitasi slug apabila diberikan kosong/null.
     */
    public function setSlugAttribute($value): void
    {
        $value = trim((string) $value);
        $this->attributes['slug'] = $value !== '' ? Str::slug($value) : null;
    }

    /* ==========================
     *           HELPERS
     * ========================== */

    /**
     * Ambil satu kegiatan yang aktif untuk slug tertentu.
     * Jika ada lebih dari satu aktif (tidak ideal), diambil yang paling baru.
     */
    public static function aktifUntukSlug(string $slug): ?self
    {
        return static::aktif()->bySlug($slug)->terbaru()->first();
    }

    /**
     * Jadikan model ini aktif dan nonaktifkan kegiatan lain dengan slug yang sama.
     * Gunakan di Admin agar satu slug hanya punya satu yang aktif.
     */
    public function setAktifEksklusif(): void
    {
        static::where('slug', $this->slug)
            ->where('id', '!=', $this->id)
            ->update(['is_aktif' => false]);

        $this->is_aktif = true;
        $this->save();
    }

    /**
     * Apakah tanggal hari ini berada di rentang kegiatan.
     */
    public function getSedangBerjalanAttribute(): bool
    {
        if (!$this->tanggal_mulai || !$this->tanggal_selesai) {
            return false;
        }
        $today = now()->startOfDay();
        return $today->between(
            $this->tanggal_mulai->startOfDay(),
            $this->tanggal_selesai->endOfDay()
        );
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StatistikPublikService
{
    /**
     * Payload statistik untuk 1 kegiatan (tahun).
     * Dipakai OLEH API PUBLIK (website Indeks), bukan dashboard internal.
     */
    public function buildPayload(int $kegiatanId): array
    {
        // Rentang kategori (inklusif pada batas atas; segmen terakhir terbuka)
        $kategoriCfg = [
            ['nama' => 'Sangat Rendah', 'min' => 10.0, 'max' => 19.0],
            ['nama' => 'Rendah',         'min' => 19.1, 'max' => 28.0],
            ['nama' => 'Sedang',         'min' => 28.1, 'max' => 37.0],
            ['nama' => 'Tinggi',         'min' => 37.1, 'max' => 46.0],
            ['nama' => 'Sangat Tinggi',  'min' => 46.1, 'max' => 55.0],
        ];

        /**
         * Subquery: ambil baris penilaian TERBARU per (user, variabel) di kegiatan ini.
         * Catatan: ini hanya menentukan “versi terbaru”, filter status dilakukan di agregasi.
         */
        $latestBase = DB::table('penilaian')
            ->where('kegiatan_id', $kegiatanId)
            ->select('user_id', 'variabel_id', 'kegiatan_id')
            ->selectRaw('MAX(updated_at) as latest_updated, MAX(id) as latest_id')
            ->groupBy('user_id', 'variabel_id', 'kegiatan_id');

        $latest = DB::table('penilaian as p1')
            ->joinSub($latestBase, 't', function ($join) {
                $join->on('p1.user_id', '=', 't.user_id')
                     ->on('p1.variabel_id', '=', 't.variabel_id')
                     ->on('p1.kegiatan_id', '=', 't.kegiatan_id')
                     ->where(function ($q) {
                         $q->whereColumn('p1.updated_at', 't.latest_updated')
                           ->orWhereColumn('p1.id', 't.latest_id');
                     });
            })
            ->select('p1.id');

        /**
         * >>> Perbaikan UTAMA: semua agregasi skor hanya menghitung baris
         *     yang sudah TERVERIFIKASI di tabel penilaian (p.status = 'diverifikasi').
         */

        // Skor total per OPD (akumulasi variabel) – HANYA diverifikasi
        $perOpdRows = DB::table('penilaian as p')
            ->joinSub($latest, 'l', 'p.id', '=', 'l.id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.kegiatan_id', $kegiatanId)
            ->where('u.role', 'opd')
            ->where('p.status', 'diverifikasi')                  // << filter status
            ->groupBy('p.user_id', 'u.name')
            ->selectRaw('p.user_id as uid, u.name as nama_singkat, SUM(p.total_poin) as skor')
            ->orderByDesc('skor')
            ->get();

        $perOpd = $perOpdRows->map(fn ($r) => [
            'uid'  => (int) $r->uid,
            'nama' => (string) $r->nama_singkat,
            'skor' => (float) $r->skor,
        ])->values();

        // Ringkasan (menggunakan skor diverifikasi)
        $totalOpd = (int) DB::table('users')->where('role', 'opd')->count(); // contoh: 34
        $sumSkor  = (float) $perOpd->sum('skor');

        // Total nilai kabupaten = (jumlah skor diverifikasi) / (total OPD)
        $totalNilaiKabupaten = $totalOpd > 0 ? round($sumSkor / $totalOpd, 2) : 0.0;

        // Donut kategori: klasifikasikan OPD yang sudah punya skor diverifikasi
        $kategoriCount = [];
        foreach ($kategoriCfg as $k) {
            $kategoriCount[$k['nama']] = 0;
        }
        foreach ($perOpd as $o) {
            $kategoriCount[$this->kategoriDariSkor($o['skor'], $kategoriCfg)]++;
        }

        // Bar Variabel – jumlah poin lintas OPD per variabel – HANYA diverifikasi
        $perVariabel = DB::table('penilaian as p')
            ->joinSub($latest, 'l', 'p.id', '=', 'l.id')
            ->join('variabels as v', 'v.id', '=', 'p.variabel_id')
            ->where('p.kegiatan_id', $kegiatanId)
            ->where('p.status', 'diverifikasi')                  // << filter status
            ->groupBy('v.id', 'v.nama', 'v.urutan')
            ->selectRaw('v.id, v.nama, v.urutan, SUM(p.total_poin) as total_poin')
            ->orderBy('v.urutan')
            ->get();

        $barVarLabels = $perVariabel->pluck('nama')->values()->all();
        $barVarData   = $perVariabel->pluck('total_poin')->map(fn ($v) => (float) $v)->values()->all();

        /**
         * Persentase pengisian: tetap mengukur keterisian (latest), bukan verifikasi.
         * Kalau ingin berbasis verifikasi, tambah where('p.status','diverifikasi') juga di dua query bawah.
         */
        $totalVariabel = (int) DB::table('variabels')->where('kegiatan_id', $kegiatanId)->count();

        $opdMengisi = (int) DB::table('penilaian as p')
            ->joinSub($latest, 'l', 'p.id', '=', 'l.id')
            ->where('p.kegiatan_id', $kegiatanId)
            ->distinct('p.user_id')
            ->count('p.user_id');

        $pairTerisi = (int) DB::table('penilaian as p')
            ->joinSub($latest, 'l', 'p.id', '=', 'l.id')
            ->where('p.kegiatan_id', $kegiatanId)
            ->distinct()
            ->count(DB::raw("CONCAT(p.user_id,'-',p.variabel_id)"));

        $denom = max(1, $totalOpd * max(1, $totalVariabel));
        $persenPengisian = round(($pairTerisi / $denom) * 100, 2);

        return [
            'kegiatan_id' => $kegiatanId,
            'updated_at'  => now()->toIso8601String(),

            'ringkasan' => [
                'total_nilai_kabupaten' => $totalNilaiKabupaten,
                'persentase_pengisian'  => $persenPengisian,
                'total_opd_mengisi'     => $opdMengisi,
                'total_opd'             => $totalOpd,
            ],

            'donut_kategori' => [
                'labels' => array_keys($kategoriCount),
                'data'   => array_values($kategoriCount),
            ],

            'bar_opd' => [
                'labels' => $perOpd->pluck('nama')->values()->all(),
                'data'   => $perOpd->pluck('skor')->map(fn ($v) => (float) $v)->values()->all(),
            ],

            'bar_variabel' => [
                'labels' => $barVarLabels,
                'data'   => $barVarData,
            ],
        ];
    }

    private function kategoriDariSkor(float $skor, array $cfg): string
    {
        $last = count($cfg) - 1;
        foreach ($cfg as $i => $k) {
            $min = (float) $k['min'];
            $max = (float) $k['max'];
            if (($skor >= $min && $skor <= $max) || ($i === $last && $skor >= $min)) {
                return (string) $k['nama'];
            }
        }
        return (string) ($cfg[0]['nama'] ?? 'Tidak Diketahui');
    }
}

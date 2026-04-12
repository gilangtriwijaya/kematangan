<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PenilaianStatusService
{
    /**
     * Ambil status & poin TERBARU per variabel untuk 1 user pada 1 kegiatan.
     * - Satu baris per variabel (latest pd.id)
     * - Status prioritas: terverifikasi > ditolak > draft > none
     * - Poin dijumlahkan hanya jika status 'terverifikasi' (0..5)
     */
    public function latestPerVariabelForUserKegiatan(int $userId, int $kegiatanId): array
    {
        // Tentukan pd.id TERBARU per variabel
        $latestPerVar = DB::table('penilaian_detail as pd')
            ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
            ->where('p.user_id', $userId)
            ->where('p.kegiatan_id', $kegiatanId)
            ->groupBy('pd.variabel_id')
            ->select('pd.variabel_id', DB::raw('MAX(pd.id) AS latest_pd_id'));

        // Ambil baris terbaru tsb + SATU jawaban latest (kalau ada)
        $base = DB::table('penilaian_detail as pd')
            ->joinSub($latestPerVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->leftJoin('jawaban_indikator as ji', function ($j) {
                // Ambil hanya jawaban yang bertanda latest
                $j->on('ji.penilaian_detail_id', '=', 'pd.id')
                  ->where('ji.is_latest', 1);
            })
            ->select([
                'pd.variabel_id',
                'pd.status as detail_status',
                DB::raw('COALESCE(pd.poin, 0) as poin'),
                'ji.status as jawaban_status',
            ]);

        $rows = $base->get();

        $statusMap    = [];
        $poinVar      = [];
        $varSubmitted = 0;
        $totalPoin    = 0;

        foreach ($rows as $r) {
            // Resolusi status (prioritas pada status detail)
            $st = 'none';
            if ($r->detail_status === 'terverifikasi') {
                $st = 'terverifikasi';
            } elseif ($r->detail_status === 'ditolak') {
                $st = 'ditolak';
            } elseif ($r->jawaban_status === 'draft' || $r->detail_status === 'draft') {
                $st = 'draft';
            }

            $statusMap[$r->variabel_id] = $st;

            // Poin hanya dihitung kalau terverifikasi, dibatasi 0..5
            $p = ($st === 'terverifikasi') ? (int) $r->poin : 0;
            if ($p < 0) $p = 0;
            if ($p > 5) $p = 5;
            $poinVar[$r->variabel_id] = $p;

            // latest per variabel = satu baris → dianggap "sudah submit"
            $varSubmitted++;
            $totalPoin += $p;
        }

        return [
            'statusMap'    => $statusMap,   // [variabel_id => 'terverifikasi'|'ditolak'|'draft'|'none']
            'poinVar'      => $poinVar,     // [variabel_id => 0..5]
            'varSubmitted' => $varSubmitted,
            'totalPoin'    => $totalPoin,   // total poin OPD (sum 11 variabel, max 55)
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\KegiatanPenilaian;
use App\Services\PenilaianStatusService;
use App\Services\SsoOpdService;

class DashboardApiController extends Controller
{
    public function __construct(private PenilaianStatusService $statusSvc, private SsoOpdService $ssoSvc) {}

    /** Halaman (dropdown kegiatan) untuk semua role dikendalikan oleh DashboardPageController.
     *  Controller ini hanya menyediakan endpoint JSON: summary() & detail().
     */

    /** Kategori poin kabupaten (skala 10..55, rata-rata OPD) */
    private function kategoriPoin(float $nilai): string
    {
        if ($nilai >= 46.1) return 'Sangat Tinggi';
        if ($nilai >= 37.1) return 'Tinggi';
        if ($nilai >= 28.1) return 'Sedang';
        if ($nilai >= 19.1) return 'Rendah';
        if ($nilai >= 10.0) return 'Sangat Rendah';
        return 'Sangat Rendah';
    }

    /** ===================== SUMMARY (KPI + Donut) ===================== */
    public function summary(Request $request)
    {
        $kegiatan_id = (int) $request->query('kegiatan_id')
        ?: (int) KegiatanPenilaian::orderByDesc('tahun')->value('id');

    if (!$kegiatan_id) {
        return response()->json(['error' => 'Belum ada kegiatan'], 404);
    }

    $user = auth()->user();
    $role = strtolower($user?->role ?? 'opd');

    // BUMP versi cache biar nggak narik data lama
    $CACHE_VER = 'v10_avg34';
    $cacheKey  = sprintf('dash:%s:summary:%s:%d:%d', $CACHE_VER, $role, (int)($user?->id ?? 0), $kegiatan_id);

    // TTL kecil saat debug
    $TTL_SECONDS = 10;

    return \Cache::store('file')->remember($cacheKey, $TTL_SECONDS, function () use ($kegiatan_id, $user, $role) {
        // Ambil variabel dari tabel 'variabels'
        $variabels = \DB::table('variabels')
            ->where('kegiatan_id', $kegiatan_id)
            ->orderBy('urutan')
            ->get(['id','nama']);
        $varTotal = (int) $variabels->count();
            // ===================== OPD =====================
            if ($role === 'opd') {
                $uid = (int) $user->id;

                // id detail TERBARU per variabel untuk user & kegiatan
                $latestUserVar = DB::table('penilaian_detail as pd')
                    ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
                    ->where('p.kegiatan_id', $kegiatan_id)
                    ->where('p.user_id', $uid)
                    ->select('pd.variabel_id', DB::raw('MAX(pd.id) as latest_pd_id'))
                    ->groupBy('pd.variabel_id');

                // baris terakhir per variabel (jangan select pd.*)
                $latestRowsUser = DB::table('penilaian_detail as pd')
                    ->joinSub($latestUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
                    ->select('pd.variabel_id','pd.status','pd.poin');

                // variabel yang sudah pernah mengirim
                $varSubmitted = (int) (clone $latestRowsUser)->count();

                // total poin terverifikasi untuk OPD ini
                $totalPoin = (int) (clone $latestRowsUser)
                    ->where('pd.status', 'terverifikasi')
                    ->sum('pd.poin');

                // bar chart: total poin terverifikasi per variabel
                $poinPerVar = DB::table('penilaian_detail as pd')
                    ->joinSub($latestUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
                    ->where('pd.status', 'terverifikasi')
                    ->select('pd.variabel_id', DB::raw('SUM(pd.poin) as total'))
                    ->groupBy('pd.variabel_id')
                    ->pluck('total', 'pd.variabel_id');

                $barLabels = $variabels->pluck('nama')->values()->all();
                $barValues = $variabels->pluck('id')
                    ->map(fn($id) => (int)($poinPerVar[$id] ?? 0))
                    ->values()->all();

                return response()->json([
                    'role'   => 'opd',
                    'totals' => [
                        'var_total'     => $varTotal,
                        'var_submitted' => $varSubmitted,
                    ],
                    'poin' => [
                        'your_opd'        => $totalPoin,
                        'total_kabupaten' => null,
                        'kategori'        => null,
                    ],
                    'progress' => [
                        'completion_rate' => $varTotal > 0 ? round($varSubmitted / $varTotal, 4) : 0.0,
                    ],
                    'variabel_bar' => [
                        'labels' => $barLabels,
                        'values' => $barValues,
                    ],
                ]);
            }

              // ================= Admin / Superadmin =================
                  // Apply SSO OPD restriction (if present)
                  $allowed = $this->ssoSvc->getAllowedOpdUserIds($user, 'kematangan');

                  $opdQuery = \DB::table('users')->whereRaw('LOWER(role) = ?', ['opd']);
                  if (is_array($allowed)) {
                        $opdQuery->whereIn('id', $allowed);
                  }
                  $opdTotal = (int) $opdQuery->count();

        $latestPerUserVar = \DB::table('penilaian_detail as pd')
            ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
            ->where('p.kegiatan_id', $kegiatan_id)
            ->select('p.user_id', 'pd.variabel_id', \DB::raw('MAX(pd.id) as latest_pd_id'))
            ->groupBy('p.user_id','pd.variabel_id');

        if (is_array($allowed)) {
            // restrict by allowed OPD user ids
            $latestPerUserVar->whereIn('p.user_id', $allowed);
        }

        // gunakan kolom minimal
        $latestRows = \DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->select('lv.user_id','pd.variabel_id','pd.status','pd.poin');

        $opdMengisi = (int) (clone $latestRows)->distinct('user_id')->count('user_id');
        $draftCount = (int) (clone $latestRows)->where('pd.status','draft')->count();
        $tolakCount = (int) (clone $latestRows)->where('pd.status','ditolak')->count();
        $verifCount = (int) (clone $latestRows)->where('pd.status','terverifikasi')->count();

        // Persentase = entri verifikasi / (OPD × Variabel)
        $expectedEntries = $opdTotal * $varTotal;
        $completionRate  = $expectedEntries > 0 ? round($verifCount / $expectedEntries, 4) : 0.0;

        // Total poin kabupaten (sum latest terverifikasi)
        $totalKabupaten = (int) (clone $latestRows)
            ->where('pd.status','terverifikasi')
            ->sum('pd.poin');

        // >>> RATA-RATA poin kabupaten = total poin ÷ 34 (pembagi tetap)
        $DIVISOR_FIXED  = 34;
        $nilaiKabupaten = $DIVISOR_FIXED > 0 ? round($totalKabupaten / $DIVISOR_FIXED, 2) : 0.0;

        // Kategori memakai SKALA 10..55 berdasarkan nilai rata-rata
        $kategori       = $this->kategoriPoin($nilaiKabupaten);

        // Bar chart total poin per variabel (latest & verif) — TETAP
        $varAgg = \DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->where('pd.status', 'terverifikasi')
            ->select('pd.variabel_id', \DB::raw('SUM(pd.poin) as total'))
            ->groupBy('pd.variabel_id')
            ->pluck('total', 'pd.variabel_id');

        $barLabels = $variabels->pluck('nama')->values()->all();
        $barValues = $variabels->pluck('id')
            ->map(fn($id) => (int)($varAgg[$id] ?? 0))
            ->values()->all();

        return response()->json([
            'role'   => 'admin',
            'totals' => [
                'var_total'        => $varTotal,
                'opd_total'        => $opdTotal,
                'opd_mengisi'      => $opdMengisi,
                'draft'            => $draftCount,
                'ditolak'          => $tolakCount,
                'terverifikasi'    => $verifCount,
                'expected_entries' => $expectedEntries,
                'verified_entries' => $verifCount,
            ],
            'poin' => [
                'total_kabupaten' => (int) $totalKabupaten,   // sum (untuk footer tabel)
                'nilai_kabupaten' => $nilaiKabupaten,         // <<=== dipakai KPI (rata-rata ÷34)
                'kategori'        => $kategori,
            ],
            'progress' => [
                'completion_rate' => $completionRate,
            ],
            'variabel_bar' => [
                'labels' => $barLabels,
                'values' => $barValues,
            ],
        ]);
        });
    }

    /** ======================= DETAIL (Tables + Var Chart) ======================= */
    public function detail(Request $request)
    {
        $kegiatan_id = (int) $request->query('kegiatan_id')
            ?: (int) KegiatanPenilaian::orderByDesc('tahun')->value('id');

        if (!$kegiatan_id) {
            return response()->json([
                'variabels'  => [],
                'tracking'   => [],
                'var_chart'  => [],
                'opd_scores' => [],
            ]);
        }

        $user = auth()->user();
        $role = strtolower($user?->role ?? 'opd');

        // Variabel list
        $variabels = DB::table('variabels')
            ->where('kegiatan_id', $kegiatan_id)
            ->orderBy('urutan')
            ->get(['id','nama']);

        // ===================== OPD =====================
        if ($role === 'opd') {
            $uid       = (int) $user->id;
            $agg       = $this->statusSvc->latestPerVariabelForUserKegiatan($uid, $kegiatan_id);
            $statusMap = $agg['statusMap'];
            $poinVar   = $agg['poinVar'];

            $tracking = [];
            foreach ($variabels as $i => $v) {
                $tracking[] = [
                    'no'       => $i + 1,
                    'variabel' => $v->nama,
                    'status'   => $statusMap[$v->id] ?? 'none',
                    'poin'     => (int) ($poinVar[$v->id] ?? 0),
                ];
            }

            $var_chart = [];
            foreach ($variabels as $v) {
                $var_chart[] = [
                    'variabel'   => $v->nama,
                    'total_poin' => (int) ($poinVar[$v->id] ?? 0),
                ];
            }

            return response()->json([
                'variabels'  => $variabels->map(fn($v)=>['id'=>$v->id,'nama'=>$v->nama])->values(),
                'tracking'   => $tracking,
                'var_chart'  => $var_chart,
                'opd_scores' => [],
            ]);
        }

        // ===================== Admin / Superadmin =====================
        $opds = User::where('role','opd')->orderBy('name')->get(['id','name']);

        $latestPerUserVar = DB::table('penilaian_detail as pd')
            ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
            ->where('p.kegiatan_id', $kegiatan_id)
            ->select('p.user_id','pd.variabel_id', DB::raw('MAX(pd.id) as latest_pd_id'))
            ->groupBy('p.user_id','pd.variabel_id');

        // Basis latest rows (tanpa pd.*)
        $latestRows = DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->select('lv.user_id','pd.variabel_id','pd.status','pd.poin');

        // Skor OPD = sum poin TERBARU yang terverifikasi
        $poinPerUser = DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->where('pd.status', 'terverifikasi')
            ->select(DB::raw('SUM(pd.poin) as poin'), DB::raw('lv.user_id as uid'))
            ->groupBy('uid')
            ->pluck('poin', 'uid');

        $opd_scores = [];
        foreach ($opds as $u) {
            $p = (float) ($poinPerUser[$u->id] ?? 0);
            $opd_scores[] = [
                'user_id'  => $u->id,
                'nama'     => $u->name,
                'poin'     => $p,
                'kategori' => $p > 0 ? $this->kategoriPoin($p) : '-',
            ];
        }
        usort($opd_scores, fn($a,$b) => $b['poin'] <=> $a['poin']);

        // Tracking status per variabel (query terpisah, tidak pakai GROUP BY -> aman memakai pd.id)
        $rows = DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->leftJoin('jawaban_indikator as ji', function ($j) {
                $j->on('ji.penilaian_detail_id', '=', 'pd.id')->where('ji.is_latest', 1);
            })
            ->select('lv.user_id','pd.variabel_id','pd.status as detail_status','ji.status as jawaban_status')
            ->get();

        $statusMap = [];
        foreach ($rows as $r) {
            $st = 'none';
            if     ($r->detail_status === 'terverifikasi') $st = 'terverifikasi';
            elseif ($r->detail_status === 'ditolak')       $st = 'ditolak';
            elseif ($r->jawaban_status === 'draft' || $r->detail_status === 'draft') $st = 'draft';
            $statusMap[$r->user_id][$r->variabel_id] = $st;
        }

        $tracking = [];
        foreach ($opds as $u) {
            $row = [
                'user_id' => $u->id,
                'nama'    => $u->name,
                'status'  => [],
                'total'   => (float) ($poinPerUser[$u->id] ?? 0),
            ];
            foreach ($variabels as $v) {
                $row['status'][$v->id] = $statusMap[$u->id][$v->id] ?? 'none';
            }
            $tracking[] = $row;
        }

        // Chart variabel (sum TERBARU terverifikasi)
        $aggVar = DB::table('penilaian_detail as pd')
            ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
            ->where('pd.status', 'terverifikasi')
            ->select('pd.variabel_id', DB::raw('SUM(pd.poin) as total'))
            ->groupBy('pd.variabel_id')
            ->pluck('total', 'pd.variabel_id');

        $var_chart = [];
        foreach ($variabels as $v) {
            $var_chart[] = [
                'variabel'   => $v->nama,
                'total_poin' => (int) ($aggVar[$v->id] ?? 0),
            ];
        }

        return response()->json([
            'variabels'  => $variabels->map(fn($v)=>['id'=>$v->id,'nama'=>$v->nama])->values(),
            'opd_scores' => $opd_scores,
            'tracking'   => $tracking,
            'var_chart'  => $var_chart,
        ]);
    }
}

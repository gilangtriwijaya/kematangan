<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\KegiatanPenilaian;
use App\Models\Penilaian;
use App\Models\PenilaianDetail;
use App\Models\JawabanIndikator;
use App\Models\DokumenIndikator;
use App\Models\TingkatPenilaian;
use App\Services\SsoOpdService;

class VerifikasiController extends Controller
{
    public function __construct(private SsoOpdService $ssoSvc) {}

    /**
     * Dashboard ringkas per OPD (tetap pakai latest).
     */
    public function index(Request $request)
    {
        $kegiatan_id    = (int) $request->get('kegiatan_id');
        $daftarKegiatan = KegiatanPenilaian::orderByDesc('tahun')->get(['id','nama','tahun','is_aktif']);

        if (!$kegiatan_id) {
            $kegiatanAktif = $daftarKegiatan->firstWhere('is_aktif', 1) ?? $daftarKegiatan->first();
            $kegiatan_id   = (int) ($kegiatanAktif?->id ?? 0);
        }

        $authUser = auth()->user();
        $allowed = [];
        try {
            $allowed = $this->ssoSvc->getAllowedOpdUserIds($authUser, 'kematangan') ?? [];
        } catch (\Throwable $e) {
            $allowed = [];
        }

        $usersQuery = User::whereRaw('LOWER(role)=?', ['opd'])->orderBy('name');
        if (is_array($allowed) && count($allowed) > 0) {
            $usersQuery->whereIn('id', $allowed);
        }
        $users = $usersQuery->get(['id','name']);

        $rows = [];
        if ($kegiatan_id) {
            // Ambil ID penilaian_detail terbaru per USER × VARIABEL
            $latestPerUserVar = DB::table('penilaian_detail as pd')
                ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
                ->where('p.kegiatan_id', $kegiatan_id)
                ->select('p.user_id', 'pd.variabel_id', DB::raw('MAX(pd.id) as latest_pd_id'))
                ->groupBy('p.user_id', 'pd.variabel_id');

            if (is_array($allowed) && count($allowed) > 0) {
                $latestPerUserVar->whereIn('p.user_id', $allowed);
            }

            // Join ke baris pd tersebut + ambil JAWABAN TERBARU (is_latest=1)
            $latestRows = DB::table('penilaian_detail as pd')
                ->joinSub($latestPerUserVar, 'lv', 'lv.latest_pd_id', '=', 'pd.id')
                ->leftJoin('jawaban_indikator as ji', function ($j) {
                    $j->on('ji.penilaian_detail_id', '=', 'pd.id')->where('ji.is_latest', 1);
                })
                ->select(
                    'lv.user_id',
                    'lv.variabel_id',
                    'pd.id as pd_id',
                    DB::raw("COALESCE(ji.status, 'none') as jstatus")
                );

            // Agregasi per user memakai ji.status (latest)
            $aggRows = DB::table(DB::raw("({$latestRows->toSql()}) as t"))
                ->mergeBindings($latestRows)
                ->select(
                    't.user_id',
                    DB::raw('COUNT(*) AS terisi'),
                    DB::raw("SUM(CASE WHEN t.jstatus='diterima' THEN 1 ELSE 0 END) AS verif"),
                    DB::raw("SUM(CASE WHEN t.jstatus='draft'    THEN 1 ELSE 0 END) AS pending"),
                    DB::raw("SUM(CASE WHEN t.jstatus='ditolak'  THEN 1 ELSE 0 END) AS ditolak")
                )
                ->groupBy('t.user_id')
                ->get()
                ->keyBy('user_id');

            // Hitung OPD yang sudah mengisi
            $opdMengisi = (int) DB::table(DB::raw("({$latestRows->toSql()}) as u"))
                ->mergeBindings($latestRows)
                ->distinct()->count('u.user_id');

            foreach ($users as $u) {
                $r        = $aggRows[$u->id] ?? null;
                $terisi   = (int) ($r->terisi   ?? 0);
                $verif    = (int) ($r->verif    ?? 0);
                $pending  = (int) ($r->pending  ?? 0);
                $ditolak  = (int) ($r->ditolak  ?? 0);
                $den      = $verif + $pending; // 'ditolak' tak dihitung progres
                $progress = $den > 0 ? (int) round(($verif / $den) * 100) : 0;

                $rows[] = [
                    'user_id'  => $u->id,
                    'nama'     => $u->name,
                    'terisi'   => $terisi,
                    'verif'    => $verif,
                    'pending'  => $pending,
                    'ditolak'  => $ditolak,
                    'progress' => $progress,
                ];
            }

            $this->opd_total   = (int) $users->count();
            $this->opd_mengisi = $opdMengisi;
        } else {
            foreach ($users as $u) {
                $rows[] = [
                    'user_id'  => $u->id,
                    'nama'     => $u->name,
                    'terisi'   => 0,
                    'verif'    => 0,
                    'pending'  => 0,
                    'ditolak'  => 0,
                    'progress' => 0,
                ];
            }
            $this->opd_total   = (int) $users->count();
            $this->opd_mengisi = 0;
        }

        return view('verifikasi.index', [
            'rows'           => $rows,
            'kegiatan_id'    => $kegiatan_id,
            'daftarKegiatan' => $daftarKegiatan,
            'opd_total'      => $this->opd_total ?? (int) $users->count(),
            'opd_mengisi'    => $this->opd_mengisi ?? 0,
        ]);
    }

    /**
     * Detail per OPD: tampilkan HANYA jawaban & dokumen dari versi TERBARU.
     */
    public function detail(Request $request, $user_id)
    {
        $kegiatan_id = (int) $request->get('kegiatan_id');

        $user     = User::findOrFail($user_id);
        $kegiatan = KegiatanPenilaian::findOrFail($kegiatan_id);

        // Enforce SSO OPD restriction: jika ada pembatasan, pastikan user_id termasuk
        $authUser = auth()->user();
        $allowed = [];
        try { $allowed = $this->ssoSvc->getAllowedOpdUserIds($authUser, 'kematangan') ?? []; } catch (\Throwable $e) { $allowed = []; }
        if (is_array($allowed) && count($allowed) > 0 && !in_array((int)$user_id, $allowed, true)) {
            Log::warning('Verifikasi::detail denied by SSO mapping', ['auth_user_id' => $authUser->id ?? null, 'auth_sso_user_id' => $authUser->sso_user_id ?? null, 'target_user_id' => (int)$user_id, 'allowed' => $allowed]);
            abort(403, 'Akses ke OPD ini tidak diizinkan oleh SSO.');
        }

        // 1) ID penilaian_detail TERBARU per variabel (untuk user + kegiatan ini)
        $latestPerVar = DB::table('penilaian_detail as pd')
            ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
            ->where('p.user_id', $user_id)
            ->where('p.kegiatan_id', $kegiatan_id)
            ->select('pd.variabel_id', DB::raw('MAX(pd.id) as latest_pd_id'))
            ->groupBy('pd.variabel_id');

        // 2) Ambil PD (sebagai model) + eager basic relasi (variabel/tingkat/indikator)
        $penilaian = PenilaianDetail::with(['variabel','tingkat','indikator'])
            ->joinSub($latestPerVar, 'lv', 'lv.latest_pd_id', '=', 'penilaian_detail.id')
            ->orderBy('penilaian_detail.variabel_id')
            ->select('penilaian_detail.*') // penting agar dapat model utuh
            ->get();

        if ($penilaian->isEmpty()) {
            return view('verifikasi.detail', compact('user', 'kegiatan', 'penilaian'));
        }

        // 3) Ambil JAWABAN TERBARU (is_latest=1) untuk kumpulan PD di atas, lalu map by pd_id
        $pdIds = $penilaian->pluck('id')->all();

        $latestJawaban = JawabanIndikator::whereIn('penilaian_detail_id', $pdIds)
            ->where('is_latest', 1)
            ->orderBy('id') // urutan tidak penting, untuk konsistensi
            ->get()
            ->keyBy('penilaian_detail_id'); // -> [pd_id => JawabanIndikator]

        // 4) Ambil DOKUMEN hanya untuk JAWABAN TERBARU
        $latestJawabanIds = $latestJawaban->pluck('id')->all();
        $dokumenByJawaban = $latestJawabanIds
            ? DokumenIndikator::whereIn('jawaban_id', $latestJawabanIds)
                ->orderBy('id')
                ->get()
                ->groupBy('jawaban_id') // -> [jawaban_id => Collection<DokumenIndikator>]
            : collect();

        // 5) Tanamkan ke setiap PD agar view lama tetap jalan:
        //    - setRelation('jawabanLatest', JawabanIndikator|null)
        //    - setRelation('dokumen', Collection<DokumenIndikator> khusus jawaban latest)
        $penilaian->each(function (PenilaianDetail $pd) use ($latestJawaban, $dokumenByJawaban) {
            $ji = $latestJawaban->get($pd->id); // bisa null kalau belum ada jawaban sama sekali
            $pd->setRelation('jawabanLatest', $ji);
            $pd->setRelation('dokumen', $ji ? ($dokumenByJawaban->get($ji->id) ?? collect()) : collect());
        });

        return view('verifikasi.detail', compact('user', 'kegiatan', 'penilaian'));
    }

    /**
     * Guard: hanya boleh memproses jawaban latest & draft.
     */
    private function ensureApprovable(JawabanIndikator $jawaban): JawabanIndikator
    {
        $latest = JawabanIndikator::where('penilaian_detail_id', $jawaban->penilaian_detail_id)
            ->where('is_latest', 1)
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            abort(422, 'Data jawaban tidak ditemukan.');
        }
        if ($latest->id !== $jawaban->id) {
            abort(422, 'Sudah ada versi terbaru. Muat ulang halaman dan verifikasi versi terbaru.');
        }
        if ($latest->status !== 'draft') {
            abort(422, 'Hanya jawaban terbaru berstatus draft yang dapat diverifikasi (status: '.$latest->status.').');
        }
        return $latest;
    }

    public function approve(Request $request, JawabanIndikator $jawaban)
    {
        $jawaban = $this->ensureApprovable($jawaban);

        // verify current user is allowed to approve this OPD's data
        $penilaian = $jawaban->penilaian()->first();
        if ($penilaian) {
            $authUser = auth()->user();
            $allowed = [];
            try { $allowed = $this->ssoSvc->getAllowedOpdUserIds($authUser, 'kematangan') ?? []; } catch (\Throwable $e) { $allowed = []; }
            if (is_array($allowed) && count($allowed) > 0 && !in_array((int)$penilaian->user_id, $allowed, true)) {
                Log::warning('Verifikasi::approve denied by SSO mapping', ['auth_user_id' => $authUser->id ?? null, 'auth_sso_user_id' => $authUser->sso_user_id ?? null, 'target_user_id' => $penilaian->user_id ?? null, 'allowed' => $allowed]);
                abort(403, 'Akses ke OPD ini tidak diizinkan oleh SSO.');
            }
        }

        DB::transaction(function () use ($jawaban, $request) {
            // 1) Jawaban
            $jawaban->update([
                'status'         => 'diterima',
                'komentar'       => (string) $request->input('komentar', ''),
                'verifikator_id' => auth()->id(),
                'verified_at'    => now(),
            ]);

            // 2) Detail
            $detail = PenilaianDetail::lockForUpdate()->find($jawaban->penilaian_detail_id);
            $detail->status = 'terverifikasi';
            if (is_null($detail->poin)) {
                $detail->poin = (int) optional(TingkatPenilaian::find($detail->tingkat_id))->poin ?: 0;
            }
            $detail->save();

            // 3) Dokumen
            DokumenIndikator::where('jawaban_id', $jawaban->id)->update(['status' => 'diterima']);

            // 4) Master
            Penilaian::where('id', $detail->penilaian_id)->update([
                'status'     => 'diverifikasi',
                'tingkat_id' => $detail->tingkat_id,
                'total_poin' => (int) $detail->poin,
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, JawabanIndikator $jawaban)
    {
        $request->validate(['komentar' => 'required|string|max:1000']);
        $jawaban = $this->ensureApprovable($jawaban);

        // verify current user is allowed to reject this OPD's data
        $penilaian = $jawaban->penilaian()->first();
        if ($penilaian) {
            $authUser = auth()->user();
            $allowed = [];
            try { $allowed = $this->ssoSvc->getAllowedOpdUserIds($authUser, 'kematangan') ?? []; } catch (\Throwable $e) { $allowed = []; }
            if (is_array($allowed) && count($allowed) > 0 && !in_array((int)$penilaian->user_id, $allowed, true)) {
                Log::warning('Verifikasi::reject denied by SSO mapping', ['auth_user_id' => $authUser->id ?? null, 'auth_sso_user_id' => $authUser->sso_user_id ?? null, 'target_user_id' => $penilaian->user_id ?? null, 'allowed' => $allowed]);
                abort(403, 'Akses ke OPD ini tidak diizinkan oleh SSO.');
            }
        }

        DB::transaction(function () use ($jawaban, $request) {
            // 1) Jawaban
            $jawaban->update([
                'status'         => 'ditolak',
                'komentar'       => (string) $request->input('komentar'),
                'verifikator_id' => auth()->id(),
                'verified_at'    => now(),
            ]);

            // 2) Detail
            $detail = PenilaianDetail::lockForUpdate()->find($jawaban->penilaian_detail_id);
            $detail->status = 'ditolak';
            $detail->save();

            // 3) Dokumen
            DokumenIndikator::where('jawaban_id', $jawaban->id)->update(['status' => 'ditolak']);

            // 4) Master kembali ke draft
            Penilaian::where('id', $detail->penilaian_id)->update([
                'status'     => 'draft',
                'total_poin' => 0,
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * Simpan verifikasi (bulk via tombol di bawah tabel).
     * Sudah menggunakan only-latest di eager 'jawaban'.
     */
    public function simpan(Request $request, User $user, $kegiatan_id)
    {
        $data = $request->validate([
            'status'    => 'sometimes|array',
            'status.*'  => 'in:acc,revisi',
            'catatan'   => 'sometimes|array',
            'catatan.*' => 'nullable|string|max:1000',
        ]);

        $statuses = $data['status']  ?? [];
        $notes    = $data['catatan'] ?? [];

        // verify current user is allowed to process this OPD
        $authUser = auth()->user();
        $allowed = [];
        try { $allowed = $this->ssoSvc->getAllowedOpdUserIds($authUser, 'kematangan') ?? []; } catch (\Throwable $e) { $allowed = []; }
        if (is_array($allowed) && count($allowed) > 0 && !in_array((int)$user->id, $allowed, true)) {
            Log::warning('Verifikasi::simpan denied by SSO mapping', ['auth_user_id' => $authUser->id ?? null, 'auth_sso_user_id' => $authUser->sso_user_id ?? null, 'target_user_id' => $user->id ?? null, 'allowed' => $allowed]);
            abort(403, 'Akses ke OPD ini tidak diizinkan oleh SSO.');
        }

        if (empty($statuses)) {
            return redirect()
                ->route('verifikasi.index', ['kegiatan_id' => (int)$kegiatan_id])
                ->with('success', 'Tidak ada perubahan. Semua item sudah diproses.');
        }

        DB::beginTransaction();
        try {
            $detailIds = array_map('intval', array_keys($statuses));

            $details = PenilaianDetail::with(['jawaban' => function ($q) {
                    $q->where('is_latest', 1);
                }])
                ->whereIn('id', $detailIds)
                ->whereHas('penilaian', function ($q) use ($user, $kegiatan_id) {
                    $q->where('user_id', $user->id)
                      ->where('kegiatan_id', (int)$kegiatan_id);
                })
                ->lockForUpdate()
                ->get();

            foreach ($details as $detail) {
                $id      = $detail->id;
                $choice  = $statuses[$id] ?? null;     // 'acc' | 'revisi'
                $catatan = $notes[$id]    ?? null;

                if (!$choice) continue;
                if (!$detail->jawaban || (int) $detail->jawaban->is_latest !== 1) continue;

                $jawaban = $detail->jawaban;

                if ($choice === 'acc') {
                    // APPROVE
                    $jawaban->update([
                        'status'         => 'diterima',
                        'komentar'       => (string) $catatan,
                        'verifikator_id' => auth()->id(),
                        'verified_at'    => now(),
                    ]);

                    $detail->status = 'terverifikasi';
                    if (is_null($detail->poin)) {
                        $detail->poin = (int) optional(TingkatPenilaian::find($detail->tingkat_id))->poin ?: 0;
                    }
                    $detail->save();

                    DokumenIndikator::where('jawaban_id', $jawaban->id)->update(['status' => 'diterima']);

                    Penilaian::where('id', $detail->penilaian_id)->update([
                        'status'     => 'diverifikasi',
                        'tingkat_id' => $detail->tingkat_id,
                        'total_poin' => (int) $detail->poin,
                    ]);
                } else {
                    // REJECT
                    $jawaban->update([
                        'status'         => 'ditolak',
                        'komentar'       => (string) $catatan,
                        'verifikator_id' => auth()->id(),
                        'verified_at'    => now(),
                    ]);

                    $detail->status = 'ditolak';
                    $detail->save();

                    DokumenIndikator::where('jawaban_id', $jawaban->id)->update(['status' => 'ditolak']);

                    Penilaian::where('id', $detail->penilaian_id)->update([
                        'status'     => 'draft',
                        'total_poin' => 0,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('verifikasi.index', ['kegiatan_id' => (int)$kegiatan_id])
                ->with('success', 'Verifikasi untuk '.$user->name.' berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Verifikasi bulk gagal', ['err' => $e->getMessage()]);

            return redirect()
                ->route('verifikasi.detail', ['user' => $user->id, 'kegiatan_id' => (int)$kegiatan_id])
                ->with('error', 'Gagal menyimpan verifikasi: '.$e->getMessage());
        }
    }
}

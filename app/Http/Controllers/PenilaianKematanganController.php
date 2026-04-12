<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\KegiatanPenilaian;
use App\Models\VariabelPenilaian;
use App\Models\TingkatPenilaian;
use App\Models\Penilaian;
use App\Models\PenilaianDetail;
use App\Models\JawabanIndikator;
use App\Models\IndikatorVariabel;

class PenilaianKematanganController extends Controller
{
    /**
     * Halaman indeks penilaian per kegiatan (by slug).
     */
    public function index(Request $request, KegiatanPenilaian $kegiatan)
    {
        Log::info('PK@index', [
            'uid'   => Auth::id(),
            'role'  => Auth::user()->role ?? null,
            'slug'  => $kegiatan->slug,
            'kid'   => $kegiatan->id,
            'aktif' => (int) $kegiatan->is_aktif,
            'q'     => $request->query(),
        ]);

        $kegiatanId = (int) $kegiatan->id;
        $tingkat_id = $request->integer('tingkat_id');
        $tab        = $request->integer('tab');

        // Ambil variabel + relasi untuk form
        $variabels = VariabelPenilaian::with(['tingkat.bukti'])
            ->where('kegiatan_id', $kegiatanId)
            ->orderBy('urutan')
            ->get();

        // Kalau belum ada variabel, render "empty state" rapi
        if ($variabels->isEmpty()) {
            return view('penilaian-kematangan.index', [
                'variabels'       => collect(),
                'aktif'           => null,
                'terpilihTingkat' => null,
                'statusVariabel'  => [],
                'tingkatPerVar'   => [],
                'verifPerVar'     => [],
                'kegiatanAktif'   => $kegiatan,
            ]);
        }

        $aktifVar        = $variabels->firstWhere('id', $tab) ?? $variabels->first();
        $terpilihTingkat = null;

        // Saat user memilih tingkat (via dropdown)
        if ($tingkat_id && $aktifVar) {
            $terpilihTingkat = TingkatPenilaian::with(['indikator', 'bukti'])->find($tingkat_id);

            // 1) tingkat_id tidak ada → buang param & kembali ke tab yang sama
            if (!$terpilihTingkat) {
                return redirect()->route('penilaian-kematangan.index', [
                    'kegiatan' => $kegiatan->slug,
                    'tab'      => $aktifVar->id,
                ])->with('error', 'Tingkat yang dipilih tidak ditemukan.');
            }

            // 2) tingkat ada tapi tidak berpasangan dengan variabel aktif
            $indikator = IndikatorVariabel::where('tingkat_id', $tingkat_id)
                ->where('variabel_id', $aktifVar->id)
                ->first();

            if (!$indikator) {
                return redirect()->route('penilaian-kematangan.index', [
                    'kegiatan' => $kegiatan->slug,
                    'tab'      => $aktifVar->id,
                ])->with('error', 'Tingkat tidak sesuai dengan variabel yang dipilih.');
            }

            $terpilihTingkat->indikator = $indikator;
        }

        /**
         * Ambil "latest penilaian_detail (pd_id)" per variabel untuk user + kegiatan ini.
         * PENTING: gunakan alias (var_id, pd_id) agar aman untuk pluck/get.
         */
        $pdRows = DB::table('penilaian as p')
            ->leftJoin('penilaian_detail as pd', 'pd.penilaian_id', '=', 'p.id')
            ->where('p.user_id', Auth::id())
            ->where('p.kegiatan_id', $kegiatanId)
            ->groupBy('p.variabel_id')
            ->selectRaw('p.variabel_id as var_id, MAX(pd.id) as pd_id')
            ->get();

        // Bangun map:
        //   - $varToPdId  : [var_id => pd_id]
        //   - $mapPdToVar : [pd_id  => var_id]
        //   - $pdIds      : daftar pd_id
        $varToPdId  = [];
        $mapPdToVar = [];
        $pdIds      = [];
        foreach ($pdRows as $row) {
            $varId = (int) $row->var_id;
            $pdId  = (int) ($row->pd_id ?? 0);
            if ($pdId) {
                $varToPdId[$varId]  = $pdId;
                $mapPdToVar[$pdId]  = $varId;
                $pdIds[]            = $pdId;
            }
        }

        // (1) Label tingkat per variabel (lewat pd_id)
        $tingkatPerVar = [];
        if (!empty($pdIds)) {
            $rows = DB::table('penilaian_detail as pd')
                ->leftJoin('tingkat_penilaian as t', 't.id', '=', 'pd.tingkat_id')
                ->whereIn('pd.id', $pdIds)
                ->get(['pd.id as pd_id', 't.label as tingkat_label']);

            foreach ($rows as $r) {
                $varId = $mapPdToVar[(int) $r->pd_id] ?? null;
                if ($varId) {
                    $tingkatPerVar[$varId] = $r->tingkat_label;
                }
            }
        }

        // (2) Jumlah dokumen per variabel dari jawaban latest
        $statusVariabel = [];
        if (!empty($pdIds)) {
            $docRows = DB::table('jawaban_indikator as ji')
                ->whereIn('ji.penilaian_detail_id', $pdIds)
                ->where('ji.is_latest', 1)
                ->leftJoin('dokumen_indikator as di', 'di.jawaban_id', '=', 'ji.id')
                ->groupBy('ji.penilaian_detail_id')
                ->select('ji.penilaian_detail_id', DB::raw('COUNT(di.id) as jml'))
                ->get();

            foreach ($docRows as $r) {
                $varId = $mapPdToVar[(int) $r->penilaian_detail_id] ?? null;
                if ($varId) {
                    $statusVariabel[$varId] = (int) $r->jml;
                }
            }
        }
        // Defaultkan 0 untuk variabel yang belum punya dokumen
        foreach ($variabels as $v) {
            $statusVariabel[$v->id] = (int) ($statusVariabel[$v->id] ?? 0);
        }

        // (3) Status verifikasi per variabel dari jawaban latest
        $verifPerVar = [];
        if (!empty($pdIds)) {
            $verRows = DB::table('jawaban_indikator')
                ->whereIn('penilaian_detail_id', $pdIds)
                ->where('is_latest', 1)
                ->get(['penilaian_detail_id', 'status', 'komentar']);

            foreach ($verRows as $r) {
                $varId = $mapPdToVar[(int) $r->penilaian_detail_id] ?? null;
                if ($varId && $r->status) {
                    $verifPerVar[$varId] = [
                        'status'   => $r->status,
                        'komentar' => $r->komentar,
                    ];
                }
            }
        }

        return view('penilaian-kematangan.index', [
            'variabels'       => $variabels,
            'aktif'           => $aktifVar,
            'terpilihTingkat' => $terpilihTingkat,
            'statusVariabel'  => $statusVariabel,
            'tingkatPerVar'   => $tingkatPerVar,
            'verifPerVar'     => $verifPerVar,
            'kegiatanAktif'   => $kegiatan,
        ]);
    }

    /**
     * Pre-upload file PDF (AJAX) ke storage lokal sementara.
     */
    public function uploadTemp(Request $request)
    {
        $request->validate([
            'file'     => 'required|file|mimes:pdf|mimetypes:application/pdf|max:51200', // 50MB
            'bukti_id' => 'required|integer',
        ]);

        $userId = (int) Auth::id();
        $file   = $request->file('file');

        $origName = $file->getClientOriginalName();
        $base     = pathinfo($origName, PATHINFO_FILENAME) ?: 'file';
        $safeBase = Str::slug($base, '-') ?: 'file';
        $filename = Str::uuid()->toString() . '-' . $safeBase . '.pdf'; // paksa .pdf

        $tempPath = $file->storeAs("tmp/bukti/{$userId}", $filename, 'local');

        return response()->json([
            'success'       => true,
            'bukti_id'      => (int) $request->bukti_id,
            'temp_path'     => $tempPath,
            'original_name' => $origName,
            'size'          => $file->getSize(),
            'mime'          => $file->getMimeType(),
        ]);
    }

    /**
     * Simpan penilaian (overwrite versi terbaru).
     */
    public function simpan(Request $request, KegiatanPenilaian $kegiatan, int $variabel)
    {
        // Semua role hanya boleh menyimpan pada kegiatan aktif
        if ((int) $kegiatan->is_aktif !== 1) {
            abort(403, 'Pengisian hanya diperbolehkan untuk kegiatan yang sedang aktif.');
        }

        $request->validate([
            'tingkat_id'      => 'required|exists:tingkat_penilaian,id',
            'bukti_temp'      => 'required|array',
            'bukti_temp.*'    => 'string',
            'bukti_temp_name' => 'array',
        ]);

        $user_id     = (int) Auth::id();
        $tingkat_id  = (int) $request->integer('tingkat_id');
        $kegiatan_id = (int) $kegiatan->id;
        $variabel_id = (int) $variabel;

        // Validasi variabel milik kegiatan
        $variabelRow = VariabelPenilaian::where('id', $variabel_id)
            ->where('kegiatan_id', $kegiatan_id)
            ->firstOrFail();

        // Ambil indikator kombinasi tingkat+variabel
        $indikator = IndikatorVariabel::where('tingkat_id', $tingkat_id)
            ->where('variabel_id', $variabel_id)
            ->firstOrFail();

        // Daftar bukti wajib
        $terpilihTingkat = TingkatPenilaian::with('bukti')->findOrFail($tingkat_id);

        $mapping      = $request->input('bukti_temp', []);
        $mappingNames = $request->input('bukti_temp_name', []);
        $userPrefix   = 'tmp/bukti/' . $user_id . '/';

        foreach ($terpilihTingkat->bukti as $dok) {
            $tempPath = $mapping[$dok->id] ?? null;
            if (!$tempPath) {
                $nama = $dok->nama_dokumen ?? "Dokumen {$dok->id}";
                return back()->with('error', "{$nama} wajib diunggah.")->withInput();
            }
            if (!Str::startsWith($tempPath, $userPrefix) || !Storage::disk('local')->exists($tempPath)) {
                return back()->with('error', 'File sementara tidak valid/tidak ditemukan.')->withInput();
            }
        }

        $storedFiles = [];

        try {
            DB::beginTransaction();

            // Header penilaian (unik per user+kegiatan+variabel)
            $penilaian = Penilaian::updateOrCreate(
                [
                    'user_id'     => $user_id,
                    'kegiatan_id' => $kegiatan_id,
                    'variabel_id' => $variabel_id,
                ],
                [
                    'tingkat_id' => $tingkat_id,
                    'tahun'      => $kegiatan->tahun ?? (int) date('Y'),
                    'status'     => 'draft',
                ]
            );

            // Detail TERBARU untuk variabel ini
            $detail = PenilaianDetail::where('penilaian_id', $penilaian->id)
                ->where('variabel_id', $variabel_id)
                ->orderByDesc('id')
                ->first();

            if (!$detail) {
                $detail = PenilaianDetail::create([
                    'penilaian_id' => $penilaian->id,
                    'variabel_id'  => $variabel_id,
                    'tingkat_id'   => $tingkat_id,
                    'indikator_id' => $indikator->id,
                    'status'       => 'draft',
                    'poin'         => (int) ($terpilihTingkat->poin ?? 0),
                ]);
            } else {
                $detail->fill([
                    'tingkat_id'   => $tingkat_id,
                    'indikator_id' => $indikator->id,
                    'status'       => 'draft',
                    'poin'         => (int) ($terpilihTingkat->poin ?? 0),
                ])->save();
            }

            // Sinkron header
            $penilaian->tingkat_id = $tingkat_id;
            $penilaian->total_poin = $detail->poin;
            $penilaian->status     = 'draft';
            $penilaian->save();

            // Nonaktifkan jawaban lama & buat jawaban baru (latest, draft)
            JawabanIndikator::where('penilaian_detail_id', $detail->id)->update(['is_latest' => 0]);

            $jawaban = JawabanIndikator::create([
                'penilaian_detail_id' => $detail->id,
                'indikator_id'        => $indikator->id,
                'is_latest'           => 1,
                'status'              => 'draft',
            ]);

            // Simpan dokumen (pindah dari tmp → public/bukti) pakai stream
            $hasIndikatorDokumenId = Schema::hasColumn('dokumen_indikator', 'indikator_dokumen_id');
            $hasBuktiId            = Schema::hasColumn('dokumen_indikator', 'bukti_id');

            foreach ($terpilihTingkat->bukti as $dokumen) {
                $tempPath = $mapping[$dokumen->id];
                $origName = $mappingNames[$dokumen->id] ?? basename($tempPath);

                $base     = $origName ? pathinfo($origName, PATHINFO_FILENAME) : 'file';
                $safeBase = Str::slug($base, '-') ?: 'file';
                $final    = Str::uuid()->toString() . '-' . $safeBase . '.pdf';
                $finalRel = 'bukti/' . $final;

                $read = Storage::disk('local')->readStream($tempPath);
                if ($read === false) {
                    throw new \RuntimeException("Gagal membaca file sementara: {$tempPath}");
                }

                $ok = Storage::disk('public')->writeStream($finalRel, $read);
                if (is_resource($read)) fclose($read);
                if (!$ok) {
                    throw new \RuntimeException("Gagal menulis file final: {$finalRel}");
                }

                // Hapus temp
                Storage::disk('local')->delete($tempPath);

                $storedFiles[] = $finalRel;

                $insert = [
                    'jawaban_id'   => $jawaban->id,
                    'nama_dokumen' => $dokumen->nama_dokumen ?? $origName,
                    'file_path'    => $finalRel,
                    'status'       => 'draft',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
                if ($hasIndikatorDokumenId) {
                    $insert['indikator_dokumen_id'] = $dokumen->id;
                } elseif ($hasBuktiId) {
                    $insert['bukti_id'] = $dokumen->id;
                }

                DB::table('dokumen_indikator')->insert($insert);
            }

            DB::commit();

            return redirect()->route('penilaian-kematangan.index', [
                'kegiatan'   => $kegiatan->slug,
                'tab'        => $variabel_id,
                'tingkat_id' => $tingkat_id,
            ])->with('success', 'Penilaian variabel berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            // Bersihkan file yang sudah terlanjur tersimpan
            foreach ($storedFiles as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $ex) {
                    // ignore
                }
            }

            Log::error('PenilaianKematanganController@simpan gagal', [
                'user_id' => $user_id ?? null,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Terjadi kesalahan saat menyimpan penilaian. Silakan coba lagi.')
                ->withInput();
        }
    }
}

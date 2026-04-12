<?php

namespace App\Http\Controllers;

use App\Models\DokumenIndikator;
use App\Models\KegiatanPenilaian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadBuktiDukungController extends Controller
{
    public function index(Request $request)
    {
        $kegiatans = KegiatanPenilaian::orderBy('id', 'desc')->get();
        $users     = User::orderBy('name')->get();

        return view('bukti_dukung.index', [
            'kegiatans' => $kegiatans,
            'users'     => $users,
        ]);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'user_id'               => ['required', 'integer', 'exists:users,id'],
            'kegiatan_penilaian_id' => ['nullable', 'integer'],
            'status_jawaban'        => ['nullable', 'in:draft,diterima,ditolak'],
            'only_latest'           => ['nullable', 'boolean'],
        ]);

        $targetUserId = (int) $validated['user_id'];
        $onlyLatest   = $validated['only_latest'] ?? 1;
        $statusFilter = $validated['status_jawaban'] ?? 'diterima';

        $targetUser = User::findOrFail($targetUserId);

        // Muat sampai indikator + variabel
        $query = DokumenIndikator::query()
            ->with([
                'jawaban.indikator.variabel',
                'jawaban.penilaianDetail.penilaian.user',
            ])
            ->whereHas('jawaban.penilaianDetail.penilaian', function ($q) use ($targetUserId) {
                $q->where('user_id', $targetUserId);
            });

        // Filter kegiatan (opsional)
        if (!empty($validated['kegiatan_penilaian_id'])) {
            $kegiatanId = (int) $validated['kegiatan_penilaian_id'];

            $query->whereHas('jawaban.penilaianDetail.penilaian', function ($q) use ($kegiatanId) {
                $q->where('kegiatan_id', $kegiatanId);
            });
        }

        // Hanya jawaban terbaru
        if ($onlyLatest) {
            $query->whereHas('jawaban', function ($q) {
                $q->where('is_latest', 1);
            });
        }

        // Filter status jawaban
        if ($statusFilter) {
            $query->whereHas('jawaban', function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            });
        }

        $dokumen = $query->get();

        if ($dokumen->isEmpty()) {
            return back()->with('error', 'Tidak ada dokumen bukti dukung yang ditemukan untuk filter ini.');
        }

        // Folder tmp untuk ZIP
        $tmpDir = storage_path('app/tmp');
        if (!File::exists($tmpDir)) {
            File::makeDirectory($tmpDir, 0755, true);
        }

        // Nama ZIP = nama pengguna saja
        $userSlug    = $this->slugForFolder($targetUser->name ?? ('user_' . $targetUserId));
        $zipFileName = $userSlug . '.zip';
        $zipPath     = $tmpDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        $storageDisk = 'public'; // sesuaikan dengan disk tempat file disimpan

        foreach ($dokumen as $doc) {
            $jawaban   = $doc->jawaban;
            $penDetail = $jawaban?->penilaianDetail;
            $penilaian = $penDetail?->penilaian;
            $pengisi   = $penilaian?->user;
            $indikator = $jawaban?->indikator;
            $variabel  = $indikator?->variabel;

            // Pastikan milik user yang dipilih
            if (!$pengisi || $pengisi->id !== $targetUserId) {
                continue;
            }

            // Nama folder = nama variabel
            $variabelName = $variabel?->nama;
            if (!$variabelName) {
                // fallback kalau variabel null
                $variabelName = 'Variabel_' . ($variabel?->id ?? 'tanpa_nama');
            }

            $variabelFolder = $this->slugForFolder($variabelName);

            // ----- Nama file di dalam ZIP -----
            // 1) Kalau nama_dokumen ada:
            //    - Jika sudah mengandung titik, pakai apa adanya.
            //    - Jika belum ada titik, tambahkan ekstensi dari file_path.
            // 2) Kalau nama_dokumen kosong: pakai basename(file_path) (sudah termasuk .pdf / lainnya).
            $originalName = null;

            if (!empty($doc->nama_dokumen)) {
                $originalName = $doc->nama_dokumen;

                if (mb_strpos($originalName, '.') === false) {
                    // tidak ada titik, tambahkan ekstensi dari path
                    $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION);
                    if ($ext) {
                        $originalName .= '.' . $ext;
                    }
                }
            } else {
                $originalName = basename($doc->file_path);
            }

            // Path fisik file di storage
            $relativePath = $doc->file_path;

            if (!Storage::disk($storageDisk)->exists($relativePath)) {
                continue;
            }

            $absolutePath = Storage::disk($storageDisk)->path($relativePath);

            // Struktur: NamaVariabel/nama_file.ext (ext asli: .pdf, .docx, dll)
            $internalPath = $variabelFolder . '/' . $originalName;

            $zip->addFile($absolutePath, $internalPath);
        }

        $zip->close();

        if (!File::exists($zipPath) || File::size($zipPath) === 0) {
            return back()->with('error', 'Tidak ada file fisik yang bisa dimasukkan ke ZIP.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    protected function slugForFolder(string $name): string
    {
        $slug = preg_replace('~[^\pL0-9]+~u', '_', $name);
        $slug = trim($slug, '_');
        $slug = mb_substr($slug, 0, 80);

        return $slug ?: 'unknown';
    }
}

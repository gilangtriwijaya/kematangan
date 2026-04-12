<?php

namespace App\Http\Controllers;

use App\Models\KegiatanPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KegiatanPenilaianController extends Controller
{
    /** Tampilkan daftar kegiatan */
    public function index()
    {
        $kegiatan = KegiatanPenilaian::orderByDesc('tahun')->get();

        return view('kegiatan.index', compact('kegiatan'));
    }

    /** Form tambah kegiatan */
    public function create()
    {
        return view('kegiatan.create');
    }

    /** Simpan kegiatan baru */
    public function store(Request $request)
    {
        $request->validate([
            'nama'            => 'required|string|max:255',
            'tahun'           => 'required|integer|min:2000|max:2100',
            'deskripsi'       => 'nullable|string',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        KegiatanPenilaian::create($request->all());

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    /** Form edit kegiatan */
    public function edit(KegiatanPenilaian $kegiatan)
    {
        return view('kegiatan.edit', compact('kegiatan'));
    }

    /** Update kegiatan */
    public function update(Request $request, KegiatanPenilaian $kegiatan)
    {
        $request->validate([
            'nama'            => 'required|string|max:255',
            'tahun'           => 'required|integer|min:2000|max:2100',
            'deskripsi'       => 'nullable|string',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $kegiatan->update($request->all());

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    /**
     * Set sebagai aktif (BERBASIS ID numerik, anti bentrok slug).
     * NOTE: sengaja pakai int $kegiatan agar tidak terpengaruh routeKeyName.
     */
    public function setAktif(int $kegiatan_id)
    {
        $target = KegiatanPenilaian::findOrFail($kegiatan_id);

        DB::transaction(function () use ($target) {
            // Nonaktifkan semua kegiatan dengan NAMA yang sama
            KegiatanPenilaian::where('nama', $target->nama)->update(['is_aktif' => 0]);

            // Aktifkan yang dipilih
            $target->update(['is_aktif' => 1]);
        });

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil diaktifkan.');
    }

    /** Hapus kegiatan (hanya jika belum ada variabel) */
    public function destroy(KegiatanPenilaian $kegiatan)
    {
        $variabelAda = $kegiatan->variabels()->exists();
        if ($variabelAda) {
            return redirect()->route('kegiatan.index')
                ->with('error', 'Gagal dihapus: kegiatan memiliki variabel.');
        }

        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    /** Konfirmasi hapus (BERBASIS ID numerik) */
    public function konfirmasiHapus(int $kegiatan)
    {
        $k = KegiatanPenilaian::with('variabels')->findOrFail($kegiatan);
        $variabels = $k->variabels;

        return view('kegiatan.konfirmasi-hapus', [
            'kegiatan'  => $k,
            'variabels' => $variabels,
        ]);
    }
}

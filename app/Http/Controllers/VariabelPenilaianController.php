<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VariabelPenilaian;
use App\Models\KegiatanPenilaian;

class VariabelPenilaianController extends Controller
{
    public function index(Request $request)
    {
        $kegiatanId = $request->get('kegiatan_id') ?? KegiatanPenilaian::where('is_aktif', true)->value('id');

        if (!$kegiatanId) {
            return redirect()->route('kegiatan-penilaian.index')->with('warning', 'Belum ada kegiatan aktif atau belum dipilih.');
        }

        $kegiatanAktif = KegiatanPenilaian::findOrFail($kegiatanId);
        $daftarKegiatan = KegiatanPenilaian::orderByDesc('tahun')->get();

        $variabelList = VariabelPenilaian::where('kegiatan_id', $kegiatanId)
                            ->orderBy('urutan')
                            ->get();

        return view('variabel.index', compact('kegiatanAktif', 'daftarKegiatan', 'variabelList'));
    }

    public function create(Request $request)
    {
        $kegiatanId = $request->get('kegiatan_id') ?? KegiatanPenilaian::where('is_aktif', true)->value('id');

        if (!$kegiatanId) {
            return redirect()->route('kegiatan-penilaian.index')->with('warning', 'Kegiatan tidak ditemukan.');
        }

        $kegiatanAktif = KegiatanPenilaian::findOrFail($kegiatanId);
        return view('variabel.create', compact('kegiatanAktif'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan_penilaian,id',
            'kode'        => 'required|string|max:10',
            'nama'        => 'required|string|max:255',
            'urutan'      => 'nullable|integer|min:1',
        ]);

        VariabelPenilaian::create($request->only('kegiatan_id', 'kode', 'nama', 'urutan'));

        return redirect()->route('variabel.index', ['kegiatan_id' => $request->kegiatan_id])
                         ->with('success', 'Variabel berhasil ditambahkan.');
    }

    public function edit(VariabelPenilaian $variabel)
    {
        $kegiatanAktif = $variabel->kegiatan;
        return view('variabel.edit', compact('variabel', 'kegiatanAktif'));
    }

    public function update(Request $request, VariabelPenilaian $variabel)
    {
        $request->validate([
            'kode'   => 'required|string|max:10',
            'nama'   => 'required|string|max:255',
            'urutan' => 'nullable|integer|min:1',
        ]);

        $variabel->update($request->only('kode', 'nama', 'urutan'));

        return redirect()->route('variabel.index', ['kegiatan_id' => $variabel->kegiatan_id])
                         ->with('success', 'Variabel berhasil diperbarui.');
    }

    public function destroy(VariabelPenilaian $variabel)
    {
        $kegiatanId = $variabel->kegiatan_id;
        $variabel->delete();

        return redirect()->route('variabel.index', ['kegiatan_id' => $kegiatanId])
                         ->with('success', 'Variabel berhasil dihapus.');
    }
}

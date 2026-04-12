<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TingkatPenilaian;
use App\Models\KegiatanPenilaian;

class TingkatPenilaianController extends Controller
{
    public function index(Request $request)
    {
        $kegiatanId = $request->kegiatan_id ?? KegiatanPenilaian::where('is_aktif', true)->first()?->id;
        $kegiatanAktif = KegiatanPenilaian::findOrFail($kegiatanId);
        $daftarKegiatan = KegiatanPenilaian::orderByDesc('tahun')->get();

        $tingkatList = TingkatPenilaian::where('kegiatan_id', $kegiatanId)->orderBy('poin')->get();

        return view('tingkat.index', compact('kegiatanAktif', 'daftarKegiatan', 'tingkatList'));
    }

    public function create(Request $request)
    {
        $kegiatanId = $request->kegiatan_id ?? KegiatanPenilaian::where('is_aktif', true)->first()?->id;
        $kegiatanAktif = KegiatanPenilaian::findOrFail($kegiatanId);

        return view('tingkat.create', compact('kegiatanAktif'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kegiatan_id' => 'required|exists:kegiatan_penilaian,id',
            'label' => 'required|string|max:20',
            'poin' => 'required|numeric|min:0',
        ]);

        TingkatPenilaian::create($request->only('kegiatan_id', 'label', 'poin'));

        return redirect()->route('indikator.index', ['kegiatan_id' => $request->kegiatan_id])
                         ->with('success', 'Tingkat berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $tingkat = TingkatPenilaian::findOrFail($id);
        $kegiatanAktif = $tingkat->kegiatan;

        return view('tingkat.edit', compact('tingkat', 'kegiatanAktif'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'label' => 'required|string|max:20',
            'poin' => 'required|numeric|min:0',
        ]);

        $tingkat = TingkatPenilaian::findOrFail($id);
        $tingkat->update($request->only('label', 'poin'));

        return redirect()->route('indikator.index', ['kegiatan_id' => $tingkat->kegiatan_id])
                         ->with('success', 'Tingkat berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $tingkat = TingkatPenilaian::findOrFail($id);
        $kegiatanId = $tingkat->kegiatan_id;
        $tingkat->delete();

        return redirect()->route('indikator.index', ['kegiatan_id' => $kegiatanId])
                         ->with('success', 'Tingkat berhasil dihapus.');
    }
}

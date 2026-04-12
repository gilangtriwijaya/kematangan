<?php

namespace App\Http\Controllers;

use App\Models\KegiatanPenilaian;
use App\Models\TingkatPenilaian;
use App\Models\IndikatorVariabel;
use App\Models\VariabelPenilaian;
use App\Models\IndikatorDokumen;
use Illuminate\Http\Request;

class IndikatorController extends Controller
{
    public function index(Request $request)
    {
        $kegiatanId = $request->kegiatan_id ?? KegiatanPenilaian::where('is_aktif', true)->first()?->id;

        $kegiatanAktif = KegiatanPenilaian::find($kegiatanId);
        $daftarKegiatan = KegiatanPenilaian::orderByDesc('tahun')->get();

        $tingkatList = TingkatPenilaian::where('kegiatan_id', $kegiatanId)->orderBy('poin')->get();

        $indikatorList = IndikatorVariabel::with(['variabel', 'tingkat'])
            ->whereHas('tingkat', fn($q) => $q->where('kegiatan_id', $kegiatanId))
            ->orderBy('variabel_id')
            ->get();

        $indikatorList = IndikatorVariabel::with('bukti')->get();


        return view('indikator.index', compact('kegiatanAktif', 'daftarKegiatan', 'tingkatList', 'indikatorList'));
    }

    public function create(Request $request)
    {
        $kegiatanId = $request->kegiatan_id ?? KegiatanPenilaian::where('is_aktif', true)->first()?->id;
        $kegiatanAktif = KegiatanPenilaian::findOrFail($kegiatanId);
        $tingkatList = TingkatPenilaian::where('kegiatan_id', $kegiatanId)->orderBy('poin')->get();

        return view('indikator.create', compact('kegiatanAktif', 'tingkatList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'variabel_id' => 'required|exists:variabels,id',
            'tingkat_id' => 'required|exists:tingkat_penilaian,id',
            'deskripsi' => 'required|string|max:1000',
            'jumlah_bukti' => 'required|integer|min:0',
        ]);

        IndikatorVariabel::create($request->only('variabel_id', 'tingkat_id', 'deskripsi', 'jumlah_bukti'));

        $tingkat = TingkatPenilaian::find($request->tingkat_id);
        $kegiatanId = $tingkat->kegiatan_id;

        return redirect()->route('indikator.index', ['kegiatan_id' => $kegiatanId])
                         ->with('success', 'Indikator berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $indikator = IndikatorVariabel::findOrFail($id);
        $kegiatanAktif = $indikator->tingkat->kegiatan;
        $tingkatList = TingkatPenilaian::where('kegiatan_id', $kegiatanAktif->id)->orderBy('poin')->get();

        return view('indikator.edit', compact('indikator', 'tingkatList', 'kegiatanAktif'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'variabel_id' => 'required|exists:variabels,id',
            'tingkat_id' => 'required|exists:tingkat_penilaian,id',
            'deskripsi' => 'required|string|max:1000',
            'jumlah_bukti' => 'required|integer|min:0',
        ]);

        $indikator = IndikatorVariabel::findOrFail($id);
        $indikator->update($request->only('variabel_id', 'tingkat_id', 'deskripsi', 'jumlah_bukti'));

        $tingkat = TingkatPenilaian::find($request->tingkat_id);
        $kegiatanId = $tingkat->kegiatan_id;

        return redirect()->route('indikator.index', ['kegiatan_id' => $kegiatanId])
                         ->with('success', 'Indikator berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $indikator = IndikatorVariabel::findOrFail($id);
        $kegiatanId = $indikator->tingkat->kegiatan_id;

        $indikator->delete();

        return redirect()->route('indikator.index', ['kegiatan_id' => $kegiatanId])
                         ->with('success', 'Indikator berhasil dihapus.');
    }
}

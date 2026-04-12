<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IndikatorDokumen;
use App\Models\IndikatorVariabel;

class IndikatorDokumenController extends Controller
{
    public function index(Request $request)
    {
        $indikatorId = $request->indikator_id;
        $indikator = IndikatorVariabel::findOrFail($indikatorId);
        $dokumenList = IndikatorDokumen::where('indikator_id', $indikatorId)->orderBy('urutan')->get();

        return view('indikator-dokumen.index', compact('indikator', 'dokumenList'));
    }

    public function create(Request $request)
    {
        $indikator = IndikatorVariabel::findOrFail($request->indikator_id);
        return view('indikator-dokumen.create', compact('indikator'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'indikator_id' => 'required|exists:indikator_variabel,id',
            'nama_dokumen' => 'required|string|max:255',
            'urutan' => 'nullable|integer|min:1'
        ]);

        IndikatorDokumen::create($request->only('indikator_id', 'nama_dokumen', 'urutan'));

        return redirect()->route('indikator-dokumen.index', ['indikator_id' => $request->indikator_id])
                         ->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $dokumen = IndikatorDokumen::findOrFail($id);
        $indikator = $dokumen->indikator;

        return view('indikator-dokumen.edit', compact('dokumen', 'indikator'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_dokumen' => 'required|string|max:255',
            'urutan' => 'nullable|integer|min:1'
        ]);

        $dokumen = IndikatorDokumen::findOrFail($id);
        $dokumen->update($request->only('nama_dokumen', 'urutan'));

        return redirect()->route('indikator-dokumen.index', ['indikator_id' => $dokumen->indikator_id])
                         ->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $dokumen = IndikatorDokumen::findOrFail($id);
        $indikatorId = $dokumen->indikator_id;
        $dokumen->delete();

        return redirect()->route('indikator-dokumen.index', ['indikator_id' => $indikatorId])
                         ->with('success', 'Dokumen berhasil dihapus.');
    }
}

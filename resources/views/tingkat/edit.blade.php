@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Edit Tingkat Penilaian</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mt-2 mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tingkat.update', $tingkat->id) }}" method="POST" class="card p-4 shadow-sm">
        @csrf
        @method('PUT')

        <input type="hidden" name="kegiatan_id" value="{{ $kegiatanAktif->id }}">

        <div class="mb-3">
            <label for="variabel_id" class="form-label">Variabel</label>
            <select name="variabel_id" id="variabel_id" class="form-select" required>
                <option value="">-- Pilih Variabel --</option>
                @foreach (\App\Models\VariabelPenilaian::where('kegiatan_id', $kegiatanAktif->id)->get() as $v)
                    <option value="{{ $v->id }}" {{ $indikator->variabel_id == $v->id ? 'selected' : '' }}>
                        {{ $v->kode }} - {{ $v->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="label" class="form-label">Label Tingkat</label>
            <input type="text" name="label" id="label" class="form-control" value="{{ old('label', $tingkat->label) }}" required>
        </div>

        <div class="mb-3">
            <label for="poin" class="form-label">Poin</label>
            <input type="number" name="poin" id="poin" class="form-control" value="{{ old('poin', $tingkat->poin) }}" required min="0">
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('indikator.index', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-outline-secondary">← Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

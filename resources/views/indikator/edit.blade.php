@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Edit Indikator Penilaian</h4>

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

    <form action="{{ route('indikator.update', $indikator->id) }}" method="POST" class="card p-4 shadow-sm">
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
            <label for="tingkat_id" class="form-label">Tingkat</label>
            <select name="tingkat_id" id="tingkat_id" class="form-select" required>
                <option value="">-- Pilih Tingkat --</option>
                @foreach ($tingkatList as $t)
                    <option value="{{ $t->id }}" {{ $indikator->tingkat_id == $t->id ? 'selected' : '' }}>
                        {{ $t->label }} ({{ $t->poin }} poin)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi Indikator</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3" required>{{ old('deskripsi', $indikator->deskripsi) }}</textarea>
        </div>

        <div class="mb-4">
            <label for="jumlah_bukti" class="form-label">Jumlah Bukti Dukung</label>
            <input type="number" name="jumlah_bukti" id="jumlah_bukti" value="{{ old('jumlah_bukti', $indikator->jumlah_bukti) }}" class="form-control" min="0" required>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('indikator.index', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-outline-secondary">← Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection

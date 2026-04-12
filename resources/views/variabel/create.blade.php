@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <h4 class="mb-4">Tambah Variabel Penilaian – {{ $kegiatanAktif->nama }} ({{ $kegiatanAktif->tahun }})</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('variabel.store') }}" method="POST" class="card p-4 shadow-sm border-0">
        @csrf

        <input type="hidden" name="kegiatan_id" value="{{ $kegiatanAktif->id }}">

        <div class="mb-3">
            <label for="kode" class="form-label">Kode Variabel</label>
            <input type="text" class="form-control" id="kode" name="kode" value="{{ old('kode') }}" required>
        </div>

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Variabel</label>
            <input type="text" class="form-control" id="nama" name="nama" value="{{ old('nama') }}" required>
        </div>

        <div class="mb-4">
            <label for="urutan" class="form-label">Urutan</label>
            <input type="number" class="form-control" id="urutan" name="urutan" value="{{ old('urutan') }}">
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('variabel.index', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-outline-secondary">← Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection

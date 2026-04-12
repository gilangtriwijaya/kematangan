@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Tambah Dokumen Wajib</h4>

    <div class="mb-3">
        <h6 class="text-muted">Indikator:</h6>
        <div class="border rounded p-2 bg-light">{{ $indikator->deskripsi }}</div>
    </div>

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

    <form action="{{ route('indikator-dokumen.store') }}" method="POST" class="card p-4 shadow-sm">
        @csrf

        <input type="hidden" name="indikator_id" value="{{ $indikator->id }}">

        <div class="mb-3">
            <label for="nama_dokumen" class="form-label">Nama Dokumen</label>
            <input type="text" name="nama_dokumen" id="nama_dokumen" class="form-control" value="{{ old('nama_dokumen') }}" placeholder="Contoh: SK Tim Penilai" required>
        </div>

        <div class="mb-3">
            <label for="urutan" class="form-label">Urutan</label>
            <input type="number" name="urutan" id="urutan" class="form-control" value="{{ old('urutan') ?? 1 }}" min="1">
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('indikator-dokumen.index', ['indikator_id' => $indikator->id]) }}" class="btn btn-outline-secondary">← Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection

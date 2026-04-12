@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <h4 class="mb-4">Tambah Kegiatan Penilaian</h4>

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

    <form action="{{ route('kegiatan.store') }}" method="POST" class="card shadow-sm border-0 p-4">
        @csrf

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Kegiatan</label>
            <input type="text" class="form-control" id="nama" name="nama" value="{{ old('nama') }}" required>
        </div>

        <div class="mb-3">
            <label for="tahun" class="form-label">Tahun</label>
            <input type="number" class="form-control" id="tahun" name="tahun" value="{{ old('tahun', date('Y')) }}" required>
        </div>

        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi') }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required>
            </div>

            <div class="col-md-6 mb-3">
                <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('kegiatan.index') }}" class="btn btn-outline-secondary">← Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Kegiatan</button>
        </div>
    </form>
</div>
@endsection

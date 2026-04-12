@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Edit Variabel Penilaian</h4>

    <form action="{{ route('variabel.update', $variabel->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="kegiatan_id" value="{{ $variabel->kegiatan_id }}">

        <div class="mb-3">
            <label for="kode" class="form-label">Kode Variabel</label>
            <input type="text" name="kode" id="kode" value="{{ old('kode', $variabel->kode) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="nama" class="form-label">Nama Variabel</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama', $variabel->nama) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="urutan" class="form-label">Nomor Urutan</label>
            <input type="number" name="urutan" id="urutan" value="{{ old('urutan', $variabel->urutan) }}" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('variabel.index', ['kegiatan_id' => $variabel->kegiatan_id]) }}" class="btn btn-secondary">Batal</a>
    </form>
</div>
@endsection

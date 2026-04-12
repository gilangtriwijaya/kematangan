@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Daftar Tingkat Penilaian</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Dropdown Pilih Kegiatan --}}
    <form method="GET" action="{{ route('tingkat.index') }}" class="mb-3">
        <div class="d-flex align-items-center gap-2">
            <label for="kegiatan_id" class="form-label mb-0">Pilih Kegiatan:</label>
            <select name="kegiatan_id" id="kegiatan_id" class="form-select w-auto" onchange="this.form.submit()">
                @foreach ($daftarKegiatan as $item)
                    <option value="{{ $item->id }}" {{ $item->id == $kegiatanAktif->id ? 'selected' : '' }}>
                        {{ $item->nama }} ({{ $item->tahun }})
                    </option>
                @endforeach
            </select>
        </div>
    </form>
        <div class="mb-3">
            <a href="{{ route('tingkat.create', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Tingkat
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">Tingkat</div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Label</th>
                            <th>Poin</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tingkatList as $tingkat)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="text-center">{{ $tingkat->label }}</td>
                                <td class="text-center">{{ $tingkat->poin }}</td>
                                <td class="text-center">
                                    <a href="{{ route('tingkat.edit', $tingkat->id) }}" class="btn btn-warning btn-sm me-1">Edit</a>
                                    <a href="{{ route('kegiatan.konfirmasiHapus', $item->id) }}" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada data tingkat.</td></tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

    <div class="mt-3">
        <a href="{{ route('kegiatan.index') }}" class="btn btn-outline-secondary">← Kembali ke Kegiatan</a>
    </div>
</div>
@endsection

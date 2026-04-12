@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Dokumen Wajib Indikator</h4>

    <div class="mb-3">
        <h6 class="text-muted">Indikator:</h6>
        <div class="border rounded p-2 bg-light">{{ $indikator->deskripsi }}</div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('indikator-dokumen.create', ['indikator_id' => $indikator->id]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Dokumen Wajib
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">Daftar Dokumen</div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Nama Dokumen</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dokumenList as $dokumen)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $dokumen->nama_dokumen }}</td>
                            <td class="text-center">{{ $dokumen->urutan }}</td>
                            <td class="text-center">
                                <a href="{{ route('indikator-dokumen.edit', $dokumen->id) }}" class="btn btn-sm btn-warning me-1">Edit</a>
                                <form action="{{ route('indikator-dokumen.destroy', $dokumen->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus dokumen ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Belum ada dokumen wajib.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('indikator.index', ['kegiatan_id' => $indikator->kegiatan_id]) }}" class="btn btn-outline-secondary">← Kembali ke Indikator</a>
    </div>
</div>
@endsection

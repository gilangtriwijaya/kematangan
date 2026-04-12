@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Daftar Kegiatan Penilaian</h4>
        <a href="{{ route('kegiatan.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Kegiatan
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-bordered table-hover table-striped align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Nama</th>
                    <th style="width: 80px;">Tahun</th>
                    <th>Deskripsi</th>
                    <th style="width: 200px;">Tanggal</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kegiatan as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item->nama }}</td>
                        <td class="text-center">{{ $item->tahun }}</td>
                        <td>{{ $item->deskripsi }}</td>
                        <td class="text-center">
                            {{ $item->tanggal_mulai }}<br>
                            s.d<br>
                            {{ $item->tanggal_selesai }}
                        </td>
                        <td class="text-center">
                            @if ($item->is_aktif)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <form action="{{ route('kegiatan.aktifkan', $item->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        Aktifkan
                                    </button>
                                </form>
                            @endif
                        </td>
                        <div class="d-flex justify-content-center gap-2">
                        <td class="text-center">
                         {{-- Tombol Edit --}}
                            <a href="{{ route('kegiatan.edit', $item) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        
                        {{-- Tombol Hapus --}}
                            <form action="{{ route('kegiatan.destroy', $item) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kegiatan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada kegiatan penilaian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

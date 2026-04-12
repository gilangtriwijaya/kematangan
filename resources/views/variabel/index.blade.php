@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

{{-- Dropdown Pilih Kegiatan --}}
    <form method="GET" action="{{ route('variabel.index') }}" class="mb-3">
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Variabel Penilaian</h4>
        <a href="{{ route('variabel.create', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Variabel
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped mb-0">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Kode</th>
                        <th>Nama Variabel</th>
                        <th style="width: 80px;">Urutan</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
<tbody>
    @forelse ($variabelList as $item)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td class="text-center">{{ $item->kode }}</td>
            <td>{{ $item->nama }}</td>
            <td class="text-center">{{ $item->urutan }}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('variabel.edit', $item) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <form action="{{ route('variabel.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus variabel ini?')">
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
            <td colspan="5" class="text-center text-muted">Belum ada variabel penilaian untuk kegiatan ini.</td>
        </tr>
    @endforelse
</tbody>

            
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.dashboard')

@section('content')
<div class="container py-4">
    <h4 class="mb-4">Indikator Penilaian</h4>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Dropdown Pilih Kegiatan --}}
    <form method="GET" action="{{ route('indikator.index') }}" class="mb-3">
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

    {{-- Tombol Tambah Indikator --}}
    <div class="mb-2">
        <a href="{{ route('indikator.create', ['kegiatan_id' => $kegiatanAktif->id]) }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Indikator
        </a>
    </div>

    {{-- Tabel Indikator Variabel --}}
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">Indikator per Variabel</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>#</th>
                        <th>Variabel</th>
                        <th>Tingkat</th>
                        <th>Indikator</th>
                        <th>Bukti Dukung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indikatorList as $indikator)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $indikator->variabel->nama ?? '-' }}</td>
                            <td class="text-center">{{ $indikator->tingkat->label ?? '-' }}</td>
                            <td>{{ $indikator->deskripsi }}</td>
                            
                            @php
                            $dokumens = $indikator->bukti;
                            $jumlah = $dokumens->count();
                            $tooltip = $dokumens->pluck('nama')->implode(', ');
                        @endphp
                        
                        <td class="text-center">
                            @if ($jumlah > 0)
                                <span class="badge bg-success" title="{{ $tooltip }}">{{ $jumlah }} ✓</span>
                            @else
                                <span class="badge bg-danger">Belum 📛</span>
                            @endif
                        </td>

                            
                            <td class="text-center">
                                <a href="{{ route('indikator.edit', $indikator->id) }}" class="btn btn-sm btn-warning me-1">Edit</a>
                                <form action="{{ route('indikator.destroy', $indikator->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus indikator ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                               {{-- Tombol Kelola Dokumen --}}
                                <a href="{{ route('indikator-dokumen.index', ['indikator_id' => $indikator->id]) }}" class="btn btn-sm btn-outline-secondary mt-1">
                                    Dokumen Wajib
                                </a>

                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada indikator terdaftar.</td></tr>
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

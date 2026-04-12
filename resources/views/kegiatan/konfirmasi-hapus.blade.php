@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
    <h4 class="mb-4 text-danger">Konfirmasi Hapus Kegiatan</h4>

    <div class="alert alert-warning">
        <strong>Perhatian:</strong> Anda akan menghapus kegiatan berikut:
        <ul>
            <li><strong>Nama:</strong> {{ $kegiatan->nama }}</li>
            <li><strong>Tahun:</strong> {{ $kegiatan->tahun }}</li>
        </ul>

        @if ($variabels->count())
            <p class="mt-3 text-danger">Namun kegiatan ini memiliki <strong>{{ $variabels->count() }} variabel</strong> terkait:</p>
            <ul>
                @foreach ($variabels as $variabel)
                    <li>{{ $variabel->nama }} (ID: {{ $variabel->id }})</li>
                @endforeach
            </ul>
            <p>Silakan hapus variabel tersebut terlebih dahulu sebelum menghapus kegiatan ini.</p>
            <a href="{{ route('kegiatan.index') }}" class="btn btn-secondary mt-3">Kembali</a>
        @else
            <p class="mt-3">Kegiatan ini belum memiliki variabel. Anda bisa melanjutkan untuk menghapus.</p>
            <form method="POST" action="{{ route('kegiatan.destroy', $kegiatan->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Hapus Sekarang</button>
                <a href="{{ route('kegiatan.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
            </form>
        @endif
    </div>
</div>
@endsection

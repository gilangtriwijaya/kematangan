@extends('layouts.dashboard')

@section('title', 'Profil Saya')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Edit Profil</h2>

    {{-- Tampilkan Pesan Sukses --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tampilkan Error Validasi --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profil.update') }}">
        @csrf

        {{-- Nama (hanya tampil, tidak bisa diubah) --}}
        <div class="mb-3">
          <label for="nama" class="form-label">Nama</label>
          <input type="text"
                 id="nama"
                 class="form-control"
                 value="{{ old('nama', $user->name) }}"
                 disabled>
        
          {{-- Tetap kirim nilainya ke server --}}
          <input type="hidden" name="nama" value="{{ old('nama', $user->name) }}">
        </div>


        {{-- Password Baru --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password Baru (opsional)</label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror">
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Tombol Simpan --}}
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>
@endsection

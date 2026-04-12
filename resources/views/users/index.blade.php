@extends('layouts.dashboard')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Daftar Pengguna</h4>
  <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
    <i class="bi bi-plus-circle me-1"></i> Tambah User
  </a>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
  </div>
@endif

<div class="table-responsive shadow-sm rounded">
  <table class="table table-striped table-bordered align-middle">
    <thead class="table-dark">
      <tr>
        <th style="width:60px;">No</th>
        <th>Nama</th>
        <th>Email</th>
        <th style="width:120px;">Role</th>
        <th>OPD</th>
        <th style="width:260px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $index => $user)
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td>{{ $user->name }}</td>
          <td>{{ $user->email }}</td>
          <td><span class="badge bg-info text-dark">{{ $user->role }}</span></td>
          <td>{{ $user->opd_name ?? '-' }}</td>
          <td class="text-center">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>

            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Yakin ingin hapus user ini?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
            </form>

            @if(auth()->user()->role === 'superadmin' && $user->id !== auth()->id())
              <form action="{{ route('impersonate.start', $user->id) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Masuk sebagai {{ $user->name }} ?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary">Masuk sebagai</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center text-muted">Belum ada pengguna.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

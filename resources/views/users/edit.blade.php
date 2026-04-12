@extends('layouts.dashboard')

@section('title', 'Edit Pengguna')

@section('content')
<h4 class="mb-4">Edit Pengguna</h4>

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form action="{{ route('users.update', $user->id) }}" method="POST">
  @csrf
  @method('PUT')
  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="name">Nama</label>
      <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name) }}">
    </div>
    <div class="col-md-6 mb-3">
      <label for="email">Email</label>
      <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}">
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label for="password">Password Baru (opsional)</label>
      <input type="password" name="password" class="form-control">
    </div>
    <div class="col-md-6 mb-3">
      <label for="role">Role</label>
      <select name="role" class="form-select" required onchange="toggleOpdField(this.value)">
        <option value="">-- Pilih Role --</option>
        <option value="superadmin" {{ $user->role == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="verifikator" {{ $user->role == 'verifikator' ? 'selected' : '' }}>Verifikator</option>
        <option value="opd" {{ $user->role == 'opd' ? 'selected' : '' }}>OPD</option>
      </select>
    </div>
  </div>

  <div class="mb-3" id="opdField" style="display: none;">
    <label for="opd_name">Nama OPD</label>
    <input type="text" name="opd_name" class="form-control" value="{{ old('opd_name', $user->opd_name) }}">
  </div>

  <button type="submit" class="btn btn-primary">Update</button>
  <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
</form>

<script>
  function toggleOpdField(role) {
    const opdField = document.getElementById('opdField');
    opdField.style.display = (role === 'opd') ? 'block' : 'none';
  }
  window.onload = function() {
    toggleOpdField('{{ $user->role }}');
  }
</script>
@endsection

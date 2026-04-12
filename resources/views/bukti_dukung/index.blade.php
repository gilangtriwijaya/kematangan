@extends('layouts.dashboard')

@section('title', 'Download Bukti Dukung')

@section('content')
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col">
        <h1 class="h3">Download Bukti Dukung Penilaian</h1>
        <p class="text-muted mb-0">
          Pilih <strong>pengguna</strong> dan (opsional) kegiatan penilaian untuk mengunduh dokumen
          bukti dukung dalam satu file ZIP. Di dalam ZIP, dokumen akan dikelompokkan berdasarkan
          <strong>indikator</strong>.
        </p>
      </div>
    </div>

    @if (session('error'))
      <div class="alert alert-danger">
        {{ session('error') }}
      </div>
    @endif

    @if (session('ok'))
      <div class="alert alert-success">
        {{ session('ok') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card">
      <div class="card-header">
        <h2 class="card-title h5 mb-0">Filter & Download</h2>
      </div>

      <div class="card-body">
        <form method="GET" action="{{ route('bukti-dukung.download') }}" class="row g-3 align-items-end">

          {{-- Pengguna --}}
          <div class="col-md-4">
            <label for="user_id" class="form-label">Pengguna</label>
            <select name="user_id" id="user_id" class="form-select" required>
              <option value="">-- Pilih pengguna --</option>
              @foreach ($users as $u)
                <option value="{{ $u->id }}"
                  {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>
                  {{ $u->name }} (ID: {{ $u->id }})
                </option>
              @endforeach
            </select>
          </div>

          {{-- Kegiatan Penilaian (opsional) --}}
          <div class="col-md-4">
            <label for="kegiatan_penilaian_id" class="form-label">Kegiatan Penilaian</label>
            <select name="kegiatan_penilaian_id" id="kegiatan_penilaian_id" class="form-select">
              <option value="">Semua kegiatan</option>
              @foreach ($kegiatans as $kegiatan)
                @php
                  // Tampilkan tahun saja, karena nama kegiatan sama semua
                  $tahun = $kegiatan->tahun ?? null;
                  $label = $tahun ? 'Tahun ' . $tahun : ('ID ' . $kegiatan->id);
                @endphp
                <option value="{{ $kegiatan->id }}"
                  {{ (string) request('kegiatan_penilaian_id') === (string) $kegiatan->id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Status jawaban --}}
          <div class="col-md-2">
            <label for="status_jawaban" class="form-label">Status Jawaban</label>
            <select name="status_jawaban" id="status_jawaban" class="form-select">
              <option value="">Semua status</option>
              <option value="diterima" {{ request('status_jawaban', 'diterima') === 'diterima' ? 'selected' : '' }}>Diterima</option>
              <option value="draft" {{ request('status_jawaban') === 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="ditolak" {{ request('status_jawaban') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
          </div>

          {{-- Hanya jawaban terbaru --}}
          <div class="col-md-2">
            <div class="form-check mt-4 pt-2">
              <input class="form-check-input" type="checkbox" value="1" id="only_latest" name="only_latest"
                {{ old('only_latest', request('only_latest', 1)) ? 'checked' : '' }}>
              <label class="form-check-label" for="only_latest">
                Jawaban terbaru saja
              </label>
            </div>
          </div>

          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-download me-1"></i>
              Download ZIP
            </button>
          </div>
        </form>
      </div>
        <div class="card-footer text-muted small">
          Nama file ZIP: <code>NamaPengguna.zip</code><br>
          Struktur di dalam ZIP: <code>NamaVariabel/nama_dokumen.pdf</code> (atau ekstensi asli lainnya)<br>
          Karena dipersempit per pengguna, beban server jauh lebih ringan dan nyaman untuk diunduh.
        </div>

    </div>
  </div>
@endsection

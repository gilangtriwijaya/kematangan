@extends('layouts.dashboard')

@section('content')
<div class="container mt-4">
  <h4 class="mb-4 fw-bold">
    <i class="bi bi-clipboard-check me-2 text-danger"></i>
    Dashboard Verifikasi Penilaian OPD
  </h4>

  {{-- Pilih Kegiatan --}}
  <form method="GET" action="{{ route('verifikasi.index') }}" class="mb-4 d-flex align-items-center gap-2" aria-label="Pilih Kegiatan">
    <label for="kegiatan_id" class="form-label mb-0">Pilih Kegiatan:</label>
    <select name="kegiatan_id" id="kegiatan_id" class="form-select w-auto" onchange="this.form.submit()">
      @foreach ($daftarKegiatan as $k)
        <option value="{{ $k->id }}" @selected((int)$k->id === (int)$kegiatan_id)>
          {{ $k->nama }} ({{ $k->tahun }})
        </option>
      @endforeach
    </select>
  </form>

  {{-- Rekap --}}
  <div class="table-responsive">
    <table class="table table-bordered table-hover bg-white align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:60px">#</th>
          <th>Nama OPD</th>
          <th style="width:240px">Progres Verifikasi</th>
          <th style="width:110px">Terisi</th>
          <th style="width:140px">Terverifikasi</th>
          <th style="width:110px">Pending</th>
          <th style="width:110px">Ditolak</th>
          <th style="width:150px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($rows as $i => $r)
        @php
          $terisi  = (int)($r['terisi']   ?? 0);
          $verif   = (int)($r['verif']    ?? 0);
          $pending = (int)($r['pending']  ?? 0);
          $ditolak = (int)($r['ditolak']  ?? 0);
          $pct     = isset($r['progress']) ? (int)$r['progress'] : (($verif + $pending) > 0 ? (int) round(($verif / ($verif + $pending)) * 100) : 0);

          $btnHref  = route('verifikasi.detail', ['user' => $r['user_id'], 'kegiatan_id' => $kegiatan_id]);
          $btnClass = $pending > 0 ? 'btn btn-sm btn-danger' : 'btn btn-sm btn-outline-primary';
          $btnText  = $pending > 0 ? ('Verifikasi'.($pending ? ' ('.$pending.')' : '')) : 'Lihat Data';
          $btnTitle = $pending > 0 ? 'Ada item draft yang menunggu verifikasi' : 'Tinjau data yang sudah diverifikasi / ditolak';
        @endphp
        <tr class="{{ $i % 2 === 0 ? 'table-light' : '' }}">
          <td class="text-center">{{ $i + 1 }}</td>
          <td>{{ $r['nama'] }}</td>

          {{-- Progress bar --}}
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress w-100" style="height: 8px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ $pct }}%;"
                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <span class="small text-nowrap">{{ $pct }}%</span>
            </div>
          </td>

          <td class="text-center">
            <span class="badge bg-secondary-subtle text-secondary border px-3">{{ $terisi }}</span>
          </td>

          <td class="text-center">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3">{{ $verif }}</span>
          </td>

          <td class="text-center">
            <span class="badge {{ $pending > 0 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary border' }} px-3">
              {{ $pending }}
            </span>
          </td>

          <td class="text-center">
            <span class="badge {{ $ditolak > 0 ? 'bg-danger' : 'bg-secondary-subtle text-secondary border' }} px-3">
              {{ $ditolak }}
            </span>
          </td>

          <td class="text-center">
            @if ($terisi > 0)
              <a href="{{ $btnHref }}" class="{{ $btnClass }}" title="{{ $btnTitle }}">
                <i class="bi {{ $pending > 0 ? 'bi-check2-square' : 'bi-eye' }} me-1"></i>
                {{ $btnText }}
              </a>
            @else
              <span class="text-muted">Belum Ada</span>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center text-muted">Tidak ada data</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="small text-muted mt-2">
    <i class="bi bi-info-circle me-1"></i>
    <em>
      Pending = <strong>draft</strong>;
      Ditolak = <strong>ditolak</strong>;
      Progres = <strong>terverifikasi / (terverifikasi + draft)</strong>.
    </em>
  </div>
</div>
@endsection

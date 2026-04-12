@extends('layouts.dashboard')

@section('content')
<div class="container-fluid py-4">
  <h4 class="fw-bold mb-4">
    <i class="bi bi-shield-check me-2 text-danger"></i>
    Verifikasi Penilaian – {{ $user->name }}
  </h4>

  {{-- Flash --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="mb-3">
    <strong>Kegiatan:</strong> {{ $kegiatan->nama }} ({{ $kegiatan->tahun }})
  </div>

  {{-- Fallback bulk submit --}}
  <form method="POST" action="{{ route('verifikasi.simpan', [$user->id, $kegiatan->id]) }}" id="form-bulk">
    @csrf

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-danger text-center">
        <tr>
          <th style="width: 56px">No</th>
          <th>Variabel</th>
          <th style="width: 80px">Tingkat</th>
          <th>Indikator</th>
          <th style="min-width: 260px">Bukti Dukung</th>
          <th style="width: 110px">Status</th>
          {{-- diperbesar --}}
          <th style="min-width: 340px">Catatan</th>
          <th style="width: 180px">Aksi</th>
        </tr>
        </thead>

        <tbody id="tbody-verifikasi">
        @forelse ($penilaian as $i => $item)
          @php
            $j  = $item->jawabanLatest;          // TERBARU
            $st = $j?->status;                   // draft | diterima | ditolak
            $approveUrl = $j ? route('verifikasi.approve', $j->id) : null;
            $rejectUrl  = $j ? route('verifikasi.reject',  $j->id) : null;
          @endphp

          <tr data-row
              data-detail-id="{{ $item->id }}"
              data-approve-url="{{ $approveUrl }}"
              data-reject-url="{{ $rejectUrl }}">
            <td class="text-center align-middle">{{ $i + 1 }}</td>

            <td class="align-middle">{{ $item->variabel->nama }}</td>

            <td class="text-center align-middle">{{ $item->tingkat->label }}</td>

            <td class="align-middle">
              {{ $item->indikator->deskripsi ?? '-' }}
            </td>

            <td class="align-middle">
              <ul class="list-unstyled mb-0">
                @forelse ($item->dokumen ?? [] as $dok)
                  @php
                    $relPath = ltrim($dok->file_path, '/');
                    $href = asset('storage/app/public/'.$relPath);

                    $sizeStr = '–';
                    try {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relPath)) {
                            $bytes = \Illuminate\Support\Facades\Storage::disk('public')->size($relPath);
                            $units = ['B','KB','MB','GB','TB']; $i2 = 0; $num = (float) $bytes;
                            while ($num >= 1024 && $i2 < count($units) - 1) { $num /= 1024; $i2++; }
                            $sizeStr = number_format($num, $i2 ? 1 : 0).' '.$units[$i2];
                        } else {
                            $sizeStr = 'tidak ditemukan';
                        }
                    } catch (\Throwable $e) {
                        $sizeStr = 'ukuran tidak diketahui';
                    }

                    $name = $dok->filename ?? \Illuminate\Support\Str::afterLast($relPath, '/');
                  @endphp

                  <li class="mb-2">
                    <a href="{{ $href }}" target="_blank" class="text-danger text-decoration-none">
                      {{ $loop->iteration }}. 📄 {{ $name }}
                    </a>
                    <span class="text-muted small">({{ $sizeStr }})</span>
                  </li>
                @empty
                  <li class="text-muted">-</li>
                @endforelse
              </ul>
            </td>

            {{-- STATUS dari jawaban latest --}}
            <td class="text-center align-middle">
              @if($st)
                <span class="badge
                  {{ $st === 'draft' ? 'bg-secondary'
                     : ($st === 'diterima' ? 'bg-success'
                     : 'bg-warning text-dark') }}">
                  {{ ucfirst($st) }}
                </span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>

            {{-- ganti input → textarea (multi-line) --}}
            <td class="align-middle">
              <textarea
                name="catatan[{{ $item->id }}]"
                class="form-control"
                rows="4"
                style="min-height: 110px; resize: vertical;"
                placeholder="Catatan verifikator (opsional saat approve, wajib saat reject). Tekan Enter untuk baris baru.">{{ $j?->komentar ?? '' }}</textarea>
            </td>

            <td class="text-center align-middle">
              @if($st === 'draft' && $approveUrl && $rejectUrl)
                <div class="d-flex gap-2 justify-content-center">
                  {{-- untuk fallback bulk jika user menekan Simpan Verifikasi --}}
                  <input type="hidden" name="status[{{ $item->id }}]" value="">
                  <button type="button" class="btn btn-success btn-sm js-approve">
                    <i class="bi bi-check2-circle me-1"></i> Approve
                  </button>
                  <button type="button" class="btn btn-warning btn-sm js-reject">
                    <i class="bi bi-x-circle me-1"></i> Reject
                  </button>
                </div>
                <div class="text-muted small mt-1 d-none js-row-status">Memproses...</div>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center">Tidak ada item verifikasi.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4 d-flex justify-content-end gap-2">
      <a href="{{ route('verifikasi.index', ['kegiatan_id' => $kegiatan->id]) }}"
         class="btn btn-outline-secondary">← Kembali</a>
      <button type="submit" class="btn btn-danger" id="btn-bulk">
        <i class="bi bi-save me-1"></i> Simpan Verifikasi
      </button>
    </div>
  </form>
</div>

{{-- JS Approve/Reject (AJAX) --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tbody   = document.getElementById('tbody-verifikasi');
  const form    = document.getElementById('form-bulk');
  const btnBulk = document.getElementById('btn-bulk');
  const csrf    = (form.querySelector('input[name="_token"]') || {}).value
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  async function post(url, data) {
    const opts = {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: (data instanceof FormData) ? data : new URLSearchParams(data)
    };
    const res = await fetch(url, opts);
    let json = null;
    try { json = await res.json(); } catch (_) {}
    if (!res.ok || !json) {
      const msg = (json && (json.message || (json.errors && Object.values(json.errors)[0]?.[0])))
                  || ('HTTP ' + res.status);
      throw new Error(msg);
    }
    return json;
  }

  function removeRow(row) {
    row.style.opacity = .5;
    setTimeout(() => row.remove(), 180);
  }

  function toggleBulk() {
    const alive = !!tbody.querySelector('tr[data-row]');
    btnBulk.disabled = !alive;
    btnBulk.classList.toggle('disabled', !alive);
  }

  tbody.querySelectorAll('tr[data-row]').forEach(row => {
    const detailId   = row.dataset.detailId;
    const approveUrl = row.dataset.approveUrl;
    const rejectUrl  = row.dataset.rejectUrl;

    // UPDATE: selector ambil dari <textarea>, bukan <input>
    const noteInput  = row.querySelector(`textarea[name="catatan[${detailId}]"]`);
    const rowStatus  = row.querySelector('.js-row-status');
    const btnApprove = row.querySelector('.js-approve');
    const btnReject  = row.querySelector('.js-reject');

    if (!approveUrl || !rejectUrl) {
      btnApprove?.setAttribute('disabled', 'disabled');
      btnReject?.setAttribute('disabled', 'disabled');
      return;
    }

    btnApprove?.addEventListener('click', async () => {
      btnApprove.disabled = true; btnReject.disabled = true;
      rowStatus.classList.remove('d-none'); rowStatus.textContent = 'Menyetujui...';

      try {
        await post(approveUrl, { komentar: (noteInput?.value || '') });
        const hid = form.querySelector(`input[name="status[${detailId}]"]`);
        if (hid) hid.value = 'acc';
        removeRow(row);
        toggleBulk();
      } catch (e) {
        alert(e.message || 'Gagal approve');
        btnApprove.disabled = false; btnReject.disabled = false;
        rowStatus.classList.add('d-none');
      }
    });

    btnReject?.addEventListener('click', async () => {
      const komentar = (noteInput?.value || '').trim();
      if (!komentar) { alert('Catatan wajib diisi saat menolak.'); return; }

      btnApprove.disabled = true; btnReject.disabled = true;
      rowStatus.classList.remove('d-none'); rowStatus.textContent = 'Menolak...';

      try {
        const fd = new FormData(); fd.append('komentar', komentar);
        await post(rejectUrl, fd);
        const hid = form.querySelector(`input[name="status[${detailId}]"]`);
        if (hid) hid.value = 'revisi';
        removeRow(row);
        toggleBulk();
      } catch (e) {
        alert(e.message || 'Gagal reject');
        btnApprove.disabled = false; btnReject.disabled = false;
        rowStatus.classList.add('d-none');
      }
    });
  });

  toggleBulk();
});
</script>
@endsection

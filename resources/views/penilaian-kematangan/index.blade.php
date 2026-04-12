@extends('layouts.dashboard')

@section('content')
<div class="container-fluid py-4">
    {{-- Judul Halaman --}}
    <div class="mb-4">
        <h4 class="fw-bold text-dark">
            <i class="bi bi-journal-text me-2 text-danger"></i>
            {{ $kegiatanAktif->nama ?? 'Penilaian Kematangan' }}
            <span class="text-muted">({{ $kegiatanAktif->tahun ?? 'Tahun tidak tersedia' }})</span>
        </h4>
        <hr>
    </div>

    {{-- EMPTY STATE --}}
    @if ($variabels->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div class="mb-2">
                    <i class="bi bi-info-circle me-2"></i>
                    Belum ada variabel pada kegiatan ini.
                </div>

                @php $role = strtolower(auth()->user()->role ?? ''); @endphp
                @if (in_array($role, ['admin','superadmin']))
                    <a href="{{ route('variabel.index', ['kegiatan' => $kegiatanAktif->slug]) }}"
                       class="btn btn-danger mt-2">
                        Kelola Variabel Kegiatan
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- ===================== TAB STRIP VARIABEL ===================== --}}
        @php
            $currentTabId = (int) request('tab');
            if (!$variabels->firstWhere('id', $currentTabId)) {
                $currentTabId = (int) optional($variabels->first())->id;
            }
        @endphp

        <div class="mb-4 d-flex flex-wrap gap-2" role="tablist" aria-label="Variabel Penilaian">
            @foreach ($variabels as $v)
                @php
                    $active   = (int) $v->id === $currentTabId;
                    $hasDocs  = (int) ($statusVariabel[$v->id] ?? 0) > 0;
                    $tingkat  = $tingkatPerVar[$v->id] ?? null;           // 'I' | 'II' | ...
                    $vf       = $verifPerVar[$v->id] ?? null;             // ['status' => '...', 'komentar' => ...]
                    $vfStatus = $vf['status'] ?? null;                    // 'diterima' | 'ditolak' | 'draft' | null

                    $btnClass = 'btn btn-sm d-flex align-items-center gap-1 ';
                    if ($active) {
                        $btnClass .= 'btn-danger text-white';
                    } elseif ($vfStatus === 'diterima') {
                        $btnClass .= 'btn-success text-white';
                    } elseif ($vfStatus === 'ditolak') {
                        $btnClass .= 'btn-warning text-dark';
                    } elseif ($vfStatus === 'draft') {
                        $btnClass .= 'btn-primary text-white';
                    } else {
                        $btnClass .= 'btn-outline-secondary';
                    }

                    $title = ($hasDocs ? 'Sudah upload' : 'Belum upload') . ($tingkat ? ' • Tingkat: '.$tingkat : '');
                @endphp

                <a  href="{{ route('penilaian-kematangan.index', ['kegiatan' => $kegiatanAktif->slug, 'tab' => $v->id]) }}"
                    class="{{ $btnClass }}"
                    role="tab"
                    aria-selected="{{ $active ? 'true' : 'false' }}"
                    title="{{ $title }}">
                    <span>Variabel {{ $loop->iteration }}</span>

                    {{-- status verifikasi --}}
                    @if ($vfStatus === 'diterima')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Terverifikasi</span>
                    @elseif ($vfStatus === 'ditolak')
                        <span class="badge bg-warning text-dark">Ditolak</span>
                    @elseif ($vfStatus === 'draft')
                        <span class="badge bg-primary">Draft</span>
                    @else
                        @if ($hasDocs)
                            <i class="bi bi-check-circle-fill text-success ms-1" title="Sudah upload"></i>
                        @else
                            <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Belum upload"></i>
                        @endif
                    @endif

                    {{-- badge tingkat --}}
                    @if ($tingkat)
                        <span class="badge bg-secondary-subtle text-secondary border">{{ $tingkat }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- ===================== KONTEN VARIABEL AKTIF ===================== --}}
        @php
            $aktif       = $variabels->firstWhere('id', $currentTabId) ?? $variabels->first();
            $indexAktif  = $variabels->search(fn($x) => (int)$x->id === (int)$aktif->id) + 1;
            $vAktif      = $verifPerVar[$aktif->id] ?? null; // ['status','komentar']
            $vStatus     = $vAktif['status'] ?? null;        // diterima | ditolak | draft
        @endphp

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-4">Variabel {{ $indexAktif }}: {{ $aktif->nama }}</h5>

                {{-- alerts --}}
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
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                {{-- catatan / status verifikator --}}
                @if ($vStatus === 'ditolak')
                    <div class="alert alert-warning d-flex align-items-start" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Catatan Verifikator:</strong>
                            <div class="mt-1">{{ $vAktif['komentar'] ?: 'Tidak ada catatan.' }}</div>
                        </div>
                    </div>
                @elseif ($vStatus === 'diterima')
                    <div class="alert alert-success d-flex align-items-start" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>
                            Variabel ini sudah <strong>terverifikasi</strong>. Mengubah & menyimpan ulang akan mengirim versi baru untuk diverifikasi.
                        </div>
                    </div>
                @elseif ($vStatus === 'draft')
                    <div class="alert alert-primary d-flex align-items-start" role="status">
                        <i class="bi bi-hourglass-split me-2"></i>
                        <div>
                            Versi terbaru variabel ini <strong>menunggu verifikasi</strong>.
                        </div>
                    </div>
                @endif

                {{-- ================= RINGKASAN VERSI TERAKHIR (status apapun) ================= --}}
                @php
                    // detail TERBARU untuk user + kegiatan + variabel aktif
                    $latestDetail = \DB::table('penilaian_detail as pd')
                        ->join('penilaian as p', 'p.id', '=', 'pd.penilaian_id')
                        ->where('p.user_id', auth()->id())
                        ->where('p.kegiatan_id', $kegiatanAktif->id)
                        ->where('pd.variabel_id', $aktif->id)
                        ->orderByDesc('pd.id')
                        ->select('pd.id','pd.tingkat_id')
                        ->first();

                    $ringkasan = [
                        'status' => null,
                        'tingkat_label' => null,
                        'indikator' => null,
                        'dokumen' => [],   // each: ['nama'=>..., 'href'=>...]
                    ];

                    if ($latestDetail) {
                        $ringkasan['tingkat_label'] = \DB::table('tingkat_penilaian')->where('id', $latestDetail->tingkat_id)->value('label');

                        $jawaban = \DB::table('jawaban_indikator')
                            ->where('penilaian_detail_id', $latestDetail->id)
                            ->where('is_latest', 1)
                            ->orderByDesc('id')
                            ->first();

                        if ($jawaban) {
                            $ringkasan['status']   = $jawaban->status; // diterima/ditolak/draft
                            $ringkasan['indikator']= \DB::table('indikator_variabel')->where('id',$jawaban->indikator_id)->value('deskripsi');

                            $docs = \DB::table('dokumen_indikator')
                                ->where('jawaban_id', $jawaban->id)
                                ->orderBy('id')
                                ->get(['nama_dokumen','file_path']);

                            foreach ($docs as $d) {
                                $path = ltrim($d->file_path ?? '', '/');
                                // mengikuti kebiasaan link lama: /storage/app/public/....
                                $href = asset('storage/app/public/' . $path);
                                $ringkasan['dokumen'][] = [
                                    'nama' => $d->nama_dokumen ?: basename($path),
                                    'href' => $href,
                                ];
                            }
                        }
                    }

                    // helper badge status
                    $badge = function ($s) {
                        return match($s) {
                            'diterima','terverifikasi' => '<span class="badge bg-success">Terverifikasi</span>',
                            'ditolak'                  => '<span class="badge bg-warning text-dark">Ditolak</span>',
                            'draft'                    => '<span class="badge bg-primary">Draft</span>',
                            default                    => '<span class="badge bg-secondary">-</span>',
                        };
                    };
                @endphp

                <div class="border rounded p-3 mb-4 bg-light">
                    <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
                        <strong>Ringkasan Versi Terakhir</strong>
                        {!! $badge($ringkasan['status']) !!}
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="text-muted small">Tingkat</div>
                            <div class="fw-semibold">{{ $ringkasan['tingkat_label'] ?: '—' }}</div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="text-muted small">Nama Indikator</div>
                            <div>{{ $ringkasan['indikator'] ?: '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Dokumen Terunggah</div>
                            @if (count($ringkasan['dokumen']))
                                <ol class="mb-0">
                                    @foreach ($ringkasan['dokumen'] as $i => $dok)
                                        <li class="mb-1">
                                            <a href="{{ $dok['href'] }}" target="_blank" class="text-danger text-decoration-none">
                                                {{ $dok['nama'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <div class="text-muted">Belum ada dokumen pada versi terakhir.</div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- ================= END RINGKASAN ================= --}}

                {{-- Filter/Pilih Tingkat --}}
                <form method="GET" action="{{ route('penilaian-kematangan.index', ['kegiatan' => $kegiatanAktif->slug]) }}" class="mb-4">
                    <input type="hidden" name="tab" value="{{ $aktif->id }}">
                    <div class="mb-3">
                        <label for="tingkat" class="form-label fw-semibold">Pilih Tingkat:</label>
                        <select name="tingkat_id" id="tingkat" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Tingkat --</option>
                            @foreach ($aktif->tingkat as $t)
                                <option value="{{ $t->id }}" {{ request('tingkat_id') == $t->id ? 'selected' : '' }}>
                                    {{ $t->label }} ({{ $t->poin }} poin)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                {{-- Form Upload (muncul jika tingkat dipilih) --}}
                @if (!empty($terpilihTingkat))
                    <form method="POST"
                          action="{{ route('penilaian-kematangan.simpan', ['kegiatan' => $kegiatanAktif->slug, 'variabel' => $aktif->id]) }}"
                          enctype="multipart/form-data" id="form-penilaian" aria-label="Form Penilaian Variabel">
                        @csrf
                        <input type="hidden" name="tingkat_id" value="{{ $terpilihTingkat->id }}">

                        {{-- Indikator --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-list-check me-1"></i> Indikator:
                            </label>
                            <div class="bg-light border rounded p-2">
                                {{ optional($terpilihTingkat->indikator)->deskripsi ?? 'Belum ada deskripsi indikator' }}
                            </div>
                        </div>

                        {{-- Upload Bukti (pre-upload AJAX per file) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Bukti Pendukung (PDF):
                            </label>
                            <div class="small text-muted mb-2">Semua dokumen harus PDF, maksimum 50 MB per file.</div>

                            @forelse ($terpilihTingkat->bukti as $bukti)
                                <div class="mb-3">
                                    <label class="form-label small mb-1">
                                        {{ $loop->iteration }}. {{ $bukti->nama_dokumen ?? 'Tanpa Nama' }}
                                    </label>

                                    <input type="file" class="form-control js-bukti"
                                           accept="application/pdf" data-bukti-id="{{ $bukti->id }}">

                                    <input type="hidden" name="bukti_temp[{{ $bukti->id }}]" class="js-bukti-temp" value="">
                                    <input type="hidden" name="bukti_temp_name[{{ $bukti->id }}]" class="js-bukti-name" value="">

                                    <div class="progress mt-2 d-none" style="height:6px;">
                                        <div class="progress-bar" role="progressbar" style="width:0%"></div>
                                    </div>
                                    <div class="small mt-1 text-muted js-bukti-status" aria-live="polite"></div>
                                </div>
                            @empty
                                <div class="text-muted">Belum ada dokumen yang ditentukan untuk tingkat ini.</div>
                            @endforelse
                        </div>

                        {{-- Aksi --}}
                        <div class="mt-4 d-flex flex-column flex-sm-row gap-2 justify-content-sm-end">
                            <a href="{{ route('penilaian-kematangan.index', ['kegiatan' => $kegiatanAktif->slug, 'tab' => $aktif->id]) }}"
                               class="btn btn-outline-secondary">← Kembali</a>

                            <button type="submit" class="btn btn-danger" id="btn-submit" disabled>
                                <i class="bi bi-save me-1"></i> Simpan Variabel Ini
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- JS pre-upload --}}
        @if (!empty($terpilihTingkat))
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('form-penilaian');
                const submitBtn = document.getElementById('btn-submit');

                function getCsrf() {
                    const f = form.querySelector('input[name="_token"]');
                    return f ? f.value : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                }

                function updateSubmitState() {
                    const hiddens = form.querySelectorAll('.js-bukti-temp');
                    let ok = true;
                    hiddens.forEach(h => { if (!h.value) ok = false; });
                    submitBtn.disabled = !ok;
                }

                form.querySelectorAll('.js-bukti').forEach(input => {
                    input.addEventListener('change', function () {
                        const file = this.files[0];
                        const wrap = this.closest('.mb-3');
                        const buktiId = this.getAttribute('data-bukti-id');
                        const prog = wrap.querySelector('.progress');
                        const bar  = wrap.querySelector('.progress-bar');
                        const hid  = wrap.querySelector('.js-bukti-temp');
                        const hidName = wrap.querySelector('.js-bukti-name');
                        const status  = wrap.querySelector('.js-bukti-status');

                        status.textContent = '';
                        status.classList.remove('text-danger','text-success');
                        bar.style.width = '0%';
                        prog.classList.add('d-none');

                        if (!file) { updateSubmitState(); return; }
                        if (file.type !== 'application/pdf') {
                            alert('File harus PDF.'); this.value = ''; updateSubmitState(); return;
                        }
                        if (file.size > 50 * 1024 * 1024) {
                            alert('Maksimal 50 MB per file.'); this.value = ''; updateSubmitState(); return;
                        }

                        const xhr = new XMLHttpRequest();
                        const fd  = new FormData();
                        fd.append('file', file);
                        fd.append('bukti_id', buktiId);

                        xhr.open('POST', "{{ route('ajax.upload-temp') }}", true);
                        xhr.setRequestHeader('X-CSRF-TOKEN', getCsrf());

                        xhr.upload.onprogress = function (e) {
                            if (e.lengthComputable) {
                                const p = Math.round((e.loaded / e.total) * 100);
                                prog.classList.remove('d-none');
                                bar.style.width = p + '%';
                            }
                        };

                        xhr.onload = function () {
                          if (xhr.status === 200) {
                            try {
                              const res = JSON.parse(xhr.responseText);
                              if (res.success) {
                                hid.value = res.temp_path;
                                hidName.value = res.original_name || file.name;
                                input.value = '';

                                status.textContent = 'Terunggah: ' + (res.original_name || file.name);
                                status.classList.add('text-success');
                                bar.style.width = '100%';
                              } else {
                                throw new Error('Gagal mengunggah.');
                              }
                            } catch (err) {
                              status.textContent = 'Gagal mengunggah.';
                              status.classList.add('text-danger');
                              hid.value = ''; hidName.value = '';
                              bar.style.width = '0%'; prog.classList.add('d-none');
                            }
                          } else {
                            status.textContent = 'Gagal mengunggah (HTTP ' + xhr.status + ').';
                            status.classList.add('text-danger');
                            hid.value = ''; hidName.value = '';
                            bar.style.width = '0%'; prog.classList.add('d-none');
                          }
                          updateSubmitState();
                        };

                        xhr.onerror = function () {
                            status.textContent = 'Gagal mengunggah (jaringan).';
                            status.classList.add('text-danger');
                            hid.value = ''; hidName.value = '';
                            bar.style.width = '0%'; prog.classList.add('d-none');
                            updateSubmitState();
                        };

                        xhr.send(fd);
                        updateSubmitState();
                    });
                });

                updateSubmitState();
            });
            </script>
        @endif
    @endif {{-- end empty state --}}
</div>
@endsection

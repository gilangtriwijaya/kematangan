@extends('layouts.dashboard')

@section('title','Dashboard OPD')

@section('content')

  <div class="alert alert-success" role="status">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>!<br>
    Anda login sebagai <strong>OPD</strong>.
  </div>

  {{-- Dropdown Pilih Kegiatan --}}
  <form method="GET" action="{{ route('dashboard') }}" class="mb-3" aria-label="Pilih Kegiatan Penilaian">
    <div class="d-flex align-items-center gap-2">
      <label for="kegiatan_id" class="form-label mb-0">Pilih Kegiatan:</label>
      <select name="kegiatan_id" id="kegiatan_id" class="form-select w-auto" onchange="this.form.submit()">
        @forelse ($daftarKegiatan as $item)
          <option value="{{ $item->id }}" {{ (int)($kegiatan_id ?? 0) === (int)$item->id ? 'selected':'' }}>
            {{ $item->nama }} ({{ $item->tahun }})
          </option>
        @empty
          <option value="0" selected>— Belum ada kegiatan —</option>
        @endforelse
      </select>
    </div>
  </form>

  {{-- KPI cards --}}
  <div class="row g-3 mb-3" aria-live="polite" aria-atomic="true">
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Pengisian</div>
        <div class="fs-3 fw-bold" id="kpi-pengisian">—</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Persentase Pengisian</div>
        <div class="fs-3 fw-bold" id="kpi-completion">—</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Poin</div>
        <div class="fs-3 fw-bold">
          <span id="kpi-poin">—</span>
          <span class="small text-muted" id="kpi-poin-kat"></span>
        </div>
      </div></div>
    </div>
  </div>

  {{-- Tracking per variabel --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Tracking Pengisian (per Variabel)</div>
        <div class="small">
          <span class="badge bg-primary" title="Terverifikasi">✅ Terverifikasi</span>
          <span class="badge bg-danger" title="Ditolak">❌ Ditolak</span>
          <span class="badge bg-warning text-dark" title="Menunggu">⏳ Menunggu</span>
          <span class="text-muted" title="Belum diisi">– Belum diisi</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle small mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col" style="width:56px">#</th>
              <th scope="col">Variabel</th>
              <th scope="col" style="width:160px" class="text-center">Status</th>
              <th scope="col" style="width:120px" class="text-end">Poin</th>
            </tr>
          </thead>
          <tbody id="tb-opd-tracking">
            <tr><td colspan="4" class="text-center text-muted">Memuat…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Bar chart poin per variabel --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="text-muted small mb-2">Poin Terverifikasi per Variabel</div>
      <div id="chart-var" style="height:380px;" role="img" aria-label="Grafik batang poin terverifikasi per variabel"></div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function () {
  const kegiatanId = {{ (int)($kegiatan_id ?? 0) }};
  const q = kegiatanId ? ('?kegiatan_id=' + encodeURIComponent(kegiatanId)) : '';

  const endpointSummary = @json(route('api.dash.summary')); // KPI
  const endpointDetail  = @json(route('api.dash.detail'));  // tracking + chart

  const setText = (id, t) => { const el = document.getElementById(id); if (el) el.textContent = t; };
  const esc = (t)=> (t||'').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const fmtInt = (n)=> Number(n||0).toLocaleString('id-ID');
  const fmtPct = (x)=> `${(Number(x||0)*100).toFixed(1)}%`;

  function kategoriPoin(n) {
    const v = Number(n || 0);
    if (v >= 46.1) return 'Sangat Tinggi';
    if (v >= 37.1) return 'Tinggi';
    if (v >= 28.1) return 'Sedang';
    if (v >= 19.1) return 'Rendah';
    if (v >= 10.0) return 'Sangat Rendah';
    return 'Sangat Rendah';
  }

  // Badge status - enum standar: 'terverifikasi'|'ditolak'|'draft'|'none'
  function badgeNode(status) {
    const span = document.createElement('span');
    span.setAttribute('role','status');
    switch (status) {
      case 'terverifikasi':
        span.className = 'badge bg-primary';
        span.title = 'Status: Terverifikasi';
        span.textContent = '✅ Terverifikasi';
        break;
      case 'ditolak':
        span.className = 'badge bg-danger';
        span.title = 'Status: Ditolak';
        span.textContent = '❌ Ditolak';
        break;
      case 'draft':
        span.className = 'badge bg-warning text-dark';
        span.title = 'Status: Menunggu';
        span.textContent = '⏳ Menunggu';
        break;
      default:
        span.className = 'text-muted';
        span.title = 'Status: Belum diisi';
        span.textContent = '–';
    }
    return span;
  }

  // Fetch helper dengan timeout & cek response.ok
  async function fetchJson(url, {timeoutMs=10000} = {}) {
    const controller = new AbortController();
    const to = setTimeout(() => controller.abort(), timeoutMs);
    try {
      const res = await fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } finally {
      clearTimeout(to);
    }
  }

  // Guard: jika tidak ada kegiatan, tampilkan state kosong & hentikan
  if (!kegiatanId) {
    setText('kpi-pengisian', '0 / 0');
    setText('kpi-completion', '0.0%');
    setText('kpi-poin', '0');
    setText('kpi-poin-kat', '');
    const tb = document.getElementById('tb-opd-tracking');
    if (tb) tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada kegiatan aktif</td></tr>';
    return;
  }

  // 1) KPI (summary) —> poin dari your_opd (latest + terverifikasi)
  fetchJson(endpointSummary + q).then(s => {
    const total  = Number(s?.totals?.var_total ?? 0);
    const sub    = Number(s?.totals?.var_submitted ?? 0);
    const rate   = Number(s?.progress?.completion_rate ?? 0);
    const your   = Number(s?.poin?.your_opd ?? 0);

    setText('kpi-pengisian', `${fmtInt(sub)} / ${fmtInt(total)}`);
    setText('kpi-completion', fmtPct(rate));
    setText('kpi-poin', fmtInt(your));
    setText('kpi-poin-kat', your ? `(${kategoriPoin(your)})` : '');
  }).catch(() => {
    setText('kpi-pengisian', '0 / 0');
    setText('kpi-completion', '0.0%');
    setText('kpi-poin', '0');
    setText('kpi-poin-kat', '');
  });

  // 2) Tracking + Chart (detail)
  fetchJson(endpointDetail + q).then(d => {
    // ===== TABEL =====
    const tb = document.getElementById('tb-opd-tracking');
    tb.innerHTML = '';
    const rows = Array.isArray(d?.tracking) ? d.tracking : [];

    if (!rows.length) {
      tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>';
    } else {
      const frag = document.createDocumentFragment();
      rows.forEach(row => {
        const tr = document.createElement('tr');

        const tdNo = document.createElement('td');
        tdNo.textContent = (row.no ?? '').toString();
        tr.appendChild(tdNo);

        const tdVar = document.createElement('td');
        tdVar.textContent = esc(row.variabel ?? '');
        tr.appendChild(tdVar);

        const tdSt = document.createElement('td');
        tdSt.className = 'text-center';
        tdSt.appendChild(badgeNode(row.status));
        tr.appendChild(tdSt);

        const tdP = document.createElement('td');
        tdP.className = 'text-end';
        tdP.textContent = fmtInt(row.poin ?? 0);
        tr.appendChild(tdP);

        frag.appendChild(tr);
      });
      tb.appendChild(frag);
    }

    // ===== CHART =====
    const chartEl = document.getElementById('chart-var');
    if (!chartEl || typeof echarts === 'undefined') return;

    // dispose jika sudah ada instance sebelumnya
    const old = echarts.getInstanceByDom(chartEl);
    if (old) old.dispose();
    const ec = echarts.init(chartEl);

    // util: bungkus label per kata ke multi-line (max ~18 char/line)
    function wrapLabel(text, max = 18) {
      if (!text) return '';
      const words = String(text).split(' ');
      const lines = [];
      let cur = '';
      for (const w of words) {
        const add = cur ? cur + ' ' + w : w;
        if (add.length > max) {
          if (cur) lines.push(cur);
          if (w.length > max) {
            // pecah kata yang sangat panjang
            for (let i = 0; i < w.length; i += max) {
              const chunk = w.slice(i, i + max);
              if (chunk.length === max) lines.push(chunk); else cur = chunk;
            }
          } else {
            cur = w;
          }
        } else {
          cur = add;
        }
      }
      if (cur) lines.push(cur);
      return lines.join('\n');
    }

    const labels = (d.var_chart || []).map(it => it.variabel);
    const values = (d.var_chart || []).map(it => Number(it.total_poin || 0));

    ec.setOption({
      grid: { left: 40, right: 20, top: 20, bottom: 120 }, // ruang ekstra untuk label multi-line
      tooltip: { trigger: 'axis' },
      dataZoom: [
        { type: 'slider', xAxisIndex: 0, height: 18, bottom: 60, brushSelect: false },
        { type: 'inside', xAxisIndex: 0 }
      ],
      xAxis: {
        type: 'category',
        data: labels,
        axisLabel: {
          interval: 0,
          rotate: 0,
          lineHeight: 16,
          margin: 16,
          hideOverlap: true,
          formatter: (val) => wrapLabel(val, 18)
        }
      },
      yAxis: { type: 'value' },
      dataset: { source: values.map((v, i) => [labels[i], v]) },
      series: [{ type: 'bar', encode: { x: 0, y: 1 }, barMaxWidth: 36 }]
    });
    window.addEventListener('resize', () => ec.resize());
  }).catch(() => {
    const tb = document.getElementById('tb-opd-tracking');
    tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Gagal memuat data</td></tr>';
  });

})();
</script>
@endpush

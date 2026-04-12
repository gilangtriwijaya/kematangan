@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
  <style>
    .badge-orange{ background-color:#fd7e14; color:#212529; border-radius:.375rem; font-weight:600; padding:.35em .65em; font-size:.85em; }
    .dl-toolbar .btn{ --bs-btn-padding-y:.15rem; --bs-btn-padding-x:.5rem; --bs-btn-font-size:.75rem; }
    /* Supaya background putih saat render ke PNG */
    .png-white-bg{ background:#ffffff; }
  </style>

  <div class="alert alert-success">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>!<br>
    Anda login sebagai <strong>{{ strtoupper(auth()->user()->role) }}</strong>.
  </div>

  {{-- Dropdown Pilih Kegiatan --}}
  <form method="GET" action="{{ route('dashboard') }}" class="mb-3">
    <div class="d-flex align-items-center gap-2">
      <label for="kegiatan_id" class="form-label mb-0">Pilih Kegiatan:</label>
      <select name="kegiatan_id" id="kegiatan_id" class="form-select w-auto" onchange="this.form.submit()">
        @forelse ($daftarKegiatan as $item)
          <option value="{{ $item->id }}" {{ (int)($kegiatan_id ?? 0) === (int)$item->id ? 'selected' : '' }}>
            {{ $item->nama }} ({{ $item->tahun }})
          </option>
        @empty
          <option disabled selected>Belum ada kegiatan</option>
        @endforelse
      </select>
    </div>
  </form>

  {{-- KPI cards --}}
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">OPD Mengisi</div>
        <div class="fs-3 fw-bold" id="kpi-opd-mengisi">…</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Persentase Pengisian</div>
        <div class="fs-3 fw-bold" id="kpi-completion">…</div>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Poin Kabupaten</div>
        <div class="fs-3 fw-bold" id="kpi-poin-kab">…</div>
      </div></div>
    </div>
  </div>

  {{-- Donut: Status Entri + Sebaran OPD --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">Status Entri</div>
            <div class="dl-toolbar">
              <button id="btn-png-status" class="btn btn-outline-secondary btn-sm">⬇︎ PNG</button>
              <button id="btn-csv-status" class="btn btn-outline-secondary btn-sm">⬇︎ CSV</button>
            </div>
          </div>
          <div id="chart-status" style="height:300px;"></div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">Sebaran OPD per Kategori</div>
            <div class="dl-toolbar">
              <button id="btn-png-tier" class="btn btn-outline-secondary btn-sm">⬇︎ PNG</button>
              <button id="btn-csv-tier" class="btn btn-outline-secondary btn-sm">⬇︎ CSV</button>
            </div>
          </div>
          <div id="chart-opd-tier" style="height:300px;"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Tabel nilai per OPD --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Nilai Per OPD</div>
        <div class="dl-toolbar">
          <button id="btn-png-opd" class="btn btn-outline-secondary btn-sm">⬇︎ PNG</button>
          <button id="btn-csv-opd" class="btn btn-outline-secondary btn-sm">⬇︎ CSV</button>
        </div>
      </div>
      <div id="wrap-opd-table" class="table-responsive png-white-bg">
        <table class="table table-striped align-middle m-0">
          <thead class="table-light">
            <tr>
              <th style="width:56px">#</th>
              <th>OPD</th>
              <th style="width:120px" class="text-end">Poin</th>
              <th style="width:180px">Kategori</th>
            </tr>
          </thead>
          <tbody id="tbl-opd-scores">
            <tr><td colspan="4" class="text-center text-muted">Memuat…</td></tr>
          </tbody>
          <tfoot>
            <tr class="table-light">
              <td colspan="2" class="fw-semibold">Total Kabupaten</td>
              <td class="text-end fw-semibold" id="sum-opd-scores">0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  {{-- Tabel tracking  (OPD × Variabel) --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Tracking Pengisian per OPD (per Variabel)</div>
        <div class="dl-toolbar">
          <button id="btn-png-tracking" class="btn btn-outline-secondary btn-sm">⬇︎ PNG</button>
          <button id="btn-csv-tracking" class="btn btn-outline-secondary btn-sm">⬇︎ CSV</button>
        </div>
      </div>
      <div id="wrap-tracking-table" class="table-responsive png-white-bg">
        <table class="table table-bordered align-middle small m-0">
          <thead id="th-tracking" class="table-light"></thead>
          <tbody id="tb-tracking">
            <tr><td class="text-center text-muted">Memuat…</td></tr>
          </tbody>
          <tfoot id="tf-tracking"></tfoot>
        </table>
      </div>
    </div>
  </div>

  {{-- Grafik total nilai per variabel --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Total Nilai Kabupaten per Variabel</div>
        <div class="dl-toolbar">
          <button id="btn-png-var" class="btn btn-outline-secondary btn-sm">⬇︎ PNG</button>
          <button id="btn-csv-var" class="btn btn-outline-secondary btn-sm">⬇︎ CSV</button>
        </div>
      </div>
      <div id="chart-var" style="height:380px;"></div>
    </div>
  </div>
@endsection

@push('scripts')
{{-- html2canvas untuk render tabel ke PNG --}}
<script defer src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
(function () {
  const setText = (id, t) => { const el = document.getElementById(id); if (el) el.textContent = t; };
  const endpointSummary = @json(route('api.dash.summary'));
  const endpointDetail  = @json(route('api.dash.detail'));
  const kegiatanId = {{ (int)($kegiatan_id ?? 0) }};
  const q = kegiatanId ? ('?kegiatan_id=' + encodeURIComponent(kegiatanId)) : '';

  // dataset untuk export
  let lastOpdScores = [];
  let lastVariabels  = [];
  let lastTracking   = [];

  // ===== Utilities download =====
  function downloadBlob(content, filename, type='text/plain') {
    const blob = new Blob([content], {type});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 0);
  }
  function toCSV(rows) {
    return rows.map(r => r.map(v => {
      const s = String(v ?? '');
      return /[",\n]/.test(s) ? `"${s.replace(/"/g,'""')}"` : s;
    }).join(',')).join('\n');
  }
  function exportPNGFromEcharts(ec, filename) {
    if (!ec) return;
    const url = ec.getDataURL({ type:'png', pixelRatio:2, backgroundColor:'#ffffff' });
    const a = document.createElement('a');
    a.href = url; a.download = filename.endsWith('.png')?filename:filename+'.png';
    document.body.appendChild(a); a.click(); a.remove();
  }
  async function exportDomPNG(dom, filename) {
    if (!window.html2canvas || !dom) return;
    const scale = Math.max(2, window.devicePixelRatio || 2);
    const canvas = await html2canvas(dom, {
      backgroundColor: '#ffffff',
      scale,
      useCORS: true,
      windowWidth: document.documentElement.clientWidth
    });
    canvas.toBlob(blob => {
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = filename.endsWith('.png')?filename:filename+'.png';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(()=>URL.revokeObjectURL(a.href),0);
    }, 'image/png');
  }

  // ===== ECharts helpers =====
  function renderPie(domId, option) {
    const el = document.getElementById(domId);
    if (!el || typeof echarts === 'undefined') return null;
    const old = echarts.getInstanceByDom(el);
    if (old) old.dispose();
    const ec = echarts.init(el);
    ec.setOption(option);
    setTimeout(() => ec.resize(), 0);
    window.addEventListener('resize', () => ec.resize());
    return ec;
  }

  const badgeClass = (k) => {
    switch ((k||'').toLowerCase()) {
      case 'sangat tinggi': return 'badge bg-primary';
      case 'tinggi':        return 'badge bg-success';
      case 'sedang':        return 'badge bg-warning text-dark';
      case 'rendah':        return 'badge badge-orange';
      case 'sangat rendah': return 'badge bg-danger';
      default:              return 'badge bg-secondary';
    }
  };
  const statusBadge = (s) => {
    if (s === 'terverifikasi') return '<span class="badge bg-primary" title="Terverifikasi">✔</span>';
    if (s === 'ditolak')       return '<span class="badge bg-danger" title="Ditolak">✖</span>';
    if (s === 'draft')         return '<span class="badge bg-warning text-dark" title="Sudah isi, belum verifikasi">•</span>';
    return '<span class="text-muted" title="Belum pernah submit">–</span>';
  };
  const statusText = (s) => {
    if (s === 'terverifikasi') return 'terverifikasi';
    if (s === 'ditolak')       return 'ditolak';
    if (s === 'draft')         return 'draft';
    return '-';
  };
  const esc = t => (t||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

  // ====== SUMMARY (KPI + Donut Status) ======
  let ecStatus = null, ecTier = null, ecVar = null;

  fetch(endpointSummary + q, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      const totals   = data.totals || {};
      const progress = data.progress || {};
      const poin     = data.poin || {};

      setText('kpi-opd-mengisi', `${totals.opd_mengisi ?? 0} / ${totals.opd_total ?? 0}`);
      setText('kpi-completion', `${(((progress.completion_rate ?? 0)*100).toFixed(1))}%`);

      const opdDiv = Number(totals.opd_total ?? 34) || 34;
      let angkaVal = Number(poin.nilai_kabupaten);
      if (!Number.isFinite(angkaVal)) {
        const total = Number(poin.total_kabupaten ?? 0);
        angkaVal = total / opdDiv;
      }
      const angkaStr = angkaVal.toFixed(1).replace(/\.0$/, '');
      const kategori = poin.kategori ?? '';
      setText('kpi-poin-kab', kategori ? `${angkaStr} (${kategori})` : angkaStr);

      const statusData = [
        { name:'Terverifikasi', value: totals.terverifikasi ?? 0 },
        { name:'Ditolak',       value: totals.ditolak ?? 0 },
        { name:'Draft',         value: totals.draft ?? 0 },
      ];
      ecStatus = renderPie('chart-status', {
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { bottom: 0, data: statusData.map(d => d.name) },
        series: [{ type:'pie', radius:['55%','75%'], center:['50%','45%'], label:{formatter:'{b}\n{c}'}, data: statusData }]
      });

      // export chart status
      document.getElementById('btn-png-status').onclick = () => exportPNGFromEcharts(ecStatus, 'status-entri');
      document.getElementById('btn-csv-status').onclick = () => {
        const rows = [['Status','Jumlah']].concat(statusData.map(d => [d.name, d.value]));
        downloadBlob(toCSV(rows), 'status-entri.csv', 'text/csv');
      };
    })
    .catch(console.error);

  // ====== DETAIL (Tables + Bar + Donut Kategori) ======
  fetch(endpointDetail + q, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
    .then(r => r.json())
    .then(d => {
      lastOpdScores = Array.isArray(d.opd_scores) ? d.opd_scores : [];
      lastVariabels = Array.isArray(d.variabels)   ? d.variabels   : [];
      lastTracking  = Array.isArray(d.tracking)    ? d.tracking    : [];

      // 1) Tabel OPD
      const tbody = document.getElementById('tbl-opd-scores');
      const sumCell = document.getElementById('sum-opd-scores');
      tbody.innerHTML = '';
      let totalKab = 0;

      if (!lastOpdScores.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada data</td></tr>';
        sumCell.textContent = '0';
      } else {
        lastOpdScores.forEach((row, i) => {
          const p = Number(row.poin ?? 0);
          totalKab += p;
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${i+1}</td>
            <td>${esc(row.nama)}</td>
            <td class="text-end">${p.toFixed(0)}</td>
            <td><span class="${badgeClass(row.kategori)}">${esc(row.kategori || '-')}</span></td>`;
          tbody.appendChild(tr);
        });
        sumCell.textContent = totalKab.toFixed(0);
      }

      // 2) Tracking
      const thead = document.getElementById('th-tracking');
      const tb2   = document.getElementById('tb-tracking');
      const tfTracking = document.getElementById('tf-tracking');
      thead.innerHTML = ''; tb2.innerHTML = ''; tfTracking.innerHTML = '';

      let h = '<tr><th style="width:56px">#</th><th>OPD</th>';
      lastVariabels.forEach(v => h += `<th title="${esc(v.nama)}">V${v.id}</th>`);
      h += '<th style="width:120px" class="text-end">Total</th></tr>';
      thead.innerHTML = h;

      if (!lastTracking.length) {
        tb2.innerHTML = '<tr><td colspan="999" class="text-center text-muted">Belum ada data</td></tr>';
        const colSpan = 2 + (lastVariabels.length || 0);
        tfTracking.innerHTML = `<tr class="table-light fw-semibold">
          <td colspan="${colSpan}">Total Akumulasi</td><td class="text-end">0</td></tr>`;
      } else {
        let grand = 0;
        lastTracking.forEach((r, i) => {
          const total = Number(r.total ?? 0); grand += total;
          let tds = `<td>${i+1}</td><td>${esc(r.nama)}</td>`;
          lastVariabels.forEach(v => {
            const st = r.status[v.id] || null;
            tds += `<td class="text-center">${statusBadge(st)}</td>`;
          });
          tds += `<td class="text-end">${total.toFixed(0)}</td>`;
          const tr = document.createElement('tr'); tr.innerHTML = tds; tb2.appendChild(tr);
        });
        const colSpan = 2 + (lastVariabels.length || 0);
        tfTracking.innerHTML = `<tr class="table-light fw-semibold">
          <td colspan="${colSpan}">Total Akumulasi</td><td class="text-end">${grand.toFixed(0)}</td></tr>`;
      }

      // 3) Bar variabel
      if (typeof echarts !== 'undefined') {
        const elBar = document.getElementById('chart-var');
        if (elBar) {
          const old = echarts.getInstanceByDom(elBar); if (old) old.dispose();
          ecVar = echarts.init(elBar);
          const labels = (d.var_chart || []).map(x => x.variabel);
          const values = (d.var_chart || []).map(x => x.total_poin);
          ecVar.setOption({
            grid: { left: 48, right: 16, top: 20, bottom: 110 },
            tooltip: { trigger: 'axis' },
            xAxis: { type: 'category', data: labels, axisLabel: { interval: 0, lineHeight: 16, width: 120, overflow: 'break' } },
            yAxis: { type: 'value' },
            series: [{ type: 'bar', data: values, barMaxWidth: 36 }]
          });
          window.addEventListener('resize', () => ecVar.resize());

          document.getElementById('btn-png-var').onclick = () => exportPNGFromEcharts(ecVar, 'total-variabel');
          document.getElementById('btn-csv-var').onclick = () => {
            const rows = [['Variabel','Total Poin']].concat(labels.map((l,i)=>[l, values[i] ?? 0]));
            downloadBlob(toCSV(rows), 'total-variabel.csv', 'text/csv');
          };
        }
      }

      // 4) Donut Sebaran OPD per Kategori
      const tierCounts = { 'Sangat Tinggi':0, 'Tinggi':0, 'Sedang':0, 'Rendah':0, 'Sangat Rendah':0 };
      lastOpdScores.forEach(r => {
        const k = String(r.kategori || '').toLowerCase();
        if (k === 'sangat tinggi')      tierCounts['Sangat Tinggi']++;
        else if (k === 'tinggi')        tierCounts['Tinggi']++;
        else if (k === 'sedang')        tierCounts['Sedang']++;
        else if (k === 'rendah')        tierCounts['Rendah']++;
        else if (k === 'sangat rendah') tierCounts['Sangat Rendah']++;
      });
      const tierData = [
        { name:'Sangat Tinggi', value: tierCounts['Sangat Tinggi'] },
        { name:'Tinggi',        value: tierCounts['Tinggi'] },
        { name:'Sedang',        value: tierCounts['Sedang'] },
        { name:'Rendah',        value: tierCounts['Rendah'] },
        { name:'Sangat Rendah', value: tierCounts['Sangat Rendah'] },
      ];
      ecTier = renderPie('chart-opd-tier', {
        color: ['#0d6efd','#198754','#ffc107','#fd7e14','#dc3545'],
        tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
        legend: { bottom: 0, data: tierData.map(d=>d.name) },
        series: [{ type:'pie', radius:['55%','75%'], center:['50%','45%'], label:{formatter:'{b}\n{c}'}, data: tierData }]
      });
      document.getElementById('btn-png-tier').onclick = () => exportPNGFromEcharts(ecTier, 'sebaran-opd-kategori');
      document.getElementById('btn-csv-tier').onclick = () => {
        const rows = [['Kategori','Jumlah']].concat(tierData.map(d => [d.name, d.value]));
        downloadBlob(toCSV(rows), 'sebaran-opd-kategori.csv', 'text/csv');
      };
    })
    .catch(console.error);

  // ====== Export TABEL: PNG + CSV ======
  // Nilai per OPD
  document.getElementById('btn-png-opd').onclick = () => exportDomPNG(document.getElementById('wrap-opd-table'), 'nilai-per-opd');
  document.getElementById('btn-csv-opd').onclick = () => {
    const rows = [['#','OPD','Poin','Kategori']];
    lastOpdScores.forEach((r,i)=> rows.push([i+1, r.nama ?? '', Number(r.poin ?? 0), r.kategori ?? '-']));
    downloadBlob(toCSV(rows), 'nilai-per-opd.csv', 'text/csv');
  };

  // Tracking OPD × Variabel
  document.getElementById('btn-png-tracking').onclick = () => exportDomPNG(document.getElementById('wrap-tracking-table'), 'tracking-opd-variabel');
  document.getElementById('btn-csv-tracking').onclick = () => {
    const head1 = ['#','OPD'].concat(lastVariabels.map(v=>'V'+v.id)).concat(['Total']);
    const head2 = ['',''].concat(lastVariabels.map(v=>v.nama)).concat(['']);
    const rows = [head1, head2];
    lastTracking.forEach((r,i) => {
      const cells = [i+1, r.nama ?? ''];
      lastVariabels.forEach(v => cells.push(statusText((r.status||{})[v.id] || null)));
      cells.push(Number(r.total ?? 0));
      rows.push(cells);
    });
    downloadBlob(toCSV(rows), 'tracking-opd-variabel.csv', 'text/csv');
  };
})();
</script>
@endpush

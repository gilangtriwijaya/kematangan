@php
    use App\Models\KegiatanPenilaian;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();

    // normalize role: "SUPER ADMIN" / "super_admin" / "super-admin" -> "superadmin"
    $roleRaw = (string) ($user?->role ?? 'opd');
    $role = strtolower(trim($roleRaw));
    $role = str_replace([' ', '_', '-'], '', $role);

    // Map common SSO role slugs to canonical local roles used by the app
    $roleCanon = $role; // default
    if (in_array($role, ['superadmin'], true)) {
      $roleCanon = 'superadmin';
    } elseif (in_array($role, ['bagoradmin', 'bagor-admin', 'bagianorganisasi', 'orgadmin', 'org-admin', 'admin-opd', 'adminopd'], true)) {
      $roleCanon = 'admin';
    } elseif (in_array($role, ['verifikatorglobal', 'verifikator-global', 'verifikator'], true)) {
      $roleCanon = 'verifikator';
    } elseif (str_contains($role, 'opd') || $role === 'opd') {
      $roleCanon = 'opd';
    }

    // Use canonical role for subsequent menu checks
    $role = $roleCanon;

    $originalAdminId = session('original_admin_id');

    // kegiatan aktif untuk menu OPD/Verifikator (penilaian-kematangan.* butuh {kegiatan})
    $kegiatanAktif = KegiatanPenilaian::where('is_aktif', 1)->orderByDesc('tahun')->first()
                  ?? KegiatanPenilaian::orderByDesc('tahun')->first();

    $has = fn(string $name) => Route::has($name);
    $is  = fn(string $pattern) => request()->routeIs($pattern);
    $active = fn(string $pattern) => $is($pattern) ? 'active' : '';
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

{{-- Impersonate banner (kalau route-nya memang ada di project ini) --}}
@if ($originalAdminId && $has('impersonate.stop'))
  <div class="alert alert-warning p-2 text-sm">
    Anda sedang menyamar sebagai user lain.
    <form action="{{ route('impersonate.stop') }}" method="POST" class="d-inline">
      @csrf
      <button type="submit" class="btn btn-sm btn-outline-dark ms-2">
        Kembali sebagai Super Admin
      </button>
    </form>
  </div>
@endif

<nav class="sidebar-nav d-flex flex-column gap-2">

  {{-- ===================== ADMIN / SUPERADMIN ===================== --}}
  @if (in_array($role, ['superadmin','admin'], true))

    @if ($has('dashboard'))
      <a href="{{ route('dashboard') }}"
         class="d-flex align-items-center {{ $active('dashboard') }}">
        <i class="bi bi-house me-2"></i> Dashboard
      </a>
    @endif

    {{-- Kegiatan Penilaian (name: kegiatan.*) --}}
    @if ($has('kegiatan.index'))
      <a href="{{ route('kegiatan.index') }}"
         class="d-flex align-items-center {{ $active('kegiatan.*') }}">
        <i class="bi bi-clipboard-data me-2"></i> Kegiatan Penilaian
      </a>
    @endif

    {{-- Pengelolaan Penilaian (routes TANPA param kegiatan) --}}
    <div class="dropdown">
      <a class="d-flex align-items-center dropdown-toggle {{ $active('variabel.*') ?: ($active('tingkat.*') ?: $active('indikator.*')) }}"
         href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear-wide-connected me-2"></i> Pengelolaan Penilaian
      </a>

      <ul class="dropdown-menu ps-2">
        @if ($has('variabel.index'))
          <li>
            <a class="dropdown-item" href="{{ route('variabel.index') }}">
              <i class="bi bi-list-check me-2"></i> Variabel Penilaian
            </a>
          </li>
        @endif

        @if ($has('tingkat.index'))
          <li>
            <a class="dropdown-item" href="{{ route('tingkat.index') }}">
              <i class="bi bi-bar-chart me-2"></i> Tingkat Penilaian
            </a>
          </li>
        @endif

        @if ($has('indikator.index'))
          <li>
            <a class="dropdown-item" href="{{ route('indikator.index') }}">
              <i class="bi bi-sliders2 me-2"></i> Indikator Penilaian
            </a>
          </li>
        @endif

        {{-- indikator-dokumen.* tidak kamu taruh di menu; kalau mau, tinggal tambah di sini --}}
      </ul>
    </div>

    @if ($has('verifikasi.index'))
      <a href="{{ route('verifikasi.index') }}"
         class="d-flex align-items-center {{ $active('verifikasi.*') }}">
        <i class="bi bi-shield-check me-2"></i> Verifikasi Penilaian
      </a>
    @endif

    @if ($has('bukti-dukung.index'))
      <a href="{{ route('bukti-dukung.index') }}"
         class="d-flex align-items-center {{ $active('bukti-dukung.*') }}">
        <i class="bi bi-download me-2"></i> Export
      </a>
    @endif

    @if ($role === 'superadmin' && $has('log.index'))
      <a href="{{ route('log.index') }}"
         class="d-flex align-items-center {{ $active('log.*') }}">
        <i class="bi bi-journal-text me-2"></i> Aktivitas & Log
      </a>
    @endif

    {{-- NOTE: users.* memang tidak ada di Kematangan (route:list kamu tidak punya), jadi JANGAN dipanggil --}}
  @endif


  {{-- ===================== OPD ===================== --}}
  @if ($role === 'opd')

    @if ($has('dashboard'))
      <a href="{{ route('dashboard') }}"
         class="d-flex align-items-center {{ $active('dashboard') }}">
        <i class="bi bi-house me-2"></i> Dashboard
      </a>
    @endif

    @if ($kegiatanAktif && $has('penilaian-kematangan.index'))
      <a href="{{ route('penilaian-kematangan.index', ['kegiatan' => $kegiatanAktif->slug ?? $kegiatanAktif->id]) }}"
         class="d-flex align-items-center {{ $active('penilaian-kematangan.*') }}">
        <i class="bi bi-file-earmark-bar-graph me-2"></i> Penilaian Kematangan
      </a>
    @else
      <span class="text-muted d-flex align-items-center">
        <i class="bi bi-file-earmark-bar-graph me-2"></i> Penilaian Kematangan (belum ada kegiatan)
      </span>
    @endif

  @endif


  {{-- ===================== VERIFIKATOR ===================== --}}
  @if ($role === 'verifikator')

    @if ($has('dashboard'))
      <a href="{{ route('dashboard') }}"
         class="d-flex align-items-center {{ $active('dashboard') }}">
        <i class="bi bi-house me-2"></i> Dashboard
      </a>
    @endif

    @if ($has('penilaian-kematangan.active'))
      <a href="{{ route('penilaian-kematangan.active') }}"
         class="d-flex align-items-center {{ $active('penilaian-kematangan.*') }}">
        <i class="bi bi-eye me-2"></i> Penilaian Kematangan
      </a>
    @endif

  @endif

</nav>

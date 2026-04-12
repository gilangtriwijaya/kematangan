<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - @yield('title')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { background-color: #f7f9fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .sidebar { height: 100vh; background-color: #003366; color: #fff; padding: 20px; position: fixed; width: 250px; }
    .sidebar a { color: #fff; display: block; padding: 10px 0; text-decoration: none; font-weight: 500; }
    .sidebar a:hover { background-color: #00509e; padding-left: 10px; }
    .sidebar .logo { width: 50px; margin-bottom: 10px; }
    .content-wrapper { margin-left: 250px; padding: 20px; }
    .topbar { background-color: #fff; border-bottom: 1px solid #ddd; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
    .sidebar .dropdown-menu { background-color: #ffffff; border: none; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); padding: 8px 0; min-width: 220px; }
    .sidebar .dropdown-item { color: #0d1b2a; padding: 8px 16px; font-size: 14px; border-radius: 6px; transition: background 0.2s; }
    .sidebar .dropdown-item:hover { background-color: #f1f5f9; color: #000; }
    @media (max-width: 768px) { .sidebar { position: relative; width: 100%; height: auto; } .content-wrapper { margin-left: 0; } }
      /* Badge oranye khusus untuk kategori 'Rendah' */
  .badge-orange{
    background:#fd7e14 !important; /* oranye */
    color:#fff !important;
    border:0 !important;
    padding:.35em .65em;      /* samakan dengan badge default */
    font-weight:600;
    border-radius:.375rem;    /* biar bentuknya tetap pill */
  }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="text-center mb-4">
      <div class="d-flex justify-content-center align-items-center gap-4">
        <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda" style="height:60px; object-fit:contain;">
        <img src="{{ asset('images/logo-ortal1.png') }}" alt="Logo OrtaL" style="height:60px; object-fit:contain;">
      </div>
      <h5 class="mt-3 text-white text-center" style="font-size: 14px; line-height: 1.4;">
        Pemerintah Daerah<br>Kabupaten Kepulauan Anambas
      </h5>
    </div>

    @include('layouts.sidebar-menu')
  </div>

  <div class="content-wrapper">
    <div class="topbar">
      <div class="d-flex align-items-center gap-3">
        <div>Halo, {{ auth()->user()->name }} ({{ strtoupper(auth()->user()->role) }})</div>
        @include('layouts.sso-scope')
      </div>
      <form action="{{ route('sso.back') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-primary">
          Kembali ke SSO
        </button>
      </form>

    </div>

    <div class="py-4">
      @yield('content')
    </div>
  </div>

  {{-- Vendor JS harus lebih dulu --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js" defer></script>

  {{-- Script halaman (dari @push) dieksekusi setelah lib di atas siap --}}
  @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Masuk | SISTAGOR</title>

  <!-- Bootstrap 5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

  <style>
    /* ========= Tokens ========= */
    :root{
      --brand-700:#155ab6;         /* utama */
      --brand-500:#1e88e5;         /* aksen */
      --text-900:#0f172a;          /* heading */
      --text-700:#374151;          /* body */
      --muted:#667085;             /* helper */
      --border:#e6e9ef;            /* garis halus */
      --surface:#ffffff;           /* kartu */
      --page:#ffffff;              /* latar aman putih */
      --radius-2xl:22px;
      --radius-xl:18px;
      --shadow-card:0 10px 28px rgba(15,23,42,.07);
      --shadow-hover:0 16px 44px rgba(15,23,42,.12);
    }

    /* ========= Page ========= */
    body{
      background: var(--page);
      min-height:100svh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px 16px;
      color: var(--text-700);
    }

    .auth-shell{ width:100%; max-width: 980px; }

    /* ========= Composite Card ========= */
    .auth-card{
      display:grid;
      grid-template-columns: 1fr;
      background: var(--surface);
      border:1px solid var(--border);
      border-radius: var(--radius-2xl);
      overflow:hidden;
      box-shadow: var(--shadow-card);
      transition: box-shadow .25s ease;
    }
    .auth-card:hover{ box-shadow: var(--shadow-hover); }

    /* Left ribbon (brand) */
    .ribbon{
      background:
        linear-gradient(135deg, rgba(255,255,255,.12), rgba(255,255,255,0)) 0 0/100% 100% no-repeat,
        linear-gradient(135deg, var(--brand-700), var(--brand-500));
      color:#fff;
      padding:28px 24px;
      position: relative;
      isolation:isolate;
    }
    /* pola garis halus */
    .ribbon::after{
      content:"";
      position:absolute; inset:0;
      background-image:
        repeating-linear-gradient(
          -25deg,
          rgba(255,255,255,.12) 0, rgba(255,255,255,.12) 1px,
          transparent 1px, transparent 10px
        );
      opacity:.18;
      mix-blend-mode: overlay;
      pointer-events:none;
    }

    .brand-row{
      display:flex; gap:14px; align-items:center; margin-bottom:12px;
    }
    .brand-row img{
      width:56px; height:56px; object-fit:contain; flex:0 0 auto;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.25));
    }
    .brand-title{ margin:0; font-size:1.55rem; font-weight:800; letter-spacing:.2px; line-height:1.15; }
    .brand-sub{ margin:0; opacity:.98; }

    .benefit{
      margin:16px 0 0 0; padding:0; list-style:none;
    }
    .benefit li{
      display:flex; gap:10px; align-items:flex-start; margin:.5rem 0;
    }
    .benefit .dot{
      width:.55rem; height:.55rem; border-radius:99px; flex:0 0 auto;
      background: radial-gradient(circle at 30% 30%, #b8e7ff, #7dd3fc);
      margin-top:.5rem;
    }

    /* Little corner cut (unik) */
    .corner-cut{
      position:absolute;
      right:-1px; top:-1px;
      width:86px; height:86px;
      background: var(--surface);
      clip-path: polygon(0 0, 100% 0, 100% 100%);
      border-left:1px solid var(--border);
      border-bottom:1px solid var(--border);
      opacity:.9;
    }

    /* Right content (form) */
    .content{
      padding:28px;
    }
    .content h2{
      margin:0 0 6px 0; color:var(--text-900); font-weight:800;
    }
    .content .desc{ color:var(--muted); margin:0 0 18px 0; }

    .form-floating>.form-control{ padding:1.05rem .95rem; height:auto; }
    .form-floating>label{ color:#6b7280; }

    .form-control{
      border-color: var(--border);
      border-radius: 14px;
      background:#fff;
      color: var(--text-700);
    }
    .form-control:focus{
      border-color: rgba(30,136,229,.35);
      box-shadow: 0 0 0 .25rem rgba(30,136,229,.15);
    }

    .input-icon{ position:relative; }
    .input-icon .bi{
      position:absolute; left:12px; top:50%; transform: translateY(-50%);
      color:#98a2b3; pointer-events:none;
    }
    .input-icon input{ padding-left:2.2rem !important; }

    .toggle-pass{
      position:absolute; right:10px; top:50%; transform: translateY(-50%);
      border:0; background:transparent; padding:6px; color:#667085;
    }
    .toggle-pass:focus{ outline:2px solid #93c5fd; border-radius:8px; }

    .btn-brand{
      background: var(--text-900);
      border-color: var(--text-900);
      border-radius:14px;
      font-weight:700;
      padding:.9rem 1rem;
      color:#fff;
    }
    .btn-brand:hover{ filter:brightness(1.05); }

    .footnote{ color:#7b8a9c; font-size:.9rem; text-align:center; }

    /* Grid for wide screens */
    @media (min-width: 992px){
      .auth-card{
        grid-template-columns: 0.42fr 0.58fr; /* pita kiri 42%, form kanan 58% */
      }
      .ribbon{ padding:32px 28px; }
      .content{ padding:32px; }
    }

    /* Mobile: ribbon jadi strip atas */
    @media (max-width: 991.98px){
      .corner-cut{ display:none; }
    }
  </style>
</head>
<body>
  <main class="auth-shell" role="main">
    <section class="auth-card">
      <!-- Left brand ribbon -->
      <aside class="ribbon">
        <div class="corner-cut" aria-hidden="true"></div>

        <div class="brand-row">
          <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda" />
          <div>
            <h1 class="brand-title">SISTAGOR</h1>
            <div class="brand-sub small">Kabupaten Kepulauan Anambas</div>
          </div>
        </div>

        <p class="mb-2">Sistem Informasi Penataan Organisasi</p>
        <ul class="benefit">
          <li><span class="dot"></span><span>Kontrol akses terukur (Role &amp; Permission).</span></li>
          <li><span class="dot"></span><span>Realtime update progres dan rekapitulasi.</span></li>
          <li><span class="dot"></span><span>Audit trail aktivitas pengguna.</span></li>
        </ul>
      </aside>

      <!-- Right content (form) -->
      <div class="content">
        <h2>Masuk</h2>
        <p class="desc">Gunakan akun dinas Anda untuk mengakses sistem.</p>

        {{-- Flash --}}
        @if(session('success'))
          <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="needs-validation" novalidate id="loginForm">
          @csrf

          <!-- Email -->
          <div class="form-floating mb-3 input-icon">
            <i class></i>
            <input
              type="email"
              class="form-control @error('email') is-invalid @enderror"
              id="email"
              name="email"
              value="{{ old('email') }}"
              placeholder="nama@anambaskab.go.id"
              required
              autocomplete="username"
              inputmode="email"
              aria-describedby="emailHelp"
              autofocus
            />
            <label for="email">Email</label>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <!-- Password -->
          <div class="form-floating mb-3 position-relative input-icon">
            <i class></i>
            <input
              type="password"
              class="form-control @error('password') is-invalid @enderror"
              id="password"
              name="password"
              placeholder="••••••••"
              required
              autocomplete="current-password"
              minlength="6"
              aria-describedby="passwordHelp"
            />
            <label for="password">Kata sandi</label>

            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
                <button class="toggle-pass" type="button" aria-label="Tampilkan/sembunyikan sandi" title="Lihat sandi">
                  <i class="bi bi-eye-slash" id="eyeIcon"></i>
                </button>

          <!-- Remember + link -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" />
              <label class="form-check-label" for="remember">Ingat saya</label>
            </div>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa kata sandi?</a>
            @endif
          </div>

          <button type="submit" class="btn btn-brand w-100" id="submitBtn">Masuk</button>
        </form>

        <div class="mt-4">
          <p class="text-muted mb-0 small">
            © {{ date('Y') }} — Bagian Organisasi Sekretariat Daerah Kabupaten Kepulauan Anambas.
          </p>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Validasi + cegah double submit
    (function () {
      'use strict';
      const form = document.getElementById('loginForm');
      const submitBtn = document.getElementById('submitBtn');

      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        } else {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
        }
        form.classList.add('was-validated');
      }, false);
    })();

    // Toggle password visibility
    (function () {
      const toggle = document.querySelector('.toggle-pass');
      const pwd = document.getElementById('password');
      const eye = document.getElementById('eyeIcon');
      toggle.addEventListener('click', function(){
        const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
        pwd.setAttribute('type', type);
        eye.classList.toggle('bi-eye');
        eye.classList.toggle('bi-eye-slash');
      });
    })();
  </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>APDA RSPI | Login</title>
  <link rel="icon" href="../assets/img/QQ.jpg" type="image/x-icon">
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
  <style>
    :root {
      --login-ink: #172033;
      --login-muted: #667085;
      --login-primary: #0f766e;
      --login-accent: #2563eb;
      --login-border: rgba(226, 232, 240, .9);
    }

    body.login-page {
      min-height: 100vh;
      background:
        linear-gradient(120deg, rgba(15, 23, 42, .82), rgba(15, 118, 110, .72)),
        url("../assets/img/rs.jpeg") center/cover no-repeat;
      color: var(--login-ink);
      overflow-x: hidden;
    }

    .login-bg-video,
    .login-bg-overlay {
      position: fixed;
      inset: 0;
      pointer-events: none;
    }

    .login-bg-video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity .8s ease;
      z-index: -2;
    }

    .login-bg-video.is-ready {
      opacity: 1;
    }

    .login-bg-overlay {
      background:
        linear-gradient(120deg, rgba(15, 23, 42, .78), rgba(15, 118, 110, .7)),
        radial-gradient(circle at 82% 22%, rgba(45, 212, 191, .24), transparent 30%);
      z-index: -1;
    }

    .login-shell {
      width: min(1040px, calc(100% - 32px));
      min-height: 560px;
      display: grid;
      grid-template-columns: 1.04fr .96fr;
      background: rgba(255, 255, 255, .92);
      border: 1px solid rgba(255, 255, 255, .58);
      border-radius: 14px;
      box-shadow: 0 28px 70px rgba(15, 23, 42, .32);
      overflow: hidden;
      backdrop-filter: blur(14px);
    }

    .login-panel {
      padding: 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, .92), rgba(248, 250, 252, .96));
    }

    .brand-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 34px;
    }

    .brand-row img {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      object-fit: cover;
      box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
    }

    .brand-row strong {
      display: block;
      color: var(--login-ink);
      font-size: 1.1rem;
      line-height: 1.1;
    }

    .brand-row span {
      color: var(--login-muted);
      font-size: .9rem;
    }

    .login-title {
      font-size: 2.25rem;
      font-weight: 800;
      letter-spacing: 0;
      margin-bottom: .6rem;
    }

    .login-copy {
      color: var(--login-muted);
      margin-bottom: 28px;
      max-width: 420px;
    }

    .input-group {
      border: 1px solid var(--login-border);
      border-radius: 10px;
      background: #fff;
      overflow: hidden;
      transition: border .18s ease, box-shadow .18s ease;
    }

    .input-group:focus-within {
      border-color: var(--login-primary);
      box-shadow: 0 0 0 .2rem rgba(15, 118, 110, .12);
    }

    .input-group .form-control,
    .input-group-text {
      border: 0;
      min-height: 48px;
      background: #fff;
    }

    .input-group-text {
      color: var(--login-primary);
      width: 48px;
      justify-content: center;
    }

    .password-toggle {
      cursor: pointer;
    }

    .btn-login {
      min-height: 48px;
      border: 0;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--login-primary), var(--login-accent));
      color: #fff;
      font-weight: 800;
      box-shadow: 0 14px 28px rgba(15, 118, 110, .24);
      transition: transform .16s ease, box-shadow .16s ease;
    }

    .btn-login:hover {
      color: #fff;
      transform: translateY(-1px);
      box-shadow: 0 18px 34px rgba(37, 99, 235, .24);
    }

    .login-visual {
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 42px;
      color: #fff;
      background:
        linear-gradient(180deg, rgba(15, 23, 42, .1), rgba(15, 23, 42, .82)),
        url("../assets/img/latar_login.jpg") center/cover no-repeat;
    }

    .visual-logo-wrap {
      position: absolute;
      top: 155px;
      right: 48px;
      left: 48px;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 150px;
      pointer-events: none;
    }

    .visual-logo {
      width: min(360px, 82%);
      max-height: 150px;
      object-fit: contain;
      opacity: .9;
      filter: drop-shadow(0 18px 28px rgba(0, 0, 0, .32));
      animation: logoFloat 5.5s ease-in-out infinite;
    }

    @keyframes logoFloat {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-7px);
      }
    }

    .visual-card {
      border: 1px solid rgba(255, 255, 255, .24);
      border-radius: 12px;
      padding: 20px;
      background: rgba(15, 23, 42, .46);
      backdrop-filter: blur(10px);
    }

    .visual-card h2 {
      font-size: 1.4rem;
      font-weight: 800;
      margin-bottom: .5rem;
    }

    .visual-card p {
      color: rgba(255, 255, 255, .82);
      margin-bottom: 0;
    }

    .login-footnote {
      color: var(--login-muted);
      font-size: .85rem;
      margin-top: 28px;
    }

    @media (max-width: 860px) {
      .login-shell {
        grid-template-columns: 1fr;
        min-height: auto;
      }

      .login-visual {
        display: none;
      }

      .login-panel {
        padding: 34px 24px;
      }

      .login-title {
        font-size: 1.85rem;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .login-bg-video {
        display: none;
      }

      .visual-logo {
        animation: none;
      }
    }
  </style>
</head>
<body class="hold-transition login-page">
  <video class="login-bg-video" autoplay muted loop playsinline preload="metadata" poster="../assets/img/rs.jpeg" aria-hidden="true">
    <source src="../assets/video/login-bg.mp4" type="video/mp4">
    <source src="../assets/video/login-bg.webm" type="video/webm">
  </video>
  <div class="login-bg-overlay" aria-hidden="true"></div>

  <main class="login-shell">
    <section class="login-panel">
      <div>
        <div class="brand-row">
          <img src="../assets/img/QQ.jpg" alt="Logo RSPI">
          <div>
            <strong>APDA RSPI</strong>
            <span>Penomoran Surat Akreditasi</span>
          </div>
        </div>

        <h1 class="login-title">Masuk ke sistem</h1>
        <p class="login-copy">Kelola pengajuan, verifikasi, nomor dokumen, dan pengesahan dalam satu dashboard kerja.</p>

        <form action="form_login_action.php" method="post" autocomplete="on">
          <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Username" name="username" autofocus required>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-user"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input class="form-control" id="pswrd" placeholder="Password" name="password" type="password" required>
            <div class="input-group-append">
              <button class="input-group-text password-toggle" type="button" onclick="showPassword()" aria-label="Tampilkan password">
                <span id="passwordIcon" class="fas fa-eye"></span>
              </button>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">Ingat saya</label>
            </div>
          </div>

          <button type="submit" class="btn btn-login btn-block">
            <i class="fas fa-sign-in-alt mr-2"></i>Masuk
          </button>
        </form>
      </div>

      <div class="login-footnote">
        &copy; <?= date('Y') ?> IT-RSPI. Aplikasi internal Rumah Sakit Pelita Insani.
      </div>
    </section>

    <section class="login-visual">
      <div class="visual-logo-wrap">
        <img class="visual-logo" src="../assets/img/logo-rspi-transparent.png" alt="Rumah Sakit Pelita Insani">
      </div>
      <div class="visual-card">
        <h2>Dokumen rapi, nomor terkendali.</h2>
        <p>Alur pengajuan Pokja, verifikasi admin, dan arsip dokumen sah dibuat lebih mudah dipantau.</p>
      </div>
    </section>
  </main>

  <script src="../assets/plugins/jquery/jquery.min.js"></script>
  <script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/dist/js/adminlte.min.js"></script>
  <script>
    var bgVideo = document.querySelector('.login-bg-video');
    if (bgVideo) {
      bgVideo.addEventListener('canplay', function() {
        bgVideo.classList.add('is-ready');
      });

      bgVideo.addEventListener('error', function() {
        bgVideo.style.display = 'none';
      });
    }

    function showPassword() {
      var passwordInput = document.getElementById('pswrd');
      var icon = document.getElementById('passwordIcon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>

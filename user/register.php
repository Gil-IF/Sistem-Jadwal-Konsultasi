<?php
require_once '../config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/user/index.php');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password || !$phone) {
        setFlash('error', 'Nama, email, password, dan nomor telpon wajib diisi.');
        redirect(BASE_URL . '/user/register.php');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Format email tidak valid.');
        redirect(BASE_URL . '/user/register.php');
    } elseif (strlen($password) < 6) {
        setFlash('error', 'Password minimal 6 karakter.');
        redirect(BASE_URL . '/user/register.php');
    } elseif ($password !== $confirm) {
        setFlash('error', 'Konfirmasi password tidak cocok.');
        redirect(BASE_URL . '/user/register.php');
    } elseif (!ctype_digit($phone)) {
        setFlash('error', 'Nomor telepon hanya boleh berisi angka.');
        redirect(BASE_URL . '/user/register.php'); 
    } elseif (!preg_match('/^[0-9]{10,13}$/', $phone)){ 
        setFlash('error', 'panjang nomor telpon tidak sesuai.');
        redirect(BASE_URL . '/user/register.php');
    } else {
        $check = $pdo->prepare("SELECT id FROM patients WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            setFlash('error', 'Email sudah terdaftar.');
            redirect(BASE_URL . '/user/register.php');
        } else {
            $pdo->prepare(
                "INSERT INTO patients (full_name, email, phone, password_hash) VALUES (?,?,?,?)"
            )->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            $success = true;
        }
    }
}

$flash = getFlash();
$error = ($flash && $flash['type'] === 'error') ? $flash['msg'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar – Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
    <link rel="icon" type="image/png" href="../src/img/logo.png">

</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Kiri -->
        <div class="auth-left">
            <img src="<?= BASE_URL ?>/src/img/Hospital.png" alt="Logo">
            <h5>Konsul, yuk!</h5>
            <p>Daftar dan mulai kelola<br>jadwal konsultasimu</p>
        </div>

        <!-- Kanan -->
        <div class="auth-right">
            <?php if ($success): ?>
                <div class="text-center py-4">
                    <div style="font-size:3rem; color:#22c55e;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h5 class="fw-bold mt-3">Registrasi Berhasil!</h5>
                    <p class="text-muted small mb-4">Akun kamu sudah siap digunakan.</p>
                    <a href="<?= BASE_URL ?>/Login.php" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login Sekarang
                    </a>
                </div>
            <?php else: ?>
                <h5 class="fw-bold mb-1" id="stepTitle">Informasi Diri</h5>
                <p class="text-muted small mb-3" id="stepSubtitle">Langkah 1 dari 3</p>

                <!-- Progress bar -->
                <div class="progress mb-4" style="height:4px; border-radius:2px;">
                    <div class="progress-bar" id="progressBar"
                        style="width:33%; background:#3b69ff; transition: width 0.4s ease;"></div>
                </div>

                <form method="post" novalidate id="registerForm">

                    <!-- Step 1: Info Diri -->
                    <div class="step" id="step1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="full_name" id="inp_name" class="form-control"
                                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                    placeholder="Nama lengkap kamu">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">No. HP</label> <span class="text-danger">*</span>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" name="phone" id="inp_phone" class="form-control"
                                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                    placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary w-100 py-2 fw-semibold" onclick="goStep(2)">
                            Lanjut <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                    <!-- Step 2: Akun -->
                    <div class="step d-none" id="step2">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="inp_email" class="form-control"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    placeholder="email@kamu.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="pwdInput" class="form-control"
                                    placeholder="Min. 6 karakter" minlength="6" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('pwdInput','eyeIcon1')">
                                    <i class="bi bi-eye" id="eyeIcon1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" id="pwdInput2" class="form-control"
                                    placeholder="Ulangi password" minlength="6" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwd('pwdInput2','eyeIcon2')">
                                    <i class="bi bi-eye" id="eyeIcon2"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary py-2 fw-semibold" style="width:35%"
                                    onclick="goStep(1)">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="button" class="btn btn-primary py-2 fw-semibold" style="width:65%"
                                    onclick="goStep(3)">
                                Lanjut <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Konfirmasi -->
                    <div class="step d-none" id="step3">
                        <p class="text-muted small mb-3">Pastikan datamu sudah benar sebelum mendaftar.</p>
                        <div class="bg-light rounded p-3 mb-4" style="font-size:.9rem;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Nama</span>
                                <span class="fw-semibold" id="conf_name">–</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">No. HP</span>
                                <span class="fw-semibold" id="conf_phone">–</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Email</span>
                                <span class="fw-semibold" id="conf_email">–</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary py-2 fw-semibold" style="width:35%"
                                    onclick="goStep(2)">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </button>
                            <button type="submit" class="btn btn-primary py-2 fw-semibold" style="width:65%">
                                <i class="bi bi-person-plus me-1"></i> Daftar Sekarang
                            </button>
                        </div>
                    </div>

                </form>

                <hr class="my-3">
                <p class="text-center text-muted small mb-0">
                    Sudah punya akun?
                    <a href="<?= BASE_URL ?>/Login.php" class="text-decoration-none">Login di sini</a>
                </p>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const steps = {
    1: { title: 'Informasi Diri',  subtitle: 'Langkah 1 dari 3', progress: '33%'  },
    2: { title: 'Buat Akun',       subtitle: 'Langkah 2 dari 3', progress: '66%'  },
    3: { title: 'Konfirmasi Data', subtitle: 'Langkah 3 dari 3', progress: '100%' },
};

function goStep(n) {
    // Validasi sebelum lanjut
    if (n === 2) {
        const name = document.getElementById('inp_name').value.trim();
        if (!name) { showErrorPopup('Nama lengkap wajib diisi.'); return; }

        const number = document.getElementById('inp_phone').value.trim();
        if (!number) { showErrorPopup('Nomor telpon wajib diisi'); return;}
        
    }
    if (n === 3) {
        const email = document.getElementById('inp_email').value.trim();
        const pwd   = document.getElementById('pwdInput').value;
        const pwd2  = document.getElementById('pwdInput2').value;
        if (!email) { showErrorPopup('Email wajib diisi.'); return; }
        if (pwd.length < 6) { showErrorPopup('Password minimal 6 karakter.'); return; }
        if (pwd !== pwd2)   { showErrorPopup('Konfirmasi password tidak cocok.'); return; }

        // Isi data konfirmasi
        document.getElementById('conf_name').textContent  = document.getElementById('inp_name').value || '-';
        document.getElementById('conf_phone').textContent = document.getElementById('inp_phone').value || '-';
        document.getElementById('conf_email').textContent = email;
    }

    // Animasi slide
    const current = document.querySelector('.step:not(.d-none)');
    const next    = document.getElementById('step' + n);
    const goingForward = n > getCurrentStep();

    current.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
    current.style.transform  = goingForward ? 'translateX(-60px)' : 'translateX(60px)';
    current.style.opacity    = '0';

    setTimeout(() => {
        current.classList.add('d-none');
        current.style.transform = '';
        current.style.opacity   = '';

        next.classList.remove('d-none');
        next.style.transform  = goingForward ? 'translateX(60px)' : 'translateX(-60px)';
        next.style.opacity    = '0';
        next.style.transition = 'none';

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                next.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                next.style.transform  = 'translateX(0)';
                next.style.opacity    = '1';
            });
        });
    }, 280);

    // Update header & progress
    document.getElementById('stepTitle').textContent    = steps[n].title;
    document.getElementById('stepSubtitle').textContent = steps[n].subtitle;
    document.getElementById('progressBar').style.width  = steps[n].progress;
}

function getCurrentStep() {
    for (let i = 1; i <= 3; i++) {
        if (!document.getElementById('step' + i).classList.contains('d-none')) return i;
    }
    return 1;
}

function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type      = input.type === 'password' ? 'text' : 'password';
    icon.className  = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

<?php if ($error): ?>
document.addEventListener('DOMContentLoaded', () => showErrorPopup('<?= htmlspecialchars($error, ENT_QUOTES) ?>'));
<?php endif; ?>

function showErrorPopup(msg) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,0);display:flex;
        align-items:center;justify-content:center;z-index:9999;transition:background 0.3s ease;`;
    overlay.innerHTML = `
        <div id="popupBox" style="background:#fff;border-radius:1rem;padding:2rem;max-width:340px;
            width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);
            transform:scale(0.7) translateY(30px);opacity:0;
            transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1),opacity 0.3s ease;">
            <div style="font-size:2.5rem;margin-bottom:1rem;">⚠️</div>
            <h6 style="font-weight:700;margin-bottom:.5rem;">Oops!</h6>
            <p style="color:#64748b;font-size:.9rem;margin-bottom:1.5rem;">${msg}</p>
            <button id="popupBtn" style="background:#3b69ff;color:#fff;border:none;border-radius:.5rem;
                padding:.6rem 2rem;font-weight:600;cursor:pointer;width:100%;">OK</button>
        </div>`;
    document.body.appendChild(overlay);

    function closePopup() {
        const box = document.getElementById('popupBox');
        overlay.style.background = 'rgba(0,0,0,0)';
        box.style.transform = 'scale(0.7) translateY(30px)';
        box.style.opacity = '0';
        setTimeout(() => overlay.remove(), 300);
    }

    requestAnimationFrame(() => {
        overlay.style.background = 'rgba(0,0,0,0.4)';
        requestAnimationFrame(() => {
            const box = document.getElementById('popupBox');
            box.style.transform = 'scale(1) translateY(0)';
            box.style.opacity = '1';
        });
    });

    document.getElementById('popupBtn').addEventListener('click', closePopup);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closePopup(); });
}
</script>
</body>
</html>
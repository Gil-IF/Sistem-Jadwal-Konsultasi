<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/index.php'));
}

$flash = getFlash();
$error = ($flash && $flash['type'] === 'error')
    ? $flash['msg']
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($identity && $password) {
        $stmt = $pdo->prepare(
            "SELECT id, full_name, password_hash FROM admins
             WHERE (username = :i OR email = :i2) AND is_active = 1 LIMIT 1"
        );
        $stmt->execute(['i' => $identity, 'i2' => $identity]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $admin['id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['role']      = 'admin';
            redirect(BASE_URL . '/admin/index.php');
        }

        $stmt2 = $pdo->prepare(
            "SELECT id, full_name, password_hash FROM patients WHERE email = :email LIMIT 1"
        );
        $stmt2->execute(['email' => $identity]);
        $patient = $stmt2->fetch();

        if ($patient && password_verify($password, $patient['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $patient['id'];
            $_SESSION['full_name'] = $patient['full_name'];
            $_SESSION['role']      = 'patient';
            redirect(BASE_URL . '/user/index.php');
        }

        setFlash('error', 'Email/username atau password salah.');
        redirect(BASE_URL . '/login.php');
    } else {
        setFlash('error', 'Harap isi email/username dan password.');
        redirect(BASE_URL . '/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login – Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
    <link rel="icon" type="image/png" href="./src/img/logo.png">
</head>

<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-left">
            <img src="./src/img/Hospital.png" alt="Logo">
            <h5>AntriSehat</h5>
            <p>Sistem Jadwal Konsultasi Dokter</p>
        </div>
        <div class="auth-right">
            <?php if ($error): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showErrorPopup('<?= htmlspecialchars($error, ENT_QUOTES) ?>');
                    });
                </script>
            <?php endif; ?>

            <h5 class="fw-bold mb-1">Selamat Datang</h5>
            <p class="text-muted small mb-4">Masuk ke akun kamu</p>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email / Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="identity" class="form-control"
                               placeholder="PemWeb@gmail.com"
                               value="<?= htmlspecialchars($_POST['identity'] ?? '') ?>"
                               autocomplete="username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pwdInput"
                               class="form-control" placeholder="••••••••"
                               autocomplete="current-password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>

            <hr class="my-3">
            <p class="text-center text-muted small mb-0">
                Belum punya akun?
                <a href="<?= BASE_URL ?>/user/register.php" class="text-decoration-none">Daftar sebagai Pasien</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePwd() {
        const input = document.getElementById('pwdInput');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    // Cursor glow effect
    const authLeft = document.querySelector('.auth-left');
    authLeft.addEventListener('mousemove', (e) => {
        const rect = authLeft.getBoundingClientRect();
        authLeft.style.setProperty('--x', (e.clientX - rect.left) + 'px');
        authLeft.style.setProperty('--y', (e.clientY - rect.top) + 'px');
    });
    function showErrorPopup(msg) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position:fixed; inset:0; background:rgba(0,0,0,0);
            display:flex; align-items:center; justify-content:center; z-index:9999;
            transition: background 0.3s ease;
        `;

        overlay.innerHTML = `
            <div id="popupBox" style="
                background:#fff; border-radius:1rem; padding:2rem; max-width:340px;
                width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.2);
                transform: scale(0.7) translateY(30px); opacity:0;
                transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
            ">
                <div style="font-size:2.5rem; margin-bottom:1rem;">⚠️</div>
                <h6 style="font-weight:700; margin-bottom:.5rem;">Login Gagal</h6>
                <p style="color:#64748b; font-size:.9rem; margin-bottom:1.5rem;">${msg}</p>
                <button id="popupBtn"
                    style="background:#3b69ff; color:#fff; border:none; border-radius:.5rem;
                        padding:.6rem 2rem; font-weight:600; cursor:pointer; width:100%;
                        transition: background 0.2s;">
                    OK
                </button>
            </div>
        `;

        document.body.appendChild(overlay);

        // Fungsi tutup dengan animasi keluar
        function closePopup() {
            const box = document.getElementById('popupBox');
            overlay.style.background = 'rgba(75, 10, 10, 0.3)';
            box.style.transform = 'scale(0.7) translateY(30px)';
            box.style.opacity = '0';
            setTimeout(() => overlay.remove(), 300);
        }

        // Animasi masuk
        requestAnimationFrame(() => {
            overlay.style.background = 'rgba(75, 10, 10, 0.3)';
            const box = document.getElementById('popupBox');
            requestAnimationFrame(() => {
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
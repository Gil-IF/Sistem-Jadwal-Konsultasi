<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/index.php'));
}

$error = '';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $identity = trim($_POST['identity'] ?? '');
//     $password  = $_POST['password'] ?? '';

//     if ($identity && $password) {
//         $stmt = $pdo->prepare(
//             "SELECT id, full_name, password_hash FROM admins
//              WHERE (username = :i OR email = :i2) AND is_active = 1 LIMIT 1"
//         );
//         $stmt->execute(['i' => $identity, 'i2' => $identity]);
//         $admin = $stmt->fetch();

//         if ($admin && password_verify($password, $admin['password_hash'])) {
//             session_regenerate_id(true);
//             $_SESSION['user_id']   = $admin['id'];
//             $_SESSION['full_name'] = $admin['full_name'];
//             $_SESSION['role']      = 'admin';
//             redirect(BASE_URL . '/admin/index.php');
//         }

//         $stmt2 = $pdo->prepare(
//             "SELECT id, full_name, password_hash FROM patients WHERE email = :email LIMIT 1"
//         );
//         $stmt2->execute(['email' => $identity]);
//         $patient = $stmt2->fetch();

//         if ($patient && password_verify($password, $patient['password_hash'])) {
//             session_regenerate_id(true);
//             $_SESSION['user_id']   = $patient['id'];
//             $_SESSION['full_name'] = $patient['full_name'];
//             $_SESSION['role']      = 'patient';
//             redirect(BASE_URL . '/user/index.php');
//         }

//         $error = 'Email/username atau password salah.';
//     } else {
//         $error = 'Harap isi email/username dan password.';
//     }
// }
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
</head>

<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-left">
            <img src="./src/img/Hospital.png" alt="Logo">
            <h5>Konsul, yuk!</h5>
            <p>Sistem Jadwal Konsultasi</p>
        </div>
        <div class="auth-right">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
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
</script>
</body>
</html>
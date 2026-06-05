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

    if (!$name || !$email || !$password) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek email duplikat
        $check = $pdo->prepare("SELECT id FROM patients WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $pdo->prepare(
                "INSERT INTO patients (full_name, email, phone, password_hash) VALUES (?,?,?,?)"
            )->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Registrasi berhasil! Silakan login.';
        }
    }
}
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
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card card p-4 p-md-5" style="max-width:460px">
        <div class="text-center mb-4">
            <div class="auth-logo mb-2">🏥</div>
            <h4 class="fw-bold mb-0">Daftar Akun Pasien</h4>
            <p class="text-muted small">Klinik – Sistem Jadwal Konsultasi</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success py-2">
            <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
            <a href="<?= BASE_URL ?>/login.php" class="alert-link ms-1">Login sekarang →</a>
        </div>
        <?php else: ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">No. HP</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                       placeholder="08xxxxxxxxxx">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control"
                       minlength="6" required autocomplete="new-password">
                <div class="form-text">Minimal 6 karakter.</div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control"
                       minlength="6" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-person-plus me-1"></i>Daftar
            </button>
        </form>

        <?php endif; ?>

        <hr class="my-4">
        <p class="text-center text-muted small mb-0">
            Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none">Login di sini</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

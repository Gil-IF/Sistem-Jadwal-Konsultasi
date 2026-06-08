<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/index.php'));
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die('Token tidak valid.');
}

// Cari token di patients
$stmt = $pdo->prepare("SELECT id, email, reset_expires FROM patients WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
$role = 'patient';

if (!$user) {
    $stmt = $pdo->prepare("SELECT id, email, reset_expires FROM admins WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    $role = 'admin';
}

if (!$user) {
    die('Token reset password tidak valid.');
}

if (strtotime($user['reset_expires']) < time()) {
    die('Token sudah kadaluarsa. Silakan ulangi proses <a href="'.BASE_URL.'/forgotpassword.php">lupa password</a>.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($role == 'patient') {
            $update = $pdo->prepare("UPDATE patients SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        } else {
            $update = $pdo->prepare("UPDATE admins SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        }
        if ($update->execute([$hash, $user['id']])) {
            $success = 'Password berhasil direset. Silakan <a href="'.BASE_URL.'/login.php">login</a> dengan password baru.';
        } else {
            $error = 'Gagal mereset password. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
    <link rel="icon" type="image/png" href="./src/img/logo.png">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 720px;">
        <div class="auth-left">
            <img src="./src/img/Hospital.png" alt="Logo">
            <h5>AntriSehat</h5>
            <p>Reset Password</p>
        </div>
        <div class="auth-right">
            <h5 class="fw-bold mb-1">Buat Password Baru</h5>
            <p class="text-muted small mb-4">Masukkan password baru Anda</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php else: ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
            <hr class="my-3">
            <p class="text-center text-muted small mb-0">
                <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none">Kembali ke Login</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
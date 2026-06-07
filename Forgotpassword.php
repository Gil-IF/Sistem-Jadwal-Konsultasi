<?php
require_once 'config/config.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/index.php' : '/user/index.php'));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Email harus diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek apakah email terdaftar di patients atau admins
        $stmt = $pdo->prepare("SELECT id, full_name FROM patients WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $role = 'patient';
        
        if (!$user) {
            $stmt = $pdo->prepare("SELECT id, full_name FROM admins WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $role = 'admin';
        }
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            if ($role == 'patient') {
                $update = $pdo->prepare("UPDATE patients SET reset_token = ?, reset_expires = ? WHERE id = ?");
            } else {
                $update = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expires = ? WHERE id = ?");
            }
            $update->execute([$token, $expires, $user['id']]);
            
            $resetLink = BASE_URL . "/reset_password.php?token=" . $token;
            // Tampilkan link langsung (karena local)
            $success = "Link reset password: <a href='$resetLink' class='alert-link fw-bold'>Klik di sini</a> (berlaku 1 jam)";
        } else {
            $error = 'Email tidak terdaftar dalam sistem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-left" style="flex: 0.8;">
            <img src="./src/img/Hospital.png" alt="Logo">
            <h5>AntriSehat</h5>
            <p>Reset Password</p>
        </div>
        <div class="auth-right">
            <h5 class="fw-bold mb-1">Lupa Password</h5>
            <p class="text-muted small mb-4">Masukkan email Anda</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Alamat Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-send me-1"></i> Kirim Link Reset
                </button>
            </form>
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
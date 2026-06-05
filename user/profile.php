<?php
require_once '../config/config.php';
$pageTitle = 'Profil Saya';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('patient');
    $action = $_POST['action'] ?? '';
    $pid    = $_SESSION['user_id'];

    if ($action === 'update_info') {
        $name  = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name) {
            $pdo->prepare(
                "UPDATE patients SET full_name=?, phone=? WHERE id=?"
            )->execute([$name, $phone, $pid]);
            $_SESSION['full_name'] = $name;
            setFlash('success', 'Profil berhasil diperbarui.');
        } else {
            setFlash('error', 'Nama tidak boleh kosong.');
        }
    } elseif ($action === 'change_password') {
        $old  = $_POST['old_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM patients WHERE id=?");
        $stmt->execute([$pid]);
        $row = $stmt->fetch();

        if (!password_verify($old, $row['password_hash'])) {
            setFlash('error', 'Password lama salah.');
        } elseif (strlen($new) < 6) {
            setFlash('error', 'Password baru minimal 6 karakter.');
        } elseif ($new !== $conf) {
            setFlash('error', 'Konfirmasi password tidak cocok.');
        } else {
            $pdo->prepare(
                "UPDATE patients SET password_hash=? WHERE id=?"
            )->execute([password_hash($new, PASSWORD_DEFAULT), $pid]);
            setFlash('success', 'Password berhasil diubah.');
        }
    }
    redirect(BASE_URL . '/user/profile.php');
}

require_once '../includes/layout_user.php';

$patient = $pdo->prepare("SELECT * FROM patients WHERE id=?");
$patient->execute([$_SESSION['user_id']]);
$patient = $patient->fetch();
?>

<div class="row g-4">
  <!-- Info -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-person me-1"></i>Informasi Pribadi</h6>
      </div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="update_info">
          <div class="mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required
                   value="<?= htmlspecialchars($patient['full_name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" disabled
                   value="<?= htmlspecialchars($patient['email']) ?>">
            <div class="form-text">Email tidak bisa diubah.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">No. HP</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($patient['phone'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Perubahan
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Ganti Password -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-1"></i>Ganti Password</h6>
      </div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label">Password Lama</label>
            <input type="password" name="old_password" class="form-control" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" name="new_password" class="form-control" required
                   autocomplete="new-password" minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" class="form-control" required
                   autocomplete="new-password" minlength="6">
          </div>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-key me-1"></i>Ubah Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/layout_user_end.php'; ?>

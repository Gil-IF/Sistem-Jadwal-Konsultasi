<?php
require_once '../config/config.php';
require_once '../includes/layout_admin.php';

// --- Proses CRUD ---
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Tambah Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Nama, Email, dan Password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek email sudah terdaftar?
        $check = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Email sudah digunakan. Gunakan email lain.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, email, phone, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$full_name, $email, $phone, $hash])) {
                $success = "Pasien berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan pasien.";
            }
        }
    }
}

// Edit Pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_patient'])) {
    $id_edit = (int)$_POST['id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    
    if (empty($full_name) || empty($email)) {
        $error = "Nama dan Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek email milik pasien lain
        $check = $pdo->prepare("SELECT id FROM patients WHERE email = ? AND id != ?");
        $check->execute([$email, $id_edit]);
        if ($check->fetch()) {
            $error = "Email sudah digunakan oleh pasien lain.";
        } else {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?");
                $result = $stmt->execute([$full_name, $email, $phone, $hash, $id_edit]);
            } else {
                $stmt = $pdo->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $result = $stmt->execute([$full_name, $email, $phone, $id_edit]);
            }
            if ($result) {
                $success = "Data pasien berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui data.";
            }
        }
    }
}

// Hapus Pasien
if ($action === 'delete' && $id > 0) {
    // Cek apakah pasien memiliki janji temu?
    $checkApp = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
    $checkApp->execute([$id]);
    $count = $checkApp->fetchColumn();
    if ($count > 0) {
        $error = "Pasien tidak dapat dihapus karena masih memiliki $count janji temu. Hapus janji temu terlebih dahulu.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Pasien berhasil dihapus.";
        } else {
            $error = "Gagal menghapus pasien.";
        }
    }
    // Redirect untuk menghindari refresh delete
    if ($success) {
        setFlash('success', $success);
    } elseif ($error) {
        setFlash('error', $error);
    }
    redirect(BASE_URL . '/admin/patients.php');
}

// Ambil data pasien untuk ditampilkan (setelah proses add/edit/delete)
$patients = $pdo->query(
    "SELECT p.*, COUNT(a.id) AS total_appointments
     FROM patients p
     LEFT JOIN appointments a ON a.patient_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at ASC"
)->fetchAll();

// Data untuk edit (jika tombol edit diklik, akan muncul modal)
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editData) $editData = null;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Manajemen Pasien</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-person-plus"></i> Tambah Pasien
    </button>
</div>

<!-- Pesan flash -->
<?php
$flash = getFlash();
if ($flash):
    $alertClass = ($flash['type'] === 'success') ? 'alert-success' : 'alert-danger';
?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabel Pasien -->
<div class="card table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Total Janji</th><th>Terdaftar</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($patients as $p): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td><?= htmlspecialchars($p['email']) ?></td>
        <td><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
        <td><span class="badge bg-info text-dark"><?= $p['total_appointments'] ?></span></td>
        <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
        <td>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal" 
                    onclick="fillEditForm(<?= $p['id'] ?>, '<?= addslashes($p['full_name']) ?>', '<?= addslashes($p['email']) ?>', '<?= addslashes($p['phone'] ?? '') ?>')">
                <i class="bi bi-pencil"></i>
            </button>
            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" 
               onclick="return confirm('Yakin ingin menghapus pasien <?= htmlspecialchars($p['full_name']) ?>? Data janji temu juga akan terpengaruh.')">
                <i class="bi bi-trash"></i>
            </a>
         </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$patients): ?>
      <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pasien</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Pasien -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Pasien Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>No. HP</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="mb-3">
            <label>Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
            <small class="text-muted">Minimal 6 karakter, akan di-hash</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="add_patient" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Pasien -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Pasien</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="edit_email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>No. HP</label>
            <input type="text" name="phone" id="edit_phone" class="form-control">
          </div>
          <div class="mb-3">
            <label>Password (Kosongkan jika tidak diubah)</label>
            <input type="password" name="password" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="edit_patient" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function fillEditForm(id, name, email, phone) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_full_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
}
// Jika ada error dari edit, tetap buka modal
<?php if ($editData): ?>
    document.addEventListener('DOMContentLoaded', function() {
        fillEditForm(<?= $editData['id'] ?>, '<?= addslashes($editData['full_name']) ?>', '<?= addslashes($editData['email']) ?>', '<?= addslashes($editData['phone'] ?? '') ?>');
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
<?php endif; ?>
</script>

<?php require_once '../includes/layout_admin_end.php'; ?>
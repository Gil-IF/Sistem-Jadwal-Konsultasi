<?php
require_once '../config/config.php';

// 🔒 Proteksi akses: hanya admin yang boleh mengakses halaman ini
requireLogin('admin');

$pageTitle = 'Manajemen Dokter';

// POST: tambah / edit / toggle aktif / soft delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['full_name'] ?? '');
        $spec = trim($_POST['specialization'] ?? '');
        $lic  = trim($_POST['license_number'] ?? '');

        if ($name && $lic) {
            $stmt = $pdo->prepare(
                "INSERT INTO doctors (full_name, specialization, license_number, is_active, deleted_at)
                 VALUES (?, ?, ?, 1, NULL)"
            );
            $stmt->execute([$name, $spec, $lic]);
            setFlash('success', 'Dokter berhasil ditambahkan.');
        } else {
            setFlash('error', 'Nama dan nomor lisensi wajib diisi.');
        }

    } elseif ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? '');
        $spec = trim($_POST['specialization'] ?? '');
        $lic  = trim($_POST['license_number'] ?? '');

        if ($id && $name && $lic) {
            $stmt = $pdo->prepare(
                "UPDATE doctors
                 SET full_name = ?, specialization = ?, license_number = ?
                 WHERE id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$name, $spec, $lic, $id]);
            setFlash('success', 'Data dokter diperbarui.');
        } else {
            setFlash('error', 'Data dokter tidak valid.');
        }

    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE doctors
                 SET is_active = NOT is_active
                 WHERE id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$id]);
            setFlash('success', 'Status dokter diperbarui.');
        } else {
            setFlash('error', 'ID dokter tidak valid.');
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE doctors
                 SET deleted_at = NOW(), is_active = 0
                 WHERE id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$id]);
            setFlash('success', 'Dokter berhasil dihapus (soft delete).');
        } else {
            setFlash('error', 'ID dokter tidak valid.');
        }
    }

    redirect(BASE_URL . '/admin/doctors.php');
}

require_once '../includes/layout_admin.php';

$doctors = $pdo->query(
    "SELECT *
     FROM doctors
     WHERE deleted_at IS NULL
     ORDER BY full_name"
)->fetchAll();
?>

<!-- HTML (sama seperti semula, tidak perlu diubah) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i> Tambah Dokter
    </button>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Spesialisasi</th>
                    <th>No. Lisensi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($doctors as $d): ?>
            <tr>
                <td><?= (int)$d['id'] ?></td>
                <td><?= htmlspecialchars($d['full_name']) ?></td>
                <td><?= htmlspecialchars($d['specialization'] ?? '-') ?></td>
                <td><code><?= htmlspecialchars($d['license_number']) ?></code></td>
                <td>
                    <span class="badge <?= !empty($d['is_active']) ? 'bg-success' : 'bg-secondary' ?>">
                        <?= !empty($d['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td>
                    <button
                        class="btn btn-sm btn-outline-primary me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#editModal"
                        data-id="<?= (int)$d['id'] ?>"
                        data-name="<?= htmlspecialchars($d['full_name'], ENT_QUOTES) ?>"
                        data-spec="<?= htmlspecialchars($d['specialization'] ?? '', ENT_QUOTES) ?>"
                        data-lic="<?= htmlspecialchars($d['license_number'], ENT_QUOTES) ?>"
                    >
                        <i class="bi bi-pencil"></i>
                    </button>

                    <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus dokter ini? Data akan disembunyikan dari sistem.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger me-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>

                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <button
                            type="submit"
                            class="btn btn-sm <?= !empty($d['is_active']) ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                            title="<?= !empty($d['is_active']) ? 'Nonaktifkan' : 'Aktifkan' ?>"
                        >
                            <i class="bi bi-<?= !empty($d['is_active']) ? 'toggle-on' : 'toggle-off' ?>"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (!$doctors): ?>
            <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada dokter</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Dokter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="full_name" class="form-control" required placeholder="dr. Nama Dokter">
        </div>
        <div class="mb-3">
          <label class="form-label">Spesialisasi</label>
          <input type="text" name="specialization" class="form-control" placeholder="Dokter Umum">
        </div>
        <div class="mb-3">
          <label class="form-label">Nomor Lisensi <span class="text-danger">*</span></label>
          <input type="text" name="license_number" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-header">
        <h5 class="modal-title">Edit Dokter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="full_name" id="editName" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Spesialisasi</label>
          <input type="text" name="specialization" id="editSpec" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Nomor Lisensi <span class="text-danger">*</span></label>
          <input type="text" name="license_number" id="editLic" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('editId').value   = btn.dataset.id || '';
    document.getElementById('editName').value = btn.dataset.name || '';
    document.getElementById('editSpec').value = btn.dataset.spec || '';
    document.getElementById('editLic').value  = btn.dataset.lic || '';
});
</script>

<?php require_once '../includes/layout_admin_end.php'; ?>
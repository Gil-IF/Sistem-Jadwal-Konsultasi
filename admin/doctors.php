<?php
require_once '../config/config.php';
$pageTitle = 'Manajemen Dokter';

// POST: tambah / edit / toggle aktif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('admin');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name   = trim($_POST['full_name'] ?? '');
        $spec   = trim($_POST['specialization'] ?? '');
        $lic    = trim($_POST['license_number'] ?? '');
        if ($name && $lic) {
            $stmt = $pdo->prepare(
                "INSERT INTO doctors (full_name, specialization, license_number) VALUES (?,?,?)"
            );
            $stmt->execute([$name, $spec, $lic]);
            setFlash('success', 'Dokter berhasil ditambahkan.');
        } else {
            setFlash('error', 'Nama dan nomor lisensi wajib diisi.');
        }
    } elseif ($action === 'edit') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['full_name'] ?? '');
        $spec = trim($_POST['specialization'] ?? '');
        $lic  = trim($_POST['license_number'] ?? '');
        if ($id && $name && $lic) {
            $pdo->prepare(
                "UPDATE doctors SET full_name=?, specialization=?, license_number=? WHERE id=?"
            )->execute([$name, $spec, $lic, $id]);
            setFlash('success', 'Data dokter diperbarui.');
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE doctors SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        setFlash('success', 'Status dokter diperbarui.');
    }
    redirect(BASE_URL . '/admin/doctors.php');
}

require_once '../includes/layout_admin.php';

$doctors = $pdo->query("SELECT * FROM doctors ORDER BY full_name")->fetchAll();
?>

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
                <tr><th>#</th><th>Nama</th><th>Spesialisasi</th><th>No. Lisensi</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($doctors as $d): ?>
            <tr>
                <td><?= $d['id'] ?></td>
                <td><?= htmlspecialchars($d['full_name']) ?></td>
                <td><?= htmlspecialchars($d['specialization'] ?? '-') ?></td>
                <td><code><?= htmlspecialchars($d['license_number']) ?></code></td>
                <td>
                    <span class="badge <?= $d['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $d['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1"
                        data-bs-toggle="modal" data-bs-target="#editModal"
                        data-id="<?= $d['id'] ?>"
                        data-name="<?= htmlspecialchars($d['full_name'], ENT_QUOTES) ?>"
                        data-spec="<?= htmlspecialchars($d['specialization'] ?? '', ENT_QUOTES) ?>"
                        data-lic="<?= htmlspecialchars($d['license_number'], ENT_QUOTES) ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm <?= $d['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                title="<?= $d['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                            <i class="bi bi-<?= $d['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$doctors): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada dokter</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Dokter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-header">
        <h5 class="modal-title">Edit Dokter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    document.getElementById('editId').value   = btn.dataset.id;
    document.getElementById('editName').value = btn.dataset.name;
    document.getElementById('editSpec').value = btn.dataset.spec;
    document.getElementById('editLic').value  = btn.dataset.lic;
});
</script>

<?php require_once '../includes/layout_admin_end.php'; ?>

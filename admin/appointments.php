<?php
require_once '../config/config.php';
$pageTitle = 'Manajemen Janji Temu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('admin');
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'complete' && $id) {
        $pdo->prepare("UPDATE appointments SET status='completed' WHERE id=?")->execute([$id]);
        setFlash('success', 'Janji temu ditandai selesai.');
    } elseif ($action === 'cancel' && $id) {
        // Bebaskan slot
        $pdo->prepare(
            "UPDATE time_slots ts
             JOIN appointments a ON a.slot_id = ts.id
             SET ts.is_booked = 0
             WHERE a.id = ?"
        )->execute([$id]);
        $pdo->prepare("UPDATE appointments SET status='cancelled' WHERE id=?")->execute([$id]);
        setFlash('success', 'Janji temu dibatalkan dan slot dikembalikan.');
    }
    redirect(BASE_URL . '/admin/appointments.php');
}

require_once '../includes/layout_admin.php';

$filterStatus = $_GET['status'] ?? '';
$where  = $filterStatus ? "WHERE a.status = ?" : "";
$params = $filterStatus ? [$filterStatus] : [];

$stmt = $pdo->prepare(
    "SELECT a.*, p.full_name AS patient, p.phone,
            d.full_name AS doctor, ts.slot_datetime, ts.duration_minutes
     FROM appointments a
     JOIN patients p    ON p.id  = a.patient_id
     JOIN time_slots ts ON ts.id = a.slot_id
     JOIN doctors d     ON d.id  = ts.doctor_id
     $where
     ORDER BY ts.slot_datetime ASC"
);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>

<!-- Filter -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <?php
    $statuses = ['' => 'Semua', 'booked' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    foreach ($statuses as $val => $label):
        $active = $filterStatus === $val ? 'btn-primary' : 'btn-outline-secondary';
    ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $active ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Pasien</th><th>No. HP</th><th>Dokter</th><th>Jadwal</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
      <tr>
        <td><?= $a['id'] ?></td>
        <td><?= htmlspecialchars($a['patient']) ?></td>
        <td><?= htmlspecialchars($a['phone'] ?? '-') ?></td>
        <td><?= htmlspecialchars($a['doctor']) ?></td>
        <td><?= date('d M Y, H:i', strtotime($a['slot_datetime'])) ?></td>
        <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($a['notes'] ?? '-', 0, 40, '…')) ?></td>
        <td>
          <span class="badge rounded-pill badge-<?= $a['status'] ?>">
            <?= ucfirst($a['status']) ?>
          </span>
        </td>
        <td>
          <?php if ($a['status'] === 'booked'): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-outline-success me-1" title="Selesai">
              <i class="bi bi-check-lg"></i>
            </button>
          </form>
          <form method="post" class="d-inline"
                onsubmit="return confirm('Batalkan janji temu ini?')">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" title="Batalkan">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
          <?php else: ?>
          <span class="text-muted small">–</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$appointments): ?>
      <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/layout_admin_end.php'; ?>

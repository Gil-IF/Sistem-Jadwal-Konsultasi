<?php
require_once '../config/config.php';
$pageTitle = 'Janji Saya';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('patient');
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'cancel' && $id) {
        // Pastikan milik pasien ini & statusnya masih booked
        $stmt = $pdo->prepare(
            "SELECT a.id, a.slot_id FROM appointments a
             WHERE a.id = ? AND a.patient_id = ? AND a.status = 'booked'"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);
        $appt = $stmt->fetch();

        if ($appt) {
            $pdo->prepare("UPDATE appointments SET status='cancelled' WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE time_slots SET is_booked=0 WHERE id=?")->execute([$appt['slot_id']]);
            setFlash('success', 'Janji temu berhasil dibatalkan.');
        } else {
            setFlash('error', 'Janji tidak ditemukan atau tidak bisa dibatalkan.');
        }
    }
    redirect(BASE_URL . '/user/appointments.php');
}

require_once '../includes/layout_user.php';

$filterStatus = $_GET['status'] ?? '';
$pid = $_SESSION['user_id'];
$w   = $filterStatus ? "AND a.status = ?" : "";
$p   = $filterStatus ? [$pid, $filterStatus] : [$pid];

$stmt = $pdo->prepare(
    "SELECT a.id, a.status, a.notes, a.booking_time,
            d.full_name AS doctor, d.specialization,
            ts.slot_datetime, ts.duration_minutes
     FROM appointments a
     JOIN time_slots ts ON ts.id = a.slot_id
     JOIN doctors d     ON d.id  = ts.doctor_id
     WHERE a.patient_id = ? $w
     ORDER BY ts.slot_datetime DESC"
);
$stmt->execute($p);
$appointments = $stmt->fetchAll();
?>

<div class="mb-3 d-flex gap-2 flex-wrap">
  <?php
  $statuses = ['' => 'Semua', 'booked' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
  foreach ($statuses as $val => $label):
      $active = $filterStatus === $val ? 'btn-primary' : 'btn-outline-secondary';
  ?>
  <a href="?status=<?= $val ?>" class="btn btn-sm <?= $active ?>"><?= $label ?></a>
  <?php endforeach; ?>
  <a href="<?= BASE_URL ?>/user/booking.php" class="btn btn-sm btn-success ms-auto">
    <i class="bi bi-plus-circle me-1"></i> Buat Janji Baru
  </a>
</div>

<div class="card table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Dokter</th><th>Jadwal</th><th>Durasi</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
      <tr>
        <td><?= $a['id'] ?></td>
        <td>
          <div><?= htmlspecialchars($a['doctor']) ?></div>
          <small class="text-muted"><?= htmlspecialchars($a['specialization'] ?? '') ?></small>
        </td>
        <td><?= date('d M Y, H:i', strtotime($a['slot_datetime'])) ?></td>
        <td><?= $a['duration_minutes'] ?> menit</td>
        <td class="text-muted small"><?= htmlspecialchars($a['notes'] ?? '-') ?></td>
        <td>
          <span class="badge rounded-pill badge-<?= $a['status'] ?>">
            <?= ucfirst($a['status']) ?>
          </span>
        </td>
        <td>
          <?php
          $isFuture = strtotime($a['slot_datetime']) > time();
          if ($a['status'] === 'booked' && $isFuture): ?>
          <form method="post" class="d-inline"
                onsubmit="return confirm('Batalkan janji ini?')">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">
              <i class="bi bi-x-circle me-1"></i>Batalkan
            </button>
          </form>
          <?php else: ?>
          <span class="text-muted small">–</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$appointments): ?>
      <tr>
        <td colspan="7" class="text-center py-4 text-muted">
          Tidak ada data janji temu.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/layout_user_end.php'; ?>

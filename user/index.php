<?php
require_once '../config/config.php';
$pageTitle = 'Beranda';
require_once '../includes/layout_user.php';

$patientId = $_SESSION['user_id'];

$upcoming = $pdo->prepare(
    "SELECT a.id, d.full_name AS doctor, d.specialization,
            ts.slot_datetime, ts.duration_minutes, a.status, a.notes
     FROM appointments a
     JOIN time_slots ts ON ts.id = a.slot_id
     JOIN doctors d     ON d.id  = ts.doctor_id
     WHERE a.patient_id = ? AND a.status = 'booked' AND ts.slot_datetime >= NOW()
     ORDER BY ts.slot_datetime ASC LIMIT 5"
);
$upcoming->execute([$patientId]);
$upcomings = $upcoming->fetchAll();

$total = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=?")->execute([$patientId]);
$stats = [
    'upcoming'  => $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN time_slots ts ON ts.id=a.slot_id WHERE a.patient_id=? AND a.status='booked' AND ts.slot_datetime>=NOW()")->execute([$patientId]) ? $pdo->query("SELECT COUNT(*) FROM appointments a JOIN time_slots ts ON ts.id=a.slot_id WHERE a.patient_id=$patientId AND a.status='booked' AND ts.slot_datetime>=NOW()")->fetchColumn() : 0,
    'completed' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE patient_id=$patientId AND status='completed'")->fetchColumn(),
    'cancelled' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE patient_id=$patientId AND status='cancelled'")->fetchColumn(),
];
?>

<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-check"></i></div>
        <div>
          <div class="h4 mb-0 fw-bold"><?= $stats['upcoming'] ?></div>
          <div class="text-muted small">Janji Mendatang</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
        <div>
          <div class="h4 mb-0 fw-bold"><?= $stats['completed'] ?></div>
          <div class="text-muted small">Selesai</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
        <div>
          <div class="h4 mb-0 fw-bold"><?= $stats['cancelled'] ?></div>
          <div class="text-muted small">Dibatalkan</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card table-card">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
    <h6 class="mb-0 fw-bold">Janji Temu Mendatang</h6>
    <a href="<?= BASE_URL ?>/user/booking.php" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Buat Janji
    </a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>Dokter</th><th>Spesialisasi</th><th>Jadwal</th><th>Durasi</th><th>Catatan</th></tr>
      </thead>
      <tbody>
      <?php foreach ($upcomings as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['doctor']) ?></td>
        <td><?= htmlspecialchars($u['specialization'] ?? '-') ?></td>
        <td><?= date('d M Y, H:i', strtotime($u['slot_datetime'])) ?></td>
        <td><?= $u['duration_minutes'] ?> menit</td>
        <td class="text-muted small"><?= htmlspecialchars($u['notes'] ?? '-') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$upcomings): ?>
      <tr>
        <td colspan="5" class="text-center py-4">
          <i class="bi bi-calendar-x fs-3 text-muted d-block mb-2"></i>
          Belum ada janji mendatang.
          <a href="<?= BASE_URL ?>/user/booking.php">Buat sekarang</a>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/layout_user_end.php'; ?>

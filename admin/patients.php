<?php
require_once '../config/config.php';
$pageTitle = 'Data Pasien';
require_once '../includes/layout_admin.php';

$patients = $pdo->query(
    "SELECT p.*, COUNT(a.id) AS total_appointments
     FROM patients p
     LEFT JOIN appointments a ON a.patient_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC"
)->fetchAll();
?>

<div class="card table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr><th>#</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Total Janji</th><th>Terdaftar</th></tr>
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
      </tr>
      <?php endforeach; ?>
      <?php if (!$patients): ?>
      <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pasien</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/layout_admin_end.php'; ?>

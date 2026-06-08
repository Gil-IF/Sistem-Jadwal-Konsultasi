<?php
require_once '../config/config.php';
$pageTitle = 'Dashboard';
require_once '../includes/layout_admin.php';

// Statistik
$stats = [
    'doctors'      => $pdo->query("SELECT COUNT(*) FROM doctors WHERE is_active=1")->fetchColumn(),
    'patients'     => $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'slots_free'   => $pdo->query("SELECT COUNT(*) FROM time_slots WHERE is_booked=0 AND slot_datetime >= NOW()")->fetchColumn(),
    'appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='booked'")->fetchColumn(),
];

// Janji temu terbaru (5)
$recent = $pdo->query(
    "SELECT a.id, p.full_name AS patient, d.full_name AS doctor,
            ts.slot_datetime, a.status, a.notes
     FROM appointments a
     JOIN patients p   ON p.id = a.patient_id
     JOIN time_slots ts ON ts.id = a.slot_id
     JOIN doctors d    ON d.id  = ts.doctor_id
     ORDER BY a.booking_time ASC LIMIT 5"
)->fetchAll();
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['icon'=>'bi-person-badge','color'=>'bg-primary bg-opacity-10 text-primary',  'label'=>'Dokter Aktif',    'value'=>$stats['doctors']],
        ['icon'=>'bi-people',      'color'=>'bg-success bg-opacity-10 text-success',  'label'=>'Total Pasien',    'value'=>$stats['patients']],
        ['icon'=>'bi-calendar3',   'color'=>'bg-warning bg-opacity-10 text-warning',  'label'=>'Slot Tersedia',   'value'=>$stats['slots_free']],
        ['icon'=>'bi-clipboard2-check','color'=>'bg-info bg-opacity-10 text-info',    'label'=>'Janji Aktif',     'value'=>$stats['appointments']],
    ];
    foreach ($cards as $c): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $c['color'] ?>">
                    <i class="bi <?= $c['icon'] ?>"></i>
                </div>
                <div>
                    <div class="h4 mb-0 fw-bold"><?= $c['value'] ?></div>
                    <div class="text-muted small"><?= $c['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Janji Temu Terbaru -->
<div class="card table-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold">Janji Temu Terbaru</h6>
        <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pasien</th>
                    <th>Dokter</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recent): foreach ($recent as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['patient']) ?></td>
                <td><?= htmlspecialchars($r['doctor']) ?></td>
                <td><?= date('d M Y, H:i', strtotime($r['slot_datetime'])) ?></td>
                <td>
                    <span class="badge rounded-pill badge-<?= $r['status'] ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/layout_admin_end.php'; ?>

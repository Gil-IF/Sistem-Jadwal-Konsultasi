<?php
require_once '../config/config.php';
$pageTitle = 'Buat Janji Temu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('patient');
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $notes   = trim($_POST['notes'] ?? '');

    if (!$slot_id) {
        setFlash('error', 'Pilih slot waktu terlebih dahulu.');
        redirect(BASE_URL . '/user/booking.php');
    }

    // Cek slot masih tersedia (pakai transaksi untuk menghindari race condition)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM time_slots WHERE id = ? AND is_booked = 0 AND slot_datetime > NOW() FOR UPDATE"
        );
        $stmt->execute([$slot_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            setFlash('error', 'Slot sudah tidak tersedia. Silakan pilih lain.');
            redirect(BASE_URL . '/user/booking.php');
        }

        $pdo->prepare(
            "INSERT INTO appointments (patient_id, slot_id, notes) VALUES (?,?,?)"
        )->execute([$_SESSION['user_id'], $slot_id, $notes]);

        $pdo->prepare("UPDATE time_slots SET is_booked=1 WHERE id=?")->execute([$slot_id]);
        $pdo->commit();
        setFlash('success', 'Janji temu berhasil dibuat! Silakan datang tepat waktu.');
        redirect(BASE_URL . '/user/appointments.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Terjadi kesalahan. Coba lagi.');
        redirect(BASE_URL . '/user/booking.php');
    }
}

require_once '../includes/layout_user.php';

$doctors = $pdo->query(
    "SELECT DISTINCT d.id, d.full_name, d.specialization
     FROM doctors d
     JOIN time_slots ts ON ts.doctor_id = d.id
     WHERE d.is_active = 1 AND ts.is_booked = 0 AND ts.slot_datetime > NOW()
     ORDER BY d.full_name"
)->fetchAll();

$selectedDoctor = (int)($_GET['doctor_id'] ?? 0);
$selectedDate   = $_GET['date'] ?? '';

$slots = [];
if ($selectedDoctor) {
    $w = "WHERE ts.doctor_id = ? AND ts.is_booked = 0 AND ts.slot_datetime > NOW()";
    $p = [$selectedDoctor];
    if ($selectedDate) { $w .= " AND DATE(ts.slot_datetime) = ?"; $p[] = $selectedDate; }
    $s = $pdo->prepare("SELECT * FROM time_slots ts $w ORDER BY ts.slot_datetime");
    $s->execute($p);
    $slots = $s->fetchAll();
}
?>

<div class="row g-4">
  <!-- Kiri: Pilih Dokter & Tanggal -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-1"></i>Filter Slot</h6>
        <form method="get">
          <div class="mb-3">
            <label class="form-label small">Pilih Dokter</label>
            <select name="doctor_id" class="form-select" onchange="this.form.submit()">
              <option value="">– Semua –</option>
              <?php foreach ($doctors as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $selectedDoctor == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['full_name']) ?>
                <?php if ($d['specialization']): ?>
                  (<?= htmlspecialchars($d['specialization']) ?>)
                <?php endif; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small">Pilih Tanggal</label>
            <input type="date" name="date" class="form-control"
                   value="<?= htmlspecialchars($selectedDate) ?>"
                   min="<?= date('Y-m-d') ?>"
                   onchange="this.form.submit()">
          </div>
          <!-- FIX: hidden input doctor_id dihapus karena duplikat dengan <select>
               dan menyebabkan nilai select selalu ditimpa. -->
        </form>
      </div>
    </div>
  </div>

  <!-- Kanan: Form Booking + Slot -->
  <div class="col-md-8">
    <form method="post">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-clock me-1"></i>Pilih Slot Waktu</h6>
          <?php if (!$selectedDoctor): ?>
            <p class="text-muted text-center py-3">Pilih dokter terlebih dahulu.</p>
          <?php elseif (!$slots): ?>
            <p class="text-muted text-center py-3">Tidak ada slot tersedia untuk pilihan ini.</p>
          <?php else: ?>
            <div class="row g-2" id="slotGrid">
              <?php foreach ($slots as $s): ?>
              <div class="col-6 col-sm-4">
                <input type="radio" class="btn-check" name="slot_id"
                       id="slot<?= $s['id'] ?>" value="<?= $s['id'] ?>" required>
                <label class="btn btn-outline-primary w-100 py-2" for="slot<?= $s['id'] ?>">
                  <div class="fw-semibold"><?= date('H:i', strtotime($s['slot_datetime'])) ?></div>
                  <div class="small text-muted"><?= date('d M', strtotime($s['slot_datetime'])) ?></div>
                  <div class="small text-muted"><?= $s['duration_minutes'] ?> mnt</div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($slots): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-1"></i>Catatan (opsional)</h6>
          <textarea name="notes" class="form-control" rows="3"
                    placeholder="Keluhan atau keterangan tambahan…"></textarea>
          <button type="submit" class="btn btn-primary mt-3 w-100">
            <i class="bi bi-calendar-check me-1"></i> Konfirmasi Booking
          </button>
        </div>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php require_once '../includes/layout_user_end.php'; ?>
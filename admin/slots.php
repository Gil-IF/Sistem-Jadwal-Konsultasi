<?php
require_once '../config/config.php';
$pageTitle = 'Manajemen Slot Waktu';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('admin');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $doctor_id = (int)$_POST['doctor_id'];
        $datetime  = trim($_POST['slot_datetime'] ?? '');
        $duration  = (int)($_POST['duration_minutes'] ?? 30);
        if ($doctor_id && $datetime) {
            try {
                $pdo->prepare(
                    "INSERT INTO time_slots (doctor_id, slot_datetime, duration_minutes) VALUES (?,?,?)"
                )->execute([$doctor_id, $datetime, $duration]);
                setFlash('success', 'Slot berhasil ditambahkan.');
            } catch (PDOException $e) {
                setFlash('error', 'Slot sudah ada untuk dokter & waktu ini.');
            }
        } else {
            setFlash('error', 'Semua field wajib diisi.');
        }

    } elseif ($action === 'bulk_add') {
        $doctor_id  = (int)$_POST['doctor_id'];
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date']   ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time   = $_POST['end_time']   ?? '';
        $duration   = (int)($_POST['duration_minutes'] ?? 30);

        if ($doctor_id && $start_date && $end_date && $start_time && $end_time && $duration > 0) {
            $added   = 0;
            $current = new DateTime($start_date);
            $last    = new DateTime($end_date);

            while ($current <= $last) {
                $t = new DateTime($current->format('Y-m-d') . ' ' . $start_time);
                $e = new DateTime($current->format('Y-m-d') . ' ' . $end_time);
                while ($t < $e) {
                    try {
                        $pdo->prepare(
                            "INSERT INTO time_slots (doctor_id, slot_datetime, duration_minutes) VALUES (?,?,?)"
                        )->execute([$doctor_id, $t->format('Y-m-d H:i:s'), $duration]);
                        $added++;
                    } catch (PDOException) { /* skip duplikat */ }
                    $t->modify("+{$duration} minutes");
                }
                $current->modify('+1 day');
            }
            setFlash('success', "$added slot berhasil dibuat.");
        } else {
            setFlash('error', 'Semua field wajib diisi.');
        }

    } elseif ($action === 'delete') {
        $id    = (int)$_POST['id'];
        $check = $pdo->prepare("SELECT is_booked FROM time_slots WHERE id=?");
        $check->execute([$id]);
        $row = $check->fetch();
        if ($row && $row['is_booked']) {
            setFlash('error', 'Slot sudah di-booking, tidak bisa dihapus.');
        } else {
            $pdo->prepare("DELETE FROM time_slots WHERE id=?")->execute([$id]);
            setFlash('success', 'Slot dihapus.');
        }
    }

    redirect(BASE_URL . '/admin/slots.php');
}

require_once '../includes/layout_admin.php';

$doctors = $pdo->query("SELECT id, full_name FROM doctors WHERE is_active=1 ORDER BY full_name")->fetchAll();

// Filter
$filterDoctor = (int)($_GET['doctor_id'] ?? 0);
$filterDate   = $_GET['date'] ?? '';

$where  = "WHERE 1=1";
$params = [];
if ($filterDoctor) { $where .= " AND ts.doctor_id = ?"; $params[] = $filterDoctor; }
if ($filterDate)   { $where .= " AND DATE(ts.slot_datetime) = ?"; $params[] = $filterDate; }

$stmt = $pdo->prepare(
    "SELECT ts.*, d.full_name AS doctor_name
     FROM time_slots ts JOIN doctors d ON d.id = ts.doctor_id
     $where ORDER BY ts.slot_datetime DESC LIMIT 100"
);
$stmt->execute($params);
$slots = $stmt->fetchAll();
?>

<!-- Filter & Actions -->
<div class="card mb-4 border-0 shadow-sm">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-sm-4">
        <label class="form-label small">Filter Dokter</label>
        <select name="doctor_id" class="form-select form-select-sm">
          <option value="">Semua Dokter</option>
          <?php foreach ($doctors as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDoctor == $d['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['full_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-3">
        <label class="form-label small">Filter Tanggal</label>
        <input type="date" name="date" class="form-control form-control-sm"
               value="<?= htmlspecialchars($filterDate) ?>">
      </div>
      <div class="col-sm-2">
        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
      </div>
      <div class="col-sm-3 text-end">
        <!-- FIX: tambah type="button" agar tidak trigger submit form GET -->
        <button type="button" class="btn btn-sm btn-primary me-1"
                data-bs-toggle="modal" data-bs-target="#addModal">
          <i class="bi bi-plus"></i> Tambah Slot
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#bulkModal">
          <i class="bi bi-calendar-range"></i> Bulk
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Tabel Slot -->
<div class="card table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Dokter</th>
          <th>Tanggal & Waktu</th>
          <th>Durasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($slots as $s): ?>
      <tr>
        <td><?= $s['id'] ?></td>
        <td><?= htmlspecialchars($s['doctor_name']) ?></td>
        <td><?= date('d M Y, H:i', strtotime($s['slot_datetime'])) ?></td>
        <td><?= $s['duration_minutes'] ?> menit</td>
        <td>
          <span class="badge <?= $s['is_booked'] ? 'bg-danger' : 'bg-success' ?>">
            <?= $s['is_booked'] ? 'Dipesan' : 'Tersedia' ?>
          </span>
        </td>
        <td>
          <?php if (!$s['is_booked']): ?>
          <form method="post" class="d-inline"
                onsubmit="return confirm('Hapus slot ini?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash"></i>
            </button>
          </form>
          <?php else: ?>
          <span class="text-muted small">–</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$slots): ?>
      <tr>
        <td colspan="6" class="text-center text-muted py-4">Belum ada slot</td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Slot Satuan -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Slot Satuan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Dokter <span class="text-danger">*</span></label>
          <select name="doctor_id" class="form-select" required>
            <option value="">– Pilih –</option>
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Tanggal & Waktu <span class="text-danger">*</span></label>
          <input type="datetime-local" name="slot_datetime" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Durasi (menit)</label>
          <input type="number" name="duration_minutes" class="form-control"
                 value="30" min="10" max="120">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Bulk -->
<div class="modal fade" id="bulkModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="bulk_add">
      <div class="modal-header">
        <h5 class="modal-title">Buat Slot Massal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Dokter <span class="text-danger">*</span></label>
          <select name="doctor_id" class="form-select" required>
            <option value="">– Pilih –</option>
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="col-6">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Jam Mulai</label>
            <input type="time" name="start_time" class="form-control" value="08:00" required>
          </div>
          <div class="col-6">
            <label class="form-label">Jam Selesai</label>
            <input type="time" name="end_time" class="form-control" value="17:00" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Durasi per Slot (menit)</label>
          <input type="number" name="duration_minutes" class="form-control"
                 value="30" min="10" max="120">
        </div>
        <div class="alert alert-info py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          Slot akan dibuat setiap <strong>N menit</strong> dari jam mulai hingga jam selesai,
          untuk setiap hari dalam rentang tanggal.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Buat Slot</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/layout_admin_end.php'; ?>
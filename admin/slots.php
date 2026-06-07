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
            // Validasi tanggal mulai <= tanggal akhir
            if (strtotime($start_date) > strtotime($end_date)) {
                setFlash('error', 'Tanggal mulai harus lebih kecil atau sama dengan tanggal akhir.');
                redirect(BASE_URL . '/admin/slots.php');
            }

            // Batasan maksimal 31 hari untuk menghindari overload
            $dateDiff = (strtotime($end_date) - strtotime($start_date)) / (60*60*24);
            if ($dateDiff > 31) {
                setFlash('error', 'Rentang tanggal maksimal 31 hari.');
                redirect(BASE_URL . '/admin/slots.php');
            }

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

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20; // jumlah baris per halaman
$offset = ($page - 1) * $limit;

// Query hitung total (untuk pagination)
$countSql = "SELECT COUNT(*) FROM time_slots ts WHERE 1=1";
$countParams = [];
if ($filterDoctor) { $countSql .= " AND ts.doctor_id = ?"; $countParams[] = $filterDoctor; }
if ($filterDate)   { $countSql .= " AND DATE(ts.slot_datetime) = ?"; $countParams[] = $filterDate; }

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Query data dengan limit dan offset
$sql = "SELECT ts.*, d.full_name AS doctor_name
        FROM time_slots ts
        JOIN doctors d ON d.id = ts.doctor_id
        WHERE 1=1";
$params = [];
if ($filterDoctor) { $sql .= " AND ts.doctor_id = ?"; $params[] = $filterDoctor; }
if ($filterDate)   { $sql .= " AND DATE(ts.slot_datetime) = ?"; $params[] = $filterDate; }
$sql .= " ORDER BY ts.slot_datetime DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$slots = $stmt->fetchAll();
?>

<!-- Filter & Actions (sama seperti semula, tambahkan hidden field page agar filter reset ke halaman 1) -->
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
      <?php
      $no = $offset + 1;
      foreach ($slots as $s):
      ?>
      <tr>
        <td><?= $no++ ?></td>
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

<!-- Pagination Navigation -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Page navigation" class="mt-4">
  <ul class="pagination justify-content-center">
    <!-- Tombol Previous -->
    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">&laquo; Sebelumnya</a>
    </li>

    <!-- Penomoran halaman -->
    <?php
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    for ($i = $startPage; $i <= $endPage; $i++):
    ?>
      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>

    <!-- Tombol Next -->
    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">Selanjutnya &raquo;</a>
    </li>
  </ul>
</nav>
<?php endif; ?>


<?php require_once '../includes/layout_admin_end.php'; ?>
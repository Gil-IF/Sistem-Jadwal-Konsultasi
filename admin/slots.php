<?php
require_once '../config/config.php';
$pageTitle = 'Manajemen Slot Waktu';

// ── Helper ────────────────────────────────────────────────────────────────────
function generateSlots($pdo, $doctor_id, $date, $session, $max_slots) {
    $duration = 30;
    if ($session === 'pagi') {
        $start_h = 7; $end_h = 12; $start_time = '07:00';
    } else {
        $start_h = 13; $end_h = 17; $start_time = '13:00';
    }

    // Hapus slot kosong di sesi ini saja
    $pdo->prepare(
        "DELETE FROM time_slots
         WHERE doctor_id = ? AND DATE(slot_datetime) = ?
           AND HOUR(slot_datetime) >= ? AND HOUR(slot_datetime) < ?
           AND is_booked = 0"
    )->execute([$doctor_id, $date, $start_h, $end_h]);

    // Hitung yang sudah booked (tidak bisa dihapus)
    $s = $pdo->prepare(
        "SELECT COUNT(*) FROM time_slots
         WHERE doctor_id = ? AND DATE(slot_datetime) = ?
           AND HOUR(slot_datetime) >= ? AND HOUR(slot_datetime) < ?
           AND is_booked = 1"
    );
    $s->execute([$doctor_id, $date, $start_h, $end_h]);
    $booked_count = (int)$s->fetchColumn();

    $t      = new DateTime($date . ' ' . $start_time);
    $target = max($max_slots, $booked_count);
    $added  = 0;

    while ($added < $target) {
        $ins = $pdo->prepare(
            "INSERT IGNORE INTO time_slots (doctor_id, slot_datetime, duration_minutes) VALUES (?,?,?)"
        );
        $ins->execute([$doctor_id, $t->format('Y-m-d H:i:s'), $duration]);
        if ($ins->rowCount() > 0) $added++;
        $t->modify("+{$duration} minutes");
    }
}

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('admin');
    $action = $_POST['action'] ?? '';

    if ($action === 'edit_session') {
        $doctor_id = (int)$_POST['doctor_id'];
        $date      = $_POST['date'] ?? '';
        $session   = $_POST['session'] ?? '';
        $is_active = (int)($_POST['is_active'] ?? 0);
        $max_slots = max(1, min(5, (int)($_POST['max_slots'] ?? 5))); // maks 5

        if (!$doctor_id || !$date || !in_array($session, ['pagi','siang'])) {
            setFlash('error', 'Data tidak valid.');
            redirect(BASE_URL . '/admin/slots.php?date=' . $date);
        }

        if ($session === 'pagi') { $start_h = 7;  $end_h = 12; }
        else                     { $start_h = 13; $end_h = 17; }

        if (!$is_active) {
            $pdo->prepare(
                "DELETE FROM time_slots
                 WHERE doctor_id = ? AND DATE(slot_datetime) = ?
                   AND HOUR(slot_datetime) >= ? AND HOUR(slot_datetime) < ?
                   AND is_booked = 0"
            )->execute([$doctor_id, $date, $start_h, $end_h]);
            setFlash('success', 'Sesi dinonaktifkan.');
        } else {
            generateSlots($pdo, $doctor_id, $date, $session, $max_slots);
            setFlash('success', 'Sesi berhasil diperbarui.');
        }

        redirect(BASE_URL . '/admin/slots.php?date=' . $date);

    } elseif ($action === 'activate_all') {
        $date = $_POST['date'] ?? '';
        if (!$date) { redirect(BASE_URL . '/admin/slots.php'); }

        $doctors_list = $pdo->query(
            "SELECT id FROM doctors WHERE is_active=1 AND deleted_at IS NULL"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($doctors_list as $did) {
            generateSlots($pdo, $did, $date, 'pagi',  5);
            generateSlots($pdo, $did, $date, 'siang', 5);
        }

        setFlash('success', 'Semua sesi diaktifkan dengan 5 slot.');
        redirect(BASE_URL . '/admin/slots.php?date=' . $date);
    }

    redirect(BASE_URL . '/admin/slots.php');
}

require_once '../includes/layout_admin.php';

$doctors = $pdo->query(
    "SELECT id, full_name, specialization FROM doctors WHERE is_active=1 AND deleted_at IS NULL ORDER BY full_name"
)->fetchAll();

$selectedDate = (isset($_GET['date']) && $_GET['date'] !== '') ? $_GET['date'] : date('Y-m-d');
$isPastDate   = $selectedDate < date('Y-m-d');
$filterDoctor = (int)($_GET['doctor_id'] ?? 0);

$docWhere = $filterDoctor ? "AND d.id = $filterDoctor" : "";
$sql = "
    SELECT
        d.id AS doctor_id,
        d.full_name AS doctor_name,
        d.specialization,
        sesi_list.sesi,
        COUNT(ts.id) AS total_slots,
        COALESCE(SUM(ts.is_booked), 0) AS booked_slots,
        MIN(TIME(ts.slot_datetime)) AS start_time,
        MAX(TIME(ts.slot_datetime)) AS end_time
    FROM doctors d
    CROSS JOIN (SELECT 'pagi' AS sesi UNION ALL SELECT 'siang') AS sesi_list
    LEFT JOIN time_slots ts
        ON ts.doctor_id = d.id
        AND DATE(ts.slot_datetime) = ?
        AND (
            (sesi_list.sesi = 'pagi'  AND HOUR(ts.slot_datetime) >= 7  AND HOUR(ts.slot_datetime) < 12)
         OR (sesi_list.sesi = 'siang' AND HOUR(ts.slot_datetime) >= 13 AND HOUR(ts.slot_datetime) < 17)
        )
    WHERE d.is_active = 1 AND d.deleted_at IS NULL $docWhere
    GROUP BY d.id, d.full_name, d.specialization, sesi_list.sesi
    ORDER BY d.full_name, sesi_list.sesi
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$selectedDate]);
$rows = $stmt->fetchAll();

$detailData = [];
$dSql = "
    SELECT
        d.id AS doctor_id,
        CASE
            WHEN HOUR(ts.slot_datetime) >= 7  AND HOUR(ts.slot_datetime) < 12 THEN 'pagi'
            WHEN HOUR(ts.slot_datetime) >= 13 AND HOUR(ts.slot_datetime) < 17 THEN 'siang'
        END AS sesi,
        p.full_name AS patient_name,
        p.phone,
        a.notes,
        a.status,
        TIME(ts.slot_datetime) AS waktu,
        (
            SELECT COUNT(*) FROM time_slots ts3
            WHERE ts3.doctor_id = ts.doctor_id
              AND DATE(ts3.slot_datetime) = DATE(ts.slot_datetime)
              AND HOUR(ts3.slot_datetime) >= IF(HOUR(ts.slot_datetime) >= 13, 13, 7)
              AND HOUR(ts3.slot_datetime) <  IF(HOUR(ts.slot_datetime) >= 13, 17, 12)
              AND ts3.slot_datetime <= ts.slot_datetime
        ) AS queue_number
    FROM appointments a
    JOIN time_slots ts ON ts.id = a.slot_id
    JOIN doctors d     ON d.id  = ts.doctor_id
    JOIN patients p    ON p.id  = a.patient_id
    WHERE DATE(ts.slot_datetime) = ?
      AND a.status != 'cancelled'
";
$dParams = [$selectedDate];
if ($filterDoctor) { $dSql .= " AND d.id = ?"; $dParams[] = $filterDoctor; }
$dSql .= " ORDER BY ts.slot_datetime";
$dStmt = $pdo->prepare($dSql);
$dStmt->execute($dParams);
foreach ($dStmt->fetchAll() as $r) {
    $detailData[$r['doctor_id']][$r['sesi']][] = $r;
}
?>

<style>
.filter-bar { background:#fff; border-radius:1rem; padding:1rem 1.25rem; box-shadow:0 1px 6px rgba(0,0,0,.07); margin-bottom:1.5rem; }
.date-label { font-size:.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.slot-table th { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; border-bottom:2px solid #f1f5f9; padding:.6rem 1rem; }
.slot-table td { padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f8fafc; }
.slot-table tbody tr:hover { background:#f8faff; }
.sesi-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; padding:.25em .7em; border-radius:.4rem; }
.sesi-pagi  { background:#fef9c3; color:#a16207; }
.sesi-siang { background:#dbeafe; color:#1d4ed8; }
.slot-bar-wrap { display:flex; align-items:center; gap:.6rem; }
.slot-bar { height:6px; border-radius:3px; background:#e2e8f0; flex:1; max-width:80px; overflow:hidden; }
.slot-bar-fill { height:100%; border-radius:3px; background:#3b69ff; transition:width .3s; }
.slot-bar-fill.danger { background:#ef4444; }
.slot-text { font-size:.82rem; font-weight:700; color:#1e293b; white-space:nowrap; }
.btn-action { border:1.5px solid #e2e8f0; background:#fff; border-radius:.5rem; padding:.3rem .65rem; font-size:.78rem; font-weight:600; transition:all .15s; cursor:pointer; }
.btn-action:hover { border-color:#3b69ff; color:#3b69ff; background:#f0f4ff; }
.btn-action.detail { border-color:#e2e8f0; color:#64748b; }
.btn-action.detail:hover { border-color:#10b981; color:#10b981; background:#f0fdf4; }
.admin-overlay { position:fixed; inset:0; background:rgba(0,0,0,0); z-index:1050; display:flex; align-items:center; justify-content:center; padding:1rem; transition:background .25s; pointer-events:none; opacity:0; }
.admin-overlay.show { background:rgba(0,0,0,.45); pointer-events:all; opacity:1; }
.admin-modal { background:#fff; border-radius:1.25rem; width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,.18); transform:translateY(20px) scale(.96); opacity:0; transition:transform .3s cubic-bezier(.34,1.56,.64,1), opacity .25s; max-height:85vh; overflow-y:auto; }
.admin-modal.show { transform:translateY(0) scale(1); opacity:1; }
.admin-modal-header { padding:1.1rem 1.4rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:#fff; z-index:1; }
.admin-modal-body { padding:1.25rem 1.4rem; }
.toggle-row { display:flex; align-items:center; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1f5f9; margin-bottom:.75rem; }
.form-switch-lg .form-check-input { width:2.5em; height:1.3em; cursor:pointer; }
.patient-item { border:1px solid #f1f5f9; border-radius:.75rem; padding:.75rem 1rem; margin-bottom:.6rem; }
.patient-item:last-child { margin-bottom:0; }
.queue-num { width:28px; height:28px; border-radius:50%; background:#3b69ff; color:#fff; font-size:.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
.empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; }
</style>

<!-- Filter Bar -->
<div class="filter-bar d-flex align-items-end gap-3 flex-wrap">
    <div>
        <div class="date-label mb-1">Tanggal</div>
        <input type="date" id="dateInput" class="form-control form-control-sm"
               value="<?= htmlspecialchars($selectedDate) ?>"
               onchange="gotoDate(this.value)">
    </div>
    <div>
        <div class="date-label mb-1">Filter Dokter</div>
        <select id="doctorFilter" class="form-select form-select-sm" onchange="gotoDate()">
            <option value="">Semua Dokter</option>
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $filterDoctor == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['full_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="ms-auto text-end">
        <div class="date-label"><?= (new DateTime($selectedDate))->format('l, d F Y') ?></div>
        <div class="fw-bold <?= $isPastDate ? 'text-secondary' : 'text-primary' ?>" style="font-size:1.05rem;">
            <?php if ($isPastDate): ?>
            <i class="bi bi-lock-fill me-1" style="font-size:.85rem;"></i>Mode Lihat Saja
            <?php else: ?>
            <?= count(array_filter($rows, fn($r) => (int)$r['total_slots'] > 0)) ?> sesi aktif
            <?php endif; ?>
        </div>
        <?php if (!$isPastDate): ?>
        <button class="btn btn-sm btn-success mt-1" onclick="confirmActivateAll()">
            <i class="bi bi-lightning-fill me-1"></i>Aktifkan Semua
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table slot-table mb-0">
            <thead>
                <tr><th>#</th><th>Dokter</th><th>Sesi</th><th>Slot</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if ($rows): ?>
            <?php foreach ($rows as $i => $r):
                $booked = (int)$r['booked_slots'];
                $total  = (int)$r['total_slots'];
                $active = $total > 0;
                $pct    = $total > 0 ? round($booked / $total * 100) : 0;
                $isFull = $active && $booked >= $total;
                $detailList = $detailData[$r['doctor_id']][$r['sesi']] ?? [];
            ?>
            <tr <?= !$active ? 'style="opacity:.55;"' : '' ?>>
                <td class="text-muted small"><?= $i+1 ?></td>
                <td>
                    <div class="fw-semibold" style="font-size:.88rem;"><?= htmlspecialchars($r['doctor_name']) ?></div>
                    <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($r['specialization']) ?></div>
                </td>
                <td>
                    <span class="sesi-badge <?= $r['sesi'] === 'pagi' ? 'sesi-pagi' : 'sesi-siang' ?>">
                        <i class="bi <?= $r['sesi'] === 'pagi' ? 'bi-sun' : 'bi-cloud-sun' ?>"></i>
                        <?= ucfirst($r['sesi']) ?>
                    </span>
                    <?php if ($active): ?>
                    <div class="text-muted mt-1" style="font-size:.72rem;">
                        <?= date('H:i', strtotime($r['start_time'])) ?> – <?= date('H:i', strtotime($r['end_time'])) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($active): ?>
                    <div class="slot-bar-wrap">
                        <div class="slot-bar">
                            <div class="slot-bar-fill <?= $isFull ? 'danger' : '' ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="slot-text"><?= $booked ?>/<?= $total ?></span>
                    </div>
                    <?php else: ?>
                    <span class="text-muted small">–</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$active): ?>
                    <span class="badge bg-secondary rounded-pill">Nonaktif</span>
                    <?php elseif ($isFull): ?>
                    <span class="badge bg-danger rounded-pill">Penuh</span>
                    <?php elseif ($booked > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill">Sebagian</span>
                    <?php else: ?>
                    <span class="badge bg-success rounded-pill">Tersedia</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1 align-items-center">
                        <?php if ($isPastDate): ?>
                        <span class="text-muted small fst-italic"><i class="bi bi-lock me-1"></i>View only</span>
                        <?php else: ?>
                        <button class="btn-action"
                            onclick='openEdit(<?= $r["doctor_id"] ?>, <?= json_encode($r["doctor_name"]) ?>, <?= json_encode($r["sesi"]) ?>, <?= $total ?>)'>
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <?php endif; ?>
                        <button class="btn-action detail"
                                <?= !$active ? 'disabled style="opacity:.4;cursor:not-allowed;"' : '' ?>
                                onclick='<?= $active ? "openDetail(".json_encode($r["doctor_name"]).", ".json_encode($r["sesi"]).", ".json_encode($detailList).")" : "" ?>'>
                            <i class="bi bi-people me-1"></i>Detail
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>Tidak ada dokter aktif.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPastDate): ?>
<form method="post" id="activateAllForm" style="display:none;">
    <input type="hidden" name="action" value="activate_all">
    <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
</form>
<?php endif; ?>

<!-- Modal Edit -->
<div class="admin-overlay" id="editOverlay">
    <div class="admin-modal" id="editModal">
        <div class="admin-modal-header">
            <div>
                <h6 class="fw-bold mb-0" id="editTitle">Edit Sesi</h6>
                <small class="text-muted" id="editSubtitle"></small>
            </div>
            <button class="btn btn-sm btn-outline-secondary rounded-circle"
                    onclick="closeEdit()" style="width:32px;height:32px;padding:0;">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="admin-modal-body">
            <form method="post" id="editForm">
                <input type="hidden" name="action" value="edit_session">
                <input type="hidden" name="date" id="edit_date">
                <input type="hidden" name="doctor_id" id="edit_doctor_id">
                <input type="hidden" name="session" id="edit_session">
                <div class="toggle-row">
                    <div>
                        <div class="fw-semibold small">Aktifkan Sesi</div>
                        <div class="text-muted" style="font-size:.75rem;">Sesi akan tersedia untuk pasien</div>
                    </div>
                    <div class="form-check form-switch form-switch-lg mb-0">
                        <input class="form-check-input" type="checkbox" id="toggleAktif" name="is_active" value="1"
                               onchange="toggleSlotInput(this.checked)">
                    </div>
                </div>
                <div id="slotCountWrap" style="display:none;">
                    <label class="form-label small fw-semibold mt-2">Jumlah Slot (maks 5)</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" name="max_slots" id="slotRange" min="1" max="5" value="5"
                               class="form-range flex-1" oninput="document.getElementById('slotVal').textContent=this.value">
                        <span class="fw-bold text-primary" id="slotVal" style="min-width:20px;">5</span>
                        <span class="text-muted small">slot</span>
                    </div>
                    <div class="text-muted" style="font-size:.72rem;">Slot dibuat otomatis mulai jam awal sesi, interval 30 menit</div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i>Simpan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="admin-overlay" id="detailOverlay">
    <div class="admin-modal" id="detailModal">
        <div class="admin-modal-header">
            <div>
                <h6 class="fw-bold mb-0" id="detailTitle">Detail Sesi</h6>
                <small class="text-muted" id="detailSubtitle"></small>
            </div>
            <button class="btn btn-sm btn-outline-secondary rounded-circle"
                    onclick="closeDetail()" style="width:32px;height:32px;padding:0;">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="admin-modal-body" id="detailBody"></div>
    </div>
</div>

<script>
const PAGE_DATE = '<?= $selectedDate ?>';

function confirmActivateAll() {
    if (confirm('Aktifkan semua sesi (Pagi & Siang) untuk semua dokter pada ' + PAGE_DATE + ' dengan 5 slot masing-masing?\n\nSlot kosong yang ada akan di-reset.')) {
        document.getElementById('activateAllForm').submit();
    }
}

function gotoDate(date) {
    const d = date || document.getElementById('dateInput').value;
    const doc = document.getElementById('doctorFilter').value;
    let url = '?date=' + d;
    if (doc) url += '&doctor_id=' + doc;
    window.location.href = url;
}

function openEdit(doctorId, doctorName, sesi, currentSlots) {
    document.getElementById('editTitle').textContent    = 'Edit Sesi';
    document.getElementById('editSubtitle').textContent = doctorName + ' – ' + ucfirst(sesi);
    document.getElementById('edit_date').value      = PAGE_DATE;
    document.getElementById('edit_doctor_id').value = doctorId;
    document.getElementById('edit_session').value   = sesi;
    const toggle = document.getElementById('toggleAktif');
    toggle.checked = currentSlots > 0;
    toggleSlotInput(toggle.checked);
    const range = document.getElementById('slotRange');
    range.value = currentSlots > 0 ? Math.min(currentSlots, 5) : 5;
    document.getElementById('slotVal').textContent = range.value;
    showModal('editOverlay', 'editModal');
}

function toggleSlotInput(checked) {
    document.getElementById('slotCountWrap').style.display = checked ? '' : 'none';
}
function closeEdit()   { hideModal('editOverlay',   'editModal');   }
function closeDetail() { hideModal('detailOverlay', 'detailModal'); }

function openDetail(doctorName, sesi, patients) {
    document.getElementById('detailTitle').textContent    = 'Detail Sesi ' + ucfirst(sesi);
    document.getElementById('detailSubtitle').textContent = doctorName + ' – ' + PAGE_DATE;
    const body = document.getElementById('detailBody');
    if (!patients || patients.length === 0) {
        body.innerHTML = '<div class="empty-state"><i class="bi bi-person-x"></i>Belum ada pasien di sesi ini.</div>';
    } else {
        body.innerHTML = patients.map(p => `
            <div class="patient-item d-flex gap-3 align-items-start">
                <div class="queue-num">${p.queue_number}</div>
                <div class="flex-1">
                    <div class="fw-semibold" style="font-size:.88rem;">${escHtml(p.patient_name)}</div>
                    <div class="text-muted small">${escHtml(p.phone || '–')} &bull; ${p.waktu}</div>
                    ${p.notes ? '<div class="mt-1 small text-secondary">"' + escHtml(p.notes) + '"</div>' : ''}
                    <span class="badge mt-1 ${p.status === 'completed' ? 'bg-success' : 'bg-primary'} rounded-pill" style="font-size:.68rem;">${ucfirst(p.status)}</span>
                </div>
            </div>`).join('');
    }
    showModal('detailOverlay', 'detailModal');
}

function showModal(overlayId, modalId) {
    document.getElementById(overlayId).classList.add('show');
    requestAnimationFrame(() => requestAnimationFrame(() =>
        document.getElementById(modalId).classList.add('show')
    ));
}
function hideModal(overlayId, modalId) {
    document.getElementById(modalId).classList.remove('show');
    document.getElementById(overlayId).classList.remove('show');
}
function ucfirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('editOverlay').addEventListener('click',   e => { if (e.target === document.getElementById('editOverlay'))   closeEdit();   });
document.getElementById('detailOverlay').addEventListener('click', e => { if (e.target === document.getElementById('detailOverlay')) closeDetail(); });
</script>

<?php require_once '../includes/layout_admin_end.php'; ?>
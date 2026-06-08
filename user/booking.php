<?php
require_once '../config/config.php';
$pageTitle = 'Buat Janji Temu';

// ── POST: proses booking ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('patient');
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $notes   = trim($_POST['notes'] ?? '');

    if (!$slot_id) {
        setFlash('error', 'Pilih slot waktu terlebih dahulu.');
        redirect(BASE_URL . '/user/booking.php');
    }

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
        $pdo->prepare("INSERT INTO appointments (patient_id, slot_id, notes) VALUES (?,?,?)")
            ->execute([$_SESSION['user_id'], $slot_id, $notes]);
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

// ── Tanggal yang dipilih ──────────────────────────────────────────────────────
$today        = new DateTime();
$selectedDate = $_GET['date'] ?? $today->format('Y-m-d');
$minDate      = $today->format('Y-m-d');
$maxDate      = (new DateTime('+6 days'))->format('Y-m-d');
if ($selectedDate < $minDate || $selectedDate > $maxDate) $selectedDate = $minDate;

// ── Spesialisasi yang ditampilkan (urut tetap) ────────────────────────────────
$displaySpecs = ['Dokter Umum', 'Dokter Mata', 'Dokter Gigi'];

$specIcons = [
    'Dokter Umum' => 'bi-person-heart',
    'Dokter Mata' => 'bi-eye',
    'Dokter Gigi' => 'bi-emoji-smile',
];

// ── Untuk setiap spesialisasi ambil slot kosong di tanggal terpilih ───────────
// Dokter Umum: 2 dokter → dokter pertama sesi pagi, dokter kedua sesi siang
// Dokter Mata & Gigi: 1 dokter → sesi bebas (random/sesuai slot yang ada)

$specData   = []; // data slot untuk JS
$specStatus = []; // status pagi/siang untuk badge kartu

foreach ($displaySpecs as $spec) {
    // Ambil dokter aktif untuk spesialisasi ini
    $dStmt = $pdo->prepare(
        "SELECT id, full_name FROM doctors
         WHERE specialization = ? AND is_active = 1 AND deleted_at IS NULL
         ORDER BY id"
    );
    $dStmt->execute([$spec]);
    $doctors = $dStmt->fetchAll();

    $pagiSlots  = [];
    $siangSlots = [];

    if ($spec === 'Dokter Umum' && count($doctors) >= 2) {
        // Dokter pertama → pagi, dokter kedua → siang
        $dokterPagi  = $doctors[0];
        $dokterSiang = $doctors[1];

        foreach ([
            ['doc' => $dokterPagi,  'min' => 7,  'max' => 12, 'target' => &$pagiSlots],
            ['doc' => $dokterSiang, 'min' => 13, 'max' => 17, 'target' => &$siangSlots],
        ] as $cfg) {
            $sStmt = $pdo->prepare(
                "SELECT ts.id, ts.slot_datetime, ts.duration_minutes
                 FROM time_slots ts
                 WHERE ts.doctor_id = ? AND DATE(ts.slot_datetime) = ?
                   AND ts.is_booked = 0 AND ts.slot_datetime > NOW()
                   AND HOUR(ts.slot_datetime) >= ? AND HOUR(ts.slot_datetime) < ?
                 ORDER BY ts.slot_datetime"
            );
            $sStmt->execute([$cfg['doc']['id'], $selectedDate, $cfg['min'], $cfg['max']]);
            foreach ($sStmt->fetchAll() as $row) {
                $cfg['target'][] = [
                    'id'       => $row['id'],
                    'time'     => date('H:i', strtotime($row['slot_datetime'])),
                    'duration' => $row['duration_minutes'],
                    'doctor'   => $cfg['doc']['full_name'],
                ];
            }
        }
    } else {
        // Dokter Mata / Gigi: ambil semua slot dari semua dokter spesialisasi ini
        // lalu bagi berdasarkan jam
        foreach ($doctors as $doc) {
            $sStmt = $pdo->prepare(
                "SELECT ts.id, ts.slot_datetime, ts.duration_minutes
                 FROM time_slots ts
                 WHERE ts.doctor_id = ? AND DATE(ts.slot_datetime) = ?
                   AND ts.is_booked = 0 AND ts.slot_datetime > NOW()
                 ORDER BY ts.slot_datetime"
            );
            $sStmt->execute([$doc['id'], $selectedDate]);
            foreach ($sStmt->fetchAll() as $row) {
                $h = (int)date('H', strtotime($row['slot_datetime']));
                $entry = [
                    'id'       => $row['id'],
                    'time'     => date('H:i', strtotime($row['slot_datetime'])),
                    'duration' => $row['duration_minutes'],
                    'doctor'   => $doc['full_name'],
                ];
                if ($h >= 7  && $h < 12) $pagiSlots[]  = $entry;
                if ($h >= 13 && $h < 17) $siangSlots[] = $entry;
            }
        }
    }

    $specData[$spec] = ['pagi' => $pagiSlots, 'siang' => $siangSlots];
    $specStatus[$spec] = [
        'pagi'  => count($pagiSlots)  > 0,
        'siang' => count($siangSlots) > 0,
    ];
}
?>

<style>
.date-strip { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.25rem; }
.date-strip::-webkit-scrollbar { height:4px; }
.date-strip::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:2px; }
.date-btn {
    flex-shrink:0; min-width:64px; padding:.5rem .75rem;
    border:2px solid #e2e8f0; border-radius:.75rem; background:#fff;
    text-align:center; cursor:pointer; transition:all .2s; text-decoration:none; color:#475569;
}
.date-btn:hover { border-color:#3b69ff; color:#3b69ff; }
.date-btn.active { background:#3b69ff; border-color:#3b69ff; color:#fff; }
.date-btn .day-name { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.date-btn .day-num  { font-size:1.2rem; font-weight:700; line-height:1.2; }
.date-btn .month    { font-size:.7rem; }

.spec-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }
@media(max-width:576px){ .spec-grid { grid-template-columns:repeat(1,1fr); } }

.spec-card {
    border:2px solid #e2e8f0; border-radius:1rem; padding:1.5rem 1rem;
    text-align:center; cursor:pointer; background:#fff;
    transition:all .2s; position:relative;
}
.spec-card:hover:not(.disabled) {
    border-color:#3b69ff; transform:translateY(-3px);
    box-shadow:0 6px 20px rgba(59,105,255,.15);
}
.spec-card.disabled { background:#f8fafc; border-color:#e2e8f0; cursor:not-allowed; opacity:.5; }
.spec-card .spec-icon { font-size:2.5rem; margin-bottom:.6rem; }
.spec-card .spec-name { font-size:.9rem; font-weight:700; color:#334155; }
.spec-card .spec-badges { display:flex; gap:.35rem; justify-content:center; margin-top:.5rem; flex-wrap:wrap; }
.spec-badge { font-size:.65rem; padding:.2em .6em; border-radius:.3rem; font-weight:600; }
.spec-badge.available { background:#dcfce7; color:#15803d; }
.spec-badge.full      { background:#fee2e2; color:#b91c1c; }

.booking-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,0); z-index:1050;
    display:flex; align-items:center; justify-content:center; padding:1rem;
    transition:background .3s; pointer-events:none; opacity:0;
}
.booking-overlay.show { background:rgba(0,0,0,.45); pointer-events:all; opacity:1; }
.booking-modal {
    background:#fff; border-radius:1.25rem; width:100%; max-width:500px;
    max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2);
    transform:scale(.85) translateY(30px); opacity:0;
    transition:transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
}
.booking-modal.show { transform:scale(1) translateY(0); opacity:1; }
.modal-header-custom {
    padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; background:#fff; z-index:1;
}
.modal-body-custom { padding:1.25rem 1.5rem; }

.session-tab {
    border:2px solid #e2e8f0; border-radius:.75rem; padding:.85rem 1rem;
    cursor:pointer; transition:all .2s; margin-bottom:.75rem;
    display:flex; align-items:center; justify-content:space-between;
}
.session-tab:hover:not(.disabled) { border-color:#3b69ff; background:#f0f4ff; }
.session-tab.disabled { opacity:.45; cursor:not-allowed; }
.session-tab.selected { border-color:#3b69ff; background:#eff3ff; }

.slot-grid-modal { display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-top:.75rem; }
.slot-item {
    border:2px solid #e2e8f0; border-radius:.625rem; padding:.5rem;
    text-align:center; cursor:pointer; transition:all .15s; background:#fff;
}
.slot-item:hover { border-color:#3b69ff; background:#f0f4ff; }
.slot-item.selected { border-color:#3b69ff; background:#3b69ff; color:#fff; }
.slot-item .slot-time { font-weight:700; font-size:.9rem; }
.slot-item .slot-dur  { font-size:.7rem; opacity:.75; }
</style>

<!-- Date Strip -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body pb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 me-1"></i>Pilih Tanggal</h6>
        <div class="date-strip">
            <?php
            $days   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
            $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
            for ($i = 0; $i < 7; $i++):
                $d    = new DateTime("+$i days");
                $dStr = $d->format('Y-m-d');
                $isActive = $dStr === $selectedDate;
            ?>
            <a href="?date=<?= $dStr ?>" class="date-btn <?= $isActive ? 'active' : '' ?>">
                <div class="day-name"><?= $days[(int)$d->format('w')] ?></div>
                <div class="day-num"><?= $d->format('d') ?></div>
                <div class="month"><?= $months[(int)$d->format('n')-1] ?></div>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Specialization Cards -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-grid me-1"></i>Pilih Spesialisasi</h6>
        <div class="spec-grid">
            <?php foreach ($displaySpecs as $spec):
                $st       = $specStatus[$spec];
                $bothFull = !$st['pagi'] && !$st['siang'];
                $icon     = $specIcons[$spec] ?? 'bi-hospital';
                $jsonKey  = json_encode($spec);
            ?>
            <div class="spec-card <?= $bothFull ? 'disabled' : '' ?>"
                 <?= !$bothFull ? "onclick=\"openSpecModal($jsonKey)\"" : '' ?>>
                <div class="spec-icon <?= $bothFull ? 'text-secondary' : 'text-primary' ?>">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div class="spec-name"><?= htmlspecialchars($spec) ?></div>
                <div class="spec-badges">
                    <span class="spec-badge <?= $st['pagi'] ? 'available' : 'full' ?>">
                        <i class="bi bi-sun me-1"></i>Pagi
                    </span>
                    <span class="spec-badge <?= $st['siang'] ? 'available' : 'full' ?>">
                        <i class="bi bi-cloud-sun me-1"></i>Siang
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="booking-overlay" id="bookingOverlay">
    <div class="booking-modal" id="bookingModal">

        <!-- Step 1: Pilih Sesi -->
        <div id="modalStep1">
            <div class="modal-header-custom">
                <div>
                    <h6 class="fw-bold mb-0" id="modalSpecTitle"></h6>
                    <small class="text-muted" id="modalDateLabel"></small>
                </div>
                <button class="btn btn-sm btn-outline-secondary rounded-circle"
                        onclick="closeModal()" style="width:32px;height:32px;padding:0;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body-custom">
                <p class="text-muted small mb-3">Pilih sesi konsultasi</p>
                <div class="session-tab" id="tabPagi" onclick="selectSession('pagi')">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-sun me-2 text-warning"></i>Sesi Pagi</div>
                        <div class="text-muted small">07:00 – 12:00</div>
                    </div>
                    <span class="badge" id="badgePagi">–</span>
                </div>
                <div class="session-tab" id="tabSiang" onclick="selectSession('siang')">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-cloud-sun me-2 text-info"></i>Sesi Siang</div>
                        <div class="text-muted small">13:00 – 17:00</div>
                    </div>
                    <span class="badge" id="badgeSiang">–</span>
                </div>
                <div id="slotList" style="display:none;">
                    <p class="text-muted small mb-2 mt-2">Pilih slot waktu:</p>
                    <div class="slot-grid-modal" id="slotGrid"></div>
                </div>
            </div>
        </div>

        <!-- Step 2: Konfirmasi -->
        <div id="modalStep2" style="display:none;">
            <div class="modal-header-custom">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="backToStep1()">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <span class="fw-bold">Konfirmasi Booking</span>
                </div>
                <button class="btn btn-sm btn-outline-secondary rounded-circle"
                        onclick="closeModal()" style="width:32px;height:32px;padding:0;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body-custom">
                <div class="bg-light rounded-3 p-3 mb-3" style="font-size:.9rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Spesialisasi</span>
                        <span class="fw-semibold" id="conf_spec">–</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Dokter</span>
                        <span class="fw-semibold" id="conf_doctor">–</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tanggal</span>
                        <span class="fw-semibold" id="conf_date">–</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Waktu</span>
                        <span class="fw-semibold" id="conf_time">–</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Durasi</span>
                        <span class="fw-semibold" id="conf_duration">–</span>
                    </div>
                </div>
                <form method="post" id="bookingForm">
                    <input type="hidden" name="slot_id" id="hiddenSlotId">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Keluhan atau keterangan tambahan…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-calendar-check me-1"></i> Konfirmasi Booking
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
const allSlots   = <?= json_encode($specData) ?>;
const dateLabel  = '<?= (new DateTime($selectedDate))->format('d M Y') ?>';
let currentSpec  = null;

function openSpecModal(spec) {
    currentSpec = spec;
    document.getElementById('modalSpecTitle').textContent = spec;
    document.getElementById('modalDateLabel').textContent = dateLabel;

    document.getElementById('modalStep1').style.display = '';
    document.getElementById('modalStep2').style.display = 'none';
    document.getElementById('slotList').style.display   = 'none';

    const data = allSlots[spec] || {pagi:[], siang:[]};
    setBadge('badgePagi',  data.pagi.length);
    setBadge('badgeSiang', data.siang.length);

    document.getElementById('tabPagi').classList.toggle('disabled',  data.pagi.length  === 0);
    document.getElementById('tabSiang').classList.toggle('disabled', data.siang.length === 0);
    document.getElementById('tabPagi').classList.remove('selected');
    document.getElementById('tabSiang').classList.remove('selected');

    const overlay = document.getElementById('bookingOverlay');
    const modal   = document.getElementById('bookingModal');
    overlay.classList.add('show');
    requestAnimationFrame(() => requestAnimationFrame(() => modal.classList.add('show')));
}

function setBadge(id, count) {
    const el = document.getElementById(id);
    el.textContent = count > 0 ? count + ' slot' : 'Penuh';
    el.className   = 'badge ' + (count > 0 ? 'bg-success' : 'bg-danger');
}

function selectSession(sesi) {
    const data = allSlots[currentSpec] || {pagi:[], siang:[]};
    if (data[sesi].length === 0) return;

    document.getElementById('tabPagi').classList.toggle('selected',  sesi === 'pagi');
    document.getElementById('tabSiang').classList.toggle('selected', sesi === 'siang');

    const grid = document.getElementById('slotGrid');
    grid.innerHTML = '';
    data[sesi].forEach(s => {
        const div = document.createElement('div');
        div.className = 'slot-item';
        div.innerHTML = `<div class="slot-time">${s.time}</div><div class="slot-dur">${s.duration} mnt</div>`;
        div.onclick = () => selectSlot(div, s);
        grid.appendChild(div);
    });
    document.getElementById('slotList').style.display = '';
}

function selectSlot(el, slot) {
    document.querySelectorAll('.slot-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    setTimeout(() => {
        document.getElementById('conf_spec').textContent     = currentSpec;
        document.getElementById('conf_doctor').textContent   = slot.doctor;
        document.getElementById('conf_date').textContent     = dateLabel;
        document.getElementById('conf_time').textContent     = slot.time;
        document.getElementById('conf_duration').textContent = slot.duration + ' menit';
        document.getElementById('hiddenSlotId').value        = slot.id;
        document.getElementById('modalStep1').style.display  = 'none';
        document.getElementById('modalStep2').style.display  = '';
    }, 180);
}

function backToStep1() {
    document.getElementById('modalStep1').style.display = '';
    document.getElementById('modalStep2').style.display = 'none';
}

function closeModal() {
    const overlay = document.getElementById('bookingOverlay');
    const modal   = document.getElementById('bookingModal');
    modal.classList.remove('show');
    overlay.classList.remove('show');
}

document.getElementById('bookingOverlay').addEventListener('click', (e) => {
    if (e.target === document.getElementById('bookingOverlay')) closeModal();
});
</script>

<?php require_once '../includes/layout_user_end.php'; ?>
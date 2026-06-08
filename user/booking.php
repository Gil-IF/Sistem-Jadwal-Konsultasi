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

// ── Tanggal yang dipilih (default hari ini) ───────────────────────────────────
$today       = new DateTime();
$selectedDate = $_GET['date'] ?? $today->format('Y-m-d');

// Validasi: hanya boleh 7 hari ke depan dari hari ini
$minDate = $today->format('Y-m-d');
$maxDate = (new DateTime('+6 days'))->format('Y-m-d');
if ($selectedDate < $minDate || $selectedDate > $maxDate) {
    $selectedDate = $minDate;
}

// ── Ambil semua spesialisasi unik ─────────────────────────────────────────────
$specList = $pdo->query(
    "SELECT DISTINCT specialization FROM doctors WHERE is_active=1 AND deleted_at IS NULL
     ORDER BY specialization"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Untuk setiap spesialisasi, cek status slot di tanggal terpilih ────────────
// Sesi pagi: 07:00–11:59 | Sesi siang: 13:00–16:59
$specStatus = []; // ['pagi' => bool_ada_slot_kosong, 'siang' => bool_ada_slot_kosong]

foreach ($specList as $spec) {
    $stmt = $pdo->prepare(
        "SELECT ts.id, ts.is_booked, TIME(ts.slot_datetime) AS waktu
         FROM time_slots ts
         JOIN doctors d ON d.id = ts.doctor_id
         WHERE d.specialization = ? AND d.is_active = 1 AND d.deleted_at IS NULL
           AND DATE(ts.slot_datetime) = ?
         ORDER BY ts.slot_datetime"
    );
    $stmt->execute([$spec, $selectedDate]);
    $slots = $stmt->fetchAll();

    $pagiAda   = false;
    $siangAda  = false;
    foreach ($slots as $s) {
        $h = (int)substr($s['waktu'], 0, 2);
        if ($h >= 7 && $h < 12 && !$s['is_booked'])  $pagiAda  = true;
        if ($h >= 13 && $h < 17 && !$s['is_booked']) $siangAda = true;
    }
    $specStatus[$spec] = ['pagi' => $pagiAda, 'siang' => $siangAda];
}

// ── Ikon per spesialisasi ─────────────────────────────────────────────────────
$specIcons = [
    'Dokter Umum'            => 'bi-person-heart',
    'Dokter Mata'            => 'bi-eye',
    'Dokter Anak'            => 'bi-balloon-heart',
    'Dokter Gigi'            => 'bi-emoji-smile',
    'Dokter Penyakit Dalam'  => 'bi-lungs',
    'Dokter Jantung'         => 'bi-heart-pulse',
    'Dokter Saraf'           => 'bi-lightning',
    'Dokter THT'             => 'bi-ear',
    'Dokter Kulit dan Kelamin' => 'bi-bandaid',
];

// ── Slot per spesialisasi (untuk modal, dikirim ke JS) ────────────────────────
$allSlotData = [];
foreach ($specList as $spec) {
    // Ambil semua slot (booked maupun tidak) untuk hitung nomor antrian
    $stmt = $pdo->prepare(
        "SELECT ts.id, ts.slot_datetime, ts.duration_minutes, ts.is_booked,
                d.full_name AS doctor_name
         FROM time_slots ts
         JOIN doctors d ON d.id = ts.doctor_id
         WHERE d.specialization = ? AND d.is_active = 1 AND d.deleted_at IS NULL
           AND DATE(ts.slot_datetime) = ?
         ORDER BY ts.slot_datetime"
    );
    $stmt->execute([$spec, $selectedDate]);
    $rows = $stmt->fetchAll();

    $pagi  = [];
    $siang = [];
    $pagiCount  = 0;
    $siangCount = 0;
    foreach ($rows as $r) {
        $h = (int)date('H', strtotime($r['slot_datetime']));
        if ($h >= 7 && $h < 12)  $pagiCount++;
        if ($h >= 13 && $h < 17) $siangCount++;
        if ($r['is_booked']) continue; // hanya slot kosong yang ditampilkan
        $entry = [
            'id'     => $r['id'],
            'time'   => date('H:i', strtotime($r['slot_datetime'])),
            'doctor' => $r['doctor_name'],
        ];
        if ($h >= 7 && $h < 12) {
            $entry['queue'] = $pagiCount;
            $pagi[] = $entry;
        }
        if ($h >= 13 && $h < 17) {
            $entry['queue'] = $siangCount;
            $siang[] = $entry;
        }
    }
    $allSlotData[$spec] = ['pagi' => $pagi, 'siang' => $siang];
}
?>

<style>
/* ── Date Picker Strip ── */
.date-strip { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.25rem; }
.date-strip::-webkit-scrollbar { height:4px; }
.date-strip::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:2px; }

.date-btn {
    flex-shrink:0; min-width:64px; padding:.5rem .75rem;
    border:2px solid #e2e8f0; border-radius:.75rem; background:#fff;
    text-align:center; cursor:pointer; transition:all .2s; text-decoration:none;
    color:#475569;
}
.date-btn:hover { border-color:#3b69ff; color:#3b69ff; }
.date-btn.active { background:#3b69ff; border-color:#3b69ff; color:#fff; }
.date-btn .day-name { font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.date-btn .day-num  { font-size:1.2rem; font-weight:700; line-height:1.2; }
.date-btn .month    { font-size:.7rem; }

/* ── Specialization Cards ── */
.spec-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; }
@media(max-width:576px){ .spec-grid { grid-template-columns:repeat(2,1fr); } }

.spec-card {
    border:2px solid #e2e8f0; border-radius:1rem; padding:1.25rem 1rem;
    text-align:center; cursor:pointer; background:#fff;
    transition:all .2s; position:relative;
}
.spec-card:hover:not(.disabled) { border-color:#3b69ff; transform:translateY(-2px); box-shadow:0 4px 12px rgba(59,105,255,.15); }
.spec-card.disabled { background:#f8fafc; border-color:#e2e8f0; cursor:not-allowed; opacity:.55; }
.spec-card .spec-icon { font-size:2rem; margin-bottom:.5rem; }
.spec-card .spec-name { font-size:.8rem; font-weight:600; color:#334155; }
.spec-card .spec-badges { display:flex; gap:.25rem; justify-content:center; margin-top:.4rem; flex-wrap:wrap; }
.spec-badge { font-size:.65rem; padding:.15em .5em; border-radius:.3rem; font-weight:600; }
.spec-badge.available { background:#dcfce7; color:#15803d; }
.spec-badge.full      { background:#fee2e2; color:#b91c1c; }
.spec-badge.empty     { background:#f1f5f9; color:#94a3b8; }

/* ── Modal Overlay ── */
.booking-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,0); z-index:1050;
    display:flex; align-items:center; justify-content:center; padding:1rem;
    transition:background .3s; pointer-events:none; opacity:0;
}
.booking-overlay.show { background:rgba(0,0,0,.45); pointer-events:all; opacity:1; }

.booking-modal {
    background:#fff; border-radius:1.25rem; width:100%; max-width:520px;
    max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2);
    transform:scale(.85) translateY(30px); opacity:0;
    transition:transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
}
.booking-modal.show { transform:scale(1) translateY(0); opacity:1; }

.modal-header-custom {
    padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;
    display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:#fff; z-index:1;
}
.modal-body-custom { padding:1.25rem 1.5rem; }

/* ── Session Tabs ── */
.session-tab {
    border:2px solid #e2e8f0; border-radius:.75rem; padding:.75rem 1rem;
    cursor:pointer; transition:all .2s; margin-bottom:.75rem;
    display:flex; align-items:center; justify-content:space-between;
}
.session-tab:hover:not(.disabled) { border-color:#3b69ff; background:#f0f4ff; }
.session-tab.disabled { opacity:.5; cursor:not-allowed; }
.session-tab.selected { border-color:#3b69ff; background:#eff3ff; }


</style>

<!-- ── Date Strip ────────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body pb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 me-1"></i>Pilih Tanggal</h6>
        <div class="date-strip">
            <?php
            $days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
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

<!-- ── Specialization Grid ───────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-grid me-1"></i>Pilih Spesialisasi</h6>
        <div class="spec-grid">
            <?php foreach ($specList as $spec):
                $st      = $specStatus[$spec];
                $bothFull = !$st['pagi'] && !$st['siang'];
                $icon    = $specIcons[$spec] ?? 'bi-hospital';
                $jsonKey = htmlspecialchars(json_encode($spec), ENT_QUOTES);
            ?>
            <div class="spec-card <?= $bothFull ? 'disabled' : '' ?>"
                 <?= !$bothFull ? "onclick=\"openSpecModal($jsonKey)\"" : '' ?>>
                <div class="spec-icon text-primary <?= $bothFull ? 'text-secondary' : '' ?>">
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

<!-- ── Modal Pop-up ──────────────────────────────────────────────────────────── -->
<div class="booking-overlay" id="bookingOverlay">
    <div class="booking-modal" id="bookingModal">

        <!-- Step 1: Pilih Sesi -->
        <div id="modalStep1">
            <div class="modal-header-custom">
                <div>
                    <h6 class="fw-bold mb-0" id="modalSpecTitle">Spesialisasi</h6>
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


            </div>
        </div>

        <!-- Step 2: Konfirmasi -->
        <div id="modalStep2" style="display:none;">
            <div class="modal-header-custom">
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2" onclick="backToStep1()">
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
                        <span class="text-muted">No. Antrian</span>
                        <span class="fw-semibold" id="conf_queue">–</span>
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

<!-- Data slot (JSON untuk JS) -->
<script>
const allSlots    = <?= json_encode($allSlotData) ?>;
const selectedDate = '<?= $selectedDate ?>';
const dateLabel   = '<?= (new DateTime($selectedDate))->format('d M Y') ?>';

let currentSpec    = null;
let currentSession = null;
let currentSlot    = null;

function openSpecModal(spec) {
    currentSpec    = spec;
    currentSession = null;
    currentSlot    = null;

    document.getElementById('modalSpecTitle').textContent = spec;
    document.getElementById('modalDateLabel').textContent = dateLabel;

    // Reset step
    document.getElementById('modalStep1').style.display = '';
    document.getElementById('modalStep2').style.display = 'none';

    // Set badge sesi
    const data = allSlots[spec] || {pagi:[], siang:[]};
    setBadge('badgePagi',  data.pagi.length);
    setBadge('badgeSiang', data.siang.length);

    const tabPagi  = document.getElementById('tabPagi');
    const tabSiang = document.getElementById('tabSiang');
    tabPagi.classList.toggle('disabled', data.pagi.length === 0);
    tabSiang.classList.toggle('disabled', data.siang.length === 0);
    tabPagi.classList.remove('selected');
    tabSiang.classList.remove('selected');

    // Buka overlay
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

    currentSession = sesi;

    // Langsung ambil slot pertama yang tersedia di sesi ini
    const slot = data[sesi][0];
    currentSlot = slot;

    document.getElementById('tabPagi').classList.toggle('selected',  sesi === 'pagi');
    document.getElementById('tabSiang').classList.toggle('selected', sesi === 'siang');

    // Langsung pindah ke step 2 konfirmasi
    setTimeout(() => {
        document.getElementById('conf_spec').textContent     = currentSpec;
        document.getElementById('conf_doctor').textContent   = slot.doctor;
        document.getElementById('conf_date').textContent     = dateLabel;
        document.getElementById('conf_time').textContent     = slot.time;
        document.getElementById('conf_queue').textContent    = slot.queue;
        document.getElementById('hiddenSlotId').value        = slot.id;

        document.getElementById('modalStep1').style.display = 'none';
        document.getElementById('modalStep2').style.display = '';
    }, 150);
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

// Tutup klik di luar modal
document.getElementById('bookingOverlay').addEventListener('click', (e) => {
    if (e.target === document.getElementById('bookingOverlay')) closeModal();
});
</script>

<?php require_once '../includes/layout_user_end.php'; ?>
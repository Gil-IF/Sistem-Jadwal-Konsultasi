<?php
// Dipanggil di awal setiap halaman admin.
// Variabel yang diharapkan: $pageTitle (string)
requireLogin('admin');
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> – Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <span>🏥</span> Klinik Admin
    </div>
    <ul class="sidebar-nav nav flex-column mt-2">
        <li class="nav-section">Menu Utama</li>
        <li><a href="<?= BASE_URL ?>/admin/index.php"
               class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a></li>
        <li class="nav-section">Manajemen</li>
        <li><a href="<?= BASE_URL ?>/admin/doctors.php"
               class="nav-link <?= $currentPage === 'doctors.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> Dokter
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/slots.php"
               class="nav-link <?= $currentPage === 'slots.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i> Slot Waktu
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/appointments.php"
               class="nav-link <?= $currentPage === 'appointments.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check"></i> Janji Temu
        </a></li>
        <li><a href="<?= BASE_URL ?>/admin/patients.php"
               class="nav-link <?= $currentPage === 'patients.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Pasien
        </a></li>
        <li class="nav-section">Akun</li>
        <li><a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a></li>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <span class="fw-semibold"><?= htmlspecialchars($pageTitle ?? '') ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle text-muted"></i>
            <span class="small"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="mx-3 mt-3">
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show py-2">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
            <?= htmlspecialchars($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="page-body">

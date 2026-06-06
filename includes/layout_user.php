<?php
// requireLogin('patient');
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Pasien') ?> – Klinik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/style.css">
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-brand">
        <span>🏥</span> Klinik
    </div>
    <ul class="sidebar-nav nav flex-column mt-2">
        <li class="nav-section">Menu</li>
        <li><a href="<?= BASE_URL ?>/user/index.php"
               class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-house"></i> Beranda
        </a></li>
        <li><a href="<?= BASE_URL ?>/user/booking.php"
               class="nav-link <?= $currentPage === 'booking.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-plus"></i> Buat Janji
        </a></li>
        <li><a href="<?= BASE_URL ?>/user/appointments.php"
               class="nav-link <?= $currentPage === 'appointments.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check"></i> Janji Saya
        </a></li>
        <li class="nav-section">Akun</li>
        <li><a href="<?= BASE_URL ?>/user/profile.php"
               class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i> Profil
        </a></li>
        <li><a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a></li>
    </ul>
</nav>

<div class="main-content">
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

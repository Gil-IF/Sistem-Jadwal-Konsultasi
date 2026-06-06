<?php
session_start();

$host    = 'localhost';
$db      = 'klinik';
$user    = 'eagan';
$pass    = 'eagangmr123';
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Helper: cek login
function requireLogin(string $role = ''): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Helper: redirect
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Helper: flash message
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Base URL (sesuaikan dengan path instalasi Anda)
define('BASE_URL', '/Sistem-jadwal-konsultasi');

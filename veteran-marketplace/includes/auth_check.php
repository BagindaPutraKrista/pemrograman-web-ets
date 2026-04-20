<?php
// Cek apakah user sudah login
// File ini di-include di setiap halaman yang butuh login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kalau belum login, redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Ambil data user yang sedang login dari database
$stmt = $pdo->prepare(
    "SELECT id, name, email, profile_photo, notif_chat, notif_transaction
     FROM users WHERE id = ? LIMIT 1"
);
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

// Kalau user tidak ditemukan di DB, hapus session dan redirect
if (!$currentUser) {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}

// Ambil jumlah pesan & notifikasi belum dibaca untuk badge di navbar
$unreadCounts = getUnreadCounts($pdo, (int) $currentUser['id']);

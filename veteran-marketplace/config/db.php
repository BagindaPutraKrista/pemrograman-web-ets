<?php
// Konfigurasi koneksi database
// Sesuaikan dengan pengaturan Laragon kamu

$host   = 'localhost';
$dbname = 'veteran_marketplace';
$user   = 'root';
$pass   = '';

// Buat koneksi ke MySQL menggunakan PDO
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // Kalau koneksi gagal, tampilkan pesan error
    die('Koneksi database gagal: ' . $e->getMessage());
}

<?php
// Kumpulan fungsi helper untuk Veteran Marketplace

// Upload foto ke folder tertentu
// Mengembalikan array ['path' => ...] jika berhasil, atau ['error' => ...] jika gagal
function uploadPhoto($file, $dir) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['error' => 'Format foto tidak didukung. Gunakan JPG, JPEG, atau PNG.'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => 'Ukuran foto maksimal 2MB.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Gagal mengunggah foto. Coba lagi.'];
    }

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid('img_', true) . '.' . $ext;
    $dest = rtrim($dir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Gagal mengunggah foto. Coba lagi.'];
    }

    return ['path' => $dest];
}

// Format angka menjadi format Rupiah
// Contoh: 10000 -> "Rp 10.000"
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Menampilkan waktu relatif dalam Bahasa Indonesia
// Contoh: "5 menit lalu", "2 jam lalu"
function timeAgo($datetime) {
    $now  = time();
    $past = strtotime($datetime);

    if ($past === false) return 'Baru saja';

    $diff = $now - $past;

    if ($diff < 60)       return 'Baru saja';
    if ($diff < 3600)     return (int)($diff / 60) . ' menit lalu';
    if ($diff < 86400)    return (int)($diff / 3600) . ' jam lalu';
    if ($diff < 2592000)  return (int)($diff / 86400) . ' hari lalu';
    if ($diff < 31536000) return (int)($diff / 2592000) . ' bulan lalu';
    return (int)($diff / 31536000) . ' tahun lalu';
}

// Membuat notifikasi baru untuk user tertentu
function createNotification($pdo, $user_id, $type, $message, $link = '') {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $type, $message, $link]);
}

// Mengambil jumlah pesan dan notifikasi yang belum dibaca
function getUnreadCounts($pdo, $user_id) {
    // Hitung pesan chat yang belum dibaca
    $chatStmt = $pdo->prepare("
        SELECT COUNT(*) FROM chat_messages cm
        JOIN chat_rooms cr ON cr.id = cm.room_id
        WHERE cm.is_read = 0
          AND cm.sender_id != ?
          AND (cr.buyer_id = ? OR cr.seller_id = ?)
    ");
    $chatStmt->execute([$user_id, $user_id, $user_id]);
    $chatCount = (int) $chatStmt->fetchColumn();

    // Hitung notifikasi yang belum dibaca
    $notifStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
    );
    $notifStmt->execute([$user_id]);
    $notifCount = (int) $notifStmt->fetchColumn();

    return ['chat' => $chatCount, 'notif' => $notifCount];
}

// Menyimpan pesan flash ke session (untuk ditampilkan di halaman berikutnya)
function flashMessage($key, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$key] = $message;
}

// Mengambil dan menghapus pesan flash dari session
function getFlash($key) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

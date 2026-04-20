<?php
require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int) $currentUser['id'];
$errors  = [];
$success = [];

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Change Password ───────────────────────────────────────────────────────
    if ($action === 'change_password') {
        $oldPassword     = $_POST['old_password']     ?? '';
        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Fetch current hashed password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!password_verify($oldPassword, $row['password'])) {
            $errors['old_password'] = 'Password lama salah';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Password baru minimal 6 karakter';
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            flashMessage('success', 'Password berhasil diubah');
            header('Location: settings.php');
            exit;
        }
    }

    // ── Save Notification Preferences ────────────────────────────────────────
    if ($action === 'save_notifications') {
        $notifChat        = isset($_POST['notif_chat'])        ? 1 : 0;
        $notifTransaction = isset($_POST['notif_transaction']) ? 1 : 0;

        $stmt = $pdo->prepare(
            "UPDATE users SET notif_chat = ?, notif_transaction = ? WHERE id = ?"
        );
        $stmt->execute([$notifChat, $notifTransaction, $userId]);
        flashMessage('success', 'Preferensi notifikasi berhasil disimpan');
        header('Location: settings.php');
        exit;
    }
}

$pageTitle = 'Pengaturan – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width:640px;">

    <div class="mb-4">
        <h4 class="fw-bold mb-0">Pengaturan Akun</h4>
        <p class="text-muted small">Kelola keamanan dan preferensi notifikasi Anda</p>
    </div>

    <!-- Flash messages handled by global toast system -->

    <!-- ── Section 1: Change Password ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-lock me-2 text-primary"></i>Ganti Password
            </h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" novalidate>
                <input type="hidden" name="action" value="change_password">

                <!-- Old password -->
                <div class="mb-3">
                    <label for="old_password" class="form-label fw-semibold">
                        Password Lama <span class="text-danger">*</span>
                    </label>
                    <input
                        type="password"
                        id="old_password"
                        name="old_password"
                        class="form-control <?= !empty($errors['old_password']) ? 'is-invalid' : '' ?>"
                        placeholder="Masukkan password lama"
                        autocomplete="current-password"
                    >
                    <?php if (!empty($errors['old_password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['old_password']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- New password -->
                <div class="mb-3">
                    <label for="new_password" class="form-label fw-semibold">
                        Password Baru <span class="text-danger">*</span>
                    </label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control <?= !empty($errors['new_password']) ? 'is-invalid' : '' ?>"
                        placeholder="Minimal 6 karakter"
                        autocomplete="new-password"
                    >
                    <?php if (!empty($errors['new_password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Confirm new password -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label fw-semibold">
                        Konfirmasi Password Baru <span class="text-danger">*</span>
                    </label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                        placeholder="Ulangi password baru"
                        autocomplete="new-password"
                    >
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-shield-lock me-1"></i>Simpan Password
                </button>
            </form>
        </div>
    </div>

    <!-- ── Section 2: Notification Preferences ────────────────────────────── -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-bell me-2 text-primary"></i>Preferensi Notifikasi
            </h6>
        </div>
        <div class="card-body p-4">
            <form method="POST" id="formNotif">
                <input type="hidden" name="action" value="save_notifications">

                <!-- Notif Chat -->
                <div class="d-flex align-items-center justify-content-between mb-3 py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">Notifikasi Chat</div>
                        <div class="text-muted small">Terima notifikasi saat ada pesan chat baru</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="notif_chat"
                            name="notif_chat"
                            <?= $currentUser['notif_chat'] ? 'checked' : '' ?>
                            style="width:2.5em; height:1.25em;"
                        >
                        <label class="form-check-label visually-hidden" for="notif_chat">Notifikasi Chat</label>
                    </div>
                </div>

                <!-- Notif Transaction -->
                <div class="d-flex align-items-center justify-content-between mb-4 py-2">
                    <div>
                        <div class="fw-semibold">Notifikasi Transaksi</div>
                        <div class="text-muted small">Terima notifikasi saat ada pembaruan status transaksi</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="notif_transaction"
                            name="notif_transaction"
                            <?= $currentUser['notif_transaction'] ? 'checked' : '' ?>
                            style="width:2.5em; height:1.25em;"
                        >
                        <label class="form-check-label visually-hidden" for="notif_transaction">Notifikasi Transaksi</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Simpan Preferensi
                </button>
            </form>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>

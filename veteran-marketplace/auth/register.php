<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $errors = [];

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'Semua field wajib diisi.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah digunakan.';
        }
    }

    if ($password !== $confirm) {
        $errors[] = 'Password tidak cocok.';
    }

    if (!empty($errors)) {
        flashMessage('error', implode(' ', $errors));
        $_SESSION['flash']['old'] = ['name' => $name, 'email' => $email];
        header('Location: register.php');
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hash]);

    flashMessage('success', 'Registrasi berhasil! Silakan login.');
    header('Location: login.php');
    exit;
}

$errorMsg   = getFlash('error');
$successMsg = getFlash('success');
$old        = $_SESSION['flash']['old'] ?? [];
unset($_SESSION['flash']['old']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar – Veteran Marketplace</title>
    <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #2d6a4f 0%, #1b4332 100%); min-height: 100vh; }
        .auth-card { border: none; border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }
        .auth-logo { color: #2d6a4f; font-size: 1.1rem; font-weight: 700; }
        .auth-logo i { font-size: 2rem; color: #2d6a4f; }
        .btn-auth { background-color: #2d6a4f; border-color: #2d6a4f; }
        .btn-auth:hover { background-color: #1b4332; border-color: #1b4332; }
        .form-control:focus { border-color: #2d6a4f; box-shadow: 0 0 0 0.2rem rgba(45,106,79,.25); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <div class="container" style="max-width: 440px;">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="auth-logo d-flex flex-column align-items-center gap-1">
                    <i class="bi bi-shield-fill-check"></i>
                    <span>UPN Veteran Jatim</span>
                </div>
                <h4 class="mt-2 fw-bold" style="color:#2d6a4f;">Veteran Marketplace</h4>
                <p class="text-muted small mb-0">Buat akun baru</p>
            </div>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($successMsg): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($successMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="Nama lengkap Anda"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="email@upnjatim.ac.id"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Minimal 6 karakter" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-auth btn-primary w-100 fw-semibold py-2">
                    <i class="bi bi-person-plus me-1"></i> Daftar Sekarang
                </button>
            </form>

            <hr class="my-3">
            <p class="text-center text-muted small mb-0">
                Sudah punya akun?
                <a href="login.php" class="fw-semibold" style="color:#2d6a4f;">Masuk di sini</a>
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

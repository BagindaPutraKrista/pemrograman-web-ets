<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/home.php');
    exit;
}

// POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        flashMessage('error', 'Email dan password wajib diisi.');
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flashMessage('error', 'Email atau password salah.');
        $_SESSION['flash']['old_email'] = $email;
        header('Location: login.php');
        exit;
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];

    header('Location: ../pages/home.php');
    exit;
}

$errorMsg   = getFlash('error');
$successMsg = getFlash('success');
$oldEmail   = $_SESSION['flash']['old_email'] ?? '';
unset($_SESSION['flash']['old_email']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk – Veteran Marketplace</title>
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
    <div class="container" style="max-width: 420px;">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="auth-logo d-flex flex-column align-items-center gap-1">
                    <i class="bi bi-shield-fill-check"></i>
                    <span>UPN Veteran Jatim</span>
                </div>
                <h4 class="mt-2 fw-bold" style="color:#2d6a4f;">Veteran Marketplace</h4>
                <p class="text-muted small mb-0">Masuk ke akun Anda</p>
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

            <form method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="email@upnjatim.ac.id"
                               value="<?= htmlspecialchars($oldEmail) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Password Anda" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-auth btn-primary w-100 fw-semibold py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>

            <hr class="my-3">
            <p class="text-center text-muted small mb-0">
                Belum punya akun?
                <a href="register.php" class="fw-semibold" style="color:#2d6a4f;">Daftar sekarang</a>
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

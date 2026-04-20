<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Veteran Marketplace') ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= isset($basePath) ? $basePath . '/../assets/favicon.svg' : '../assets/favicon.svg' ?>">
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath ?? '../assets/css/style.css') ?>">
    <!-- Bootstrap 5.3 JS Bundle (loaded early so dropdowns/modals work) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- Global Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;" id="toastContainer"></div>

<?php
require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int) $currentUser['id'];
$errors = [];

// POST: handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $bio     = trim($_POST['bio'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    // Validate name
    if ($name === '') {
        $errors['name'] = 'Nama lengkap tidak boleh kosong.';
    }

    if (empty($errors)) {
        // Handle photo upload
        $newPhotoPath = null;
        if (!empty($_FILES['profile_photo']['name'])) {
            $result = uploadPhoto($_FILES['profile_photo'], __DIR__ . '/../uploads/profiles/');
            if (isset($result['error'])) {
                $errors['profile_photo'] = $result['error'];
            } else {
                // Store relative path for web access
                $newPhotoPath = '../uploads/profiles/' . basename($result['path']);
            }
        }

        if (empty($errors)) {
            if ($newPhotoPath !== null) {
                $stmt = $pdo->prepare(
                    "UPDATE users SET name = ?, bio = ?, contact = ?, profile_photo = ? WHERE id = ?"
                );
                $stmt->execute([$name, $bio, $contact, $newPhotoPath, $userId]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE users SET name = ?, bio = ?, contact = ? WHERE id = ?"
                );
                $stmt->execute([$name, $bio, $contact, $userId]);
            }

            flashMessage('success', 'Profil berhasil diperbarui.');
            header('Location: edit_profile.php');
            exit;
        }
    }

    // Re-populate form values on error
    $formData = ['name' => $name, 'bio' => $bio, 'contact' => $contact];} else {
    // GET: fetch current user data
    $stmt = $pdo->prepare(
        "SELECT id, name, bio, contact, profile_photo FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $formData = $userData;
}

$pageTitle = 'Edit Profil – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width:600px;">

    <div class="mb-4">
        <h4 class="fw-bold mb-0">Edit Profil</h4>
        <p class="text-muted small">Perbarui informasi profil Anda</p>
    </div>

    <!-- Flash messages handled by global toast system -->

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data" novalidate>

                <!-- Profile photo -->
                <div class="mb-4 text-center">
                    <?php
                    $photoSrc = !empty($formData['profile_photo'])
                        ? htmlspecialchars($formData['profile_photo'])
                        : null;
                    ?>
                    <?php if ($photoSrc): ?>
                        <img
                            src="<?= $photoSrc ?>"
                            alt="Foto Profil"
                            class="rounded-circle object-fit-cover mb-3"
                            style="width:100px; height:100px;"
                            id="photoPreview"
                        >
                    <?php else: ?>
                        <div
                            class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white mx-auto mb-3"
                            style="width:100px; height:100px; font-size:2.5rem;"
                            id="photoPlaceholder"
                        >
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <img
                            src=""
                            alt="Foto Profil"
                            class="rounded-circle object-fit-cover mb-3 d-none"
                            style="width:100px; height:100px;"
                            id="photoPreview"
                        >
                    <?php endif; ?>

                    <div>
                        <label for="profile_photo" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-camera me-1"></i>Ganti Foto
                        </label>
                        <input
                            type="file"
                            id="profile_photo"
                            name="profile_photo"
                            accept=".jpg,.jpeg,.png"
                            class="d-none"
                            onchange="previewPhoto(this)"
                        >
                    </div>
                    <?php if (!empty($errors['profile_photo'])): ?>
                        <div class="text-danger small mt-1"><?= htmlspecialchars($errors['profile_photo']) ?></div>
                    <?php endif; ?>
                    <p class="text-muted small mt-1">Format: JPG, JPEG, PNG. Maks 2MB.</p>
                </div>

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Bio -->
                <div class="mb-3">
                    <label for="bio" class="form-label fw-semibold">Bio</label>
                    <textarea
                        id="bio"
                        name="bio"
                        class="form-control"
                        rows="3"
                        placeholder="Ceritakan sedikit tentang diri Anda..."
                    ><?= htmlspecialchars($formData['bio'] ?? '') ?></textarea>
                </div>

                <!-- Contact -->
                <div class="mb-4">
                    <label for="contact" class="form-label fw-semibold">Kontak</label>
                    <input
                        type="text"
                        id="contact"
                        name="contact"
                        class="form-control"
                        placeholder="Nomor HP, WhatsApp, dll."
                        value="<?= htmlspecialchars($formData['contact'] ?? '') ?>"
                    >
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                    </button>
                    <a href="profile.php?id=<?= $userId ?>" class="btn btn-outline-secondary">Batal</a>
                </div>

            </form>
        </div>
    </div>
</main>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (placeholder) placeholder.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int) $currentUser['id'];
$itemId = (int) ($_GET['id'] ?? 0);

if ($itemId <= 0) {
    header('Location: home.php');
    exit;
}

// Fetch item — must belong to current user
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND seller_id = ? LIMIT 1");
$stmt->execute([$itemId, $userId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: home.php');
    exit;
}

// Block editing if item is already sold or ordered
if ($item['status'] !== 'Tersedia') {
    flashMessage('error', 'Barang yang sudah ' . strtolower($item['status']) . ' tidak dapat diedit.');
    header('Location: item_detail.php?id=' . $itemId);
    exit;
}

// Fetch existing photos
$photoStmt = $pdo->prepare("SELECT * FROM item_photos WHERE item_id = ? ORDER BY is_primary DESC");
$photoStmt->execute([$itemId]);
$existingPhotos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $deletePhotos = $_POST['delete_photos'] ?? [];

    if ($title === '')       $errors[] = 'Judul wajib diisi.';
    if ($description === '') $errors[] = 'Deskripsi wajib diisi.';
    if ($category_id <= 0)   $errors[] = 'Kategori wajib dipilih.';
    if ($location === '')    $errors[] = 'Lokasi wajib diisi.';
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Harga wajib diisi dengan nilai yang valid.';
    }

    if (empty($errors)) {
        // Delete selected photos
        if (!empty($deletePhotos)) {
            foreach ($deletePhotos as $photoId) {
                $photoId = (int) $photoId;
                $pStmt = $pdo->prepare("SELECT photo_path FROM item_photos WHERE id = ? AND item_id = ?");
                $pStmt->execute([$photoId, $itemId]);
                $photo = $pStmt->fetch(PDO::FETCH_ASSOC);
                if ($photo) {
                    $fullPath = __DIR__ . '/../' . ltrim($photo['photo_path'], '../');
                    if (file_exists($fullPath)) @unlink($fullPath);
                    $pdo->prepare("DELETE FROM item_photos WHERE id = ?")->execute([$photoId]);
                }
            }
        }

        // Upload new photos
        if (!empty($_FILES['new_photos']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/items/';
            foreach ($_FILES['new_photos']['name'] as $i => $name) {
                if (empty($name)) continue;
                $file = [
                    'name'     => $_FILES['new_photos']['name'][$i],
                    'tmp_name' => $_FILES['new_photos']['tmp_name'][$i],
                    'error'    => $_FILES['new_photos']['error'][$i],
                    'size'     => $_FILES['new_photos']['size'][$i],
                ];
                $result = uploadPhoto($file, $uploadDir);
                if (isset($result['error'])) {
                    $errors[] = $result['error'];
                    break;
                }
                $relativePath = '../uploads/items/' . basename($result['path']);
                $pdo->prepare("INSERT INTO item_photos (item_id, photo_path, is_primary) VALUES (?, ?, 0)")
                    ->execute([$itemId, $relativePath]);
            }
        }

        if (empty($errors)) {
            // Ensure at least one photo remains
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM item_photos WHERE item_id = ?");
            $countStmt->execute([$itemId]);
            if ((int) $countStmt->fetchColumn() === 0) {
                $errors[] = 'Minimal satu foto harus ada.';
            }
        }

        if (empty($errors)) {
            // Ensure a primary photo exists
            $primaryStmt = $pdo->prepare("SELECT id FROM item_photos WHERE item_id = ? AND is_primary = 1 LIMIT 1");
            $primaryStmt->execute([$itemId]);
            if (!$primaryStmt->fetch()) {
                $firstStmt = $pdo->prepare("SELECT id FROM item_photos WHERE item_id = ? ORDER BY id ASC LIMIT 1");
                $firstStmt->execute([$itemId]);
                $first = $firstStmt->fetch(PDO::FETCH_ASSOC);
                if ($first) {
                    $pdo->prepare("UPDATE item_photos SET is_primary = 1 WHERE id = ?")->execute([$first['id']]);
                }
            }

            // Update item
            $pdo->prepare("
                UPDATE items SET title=?, description=?, category_id=?, location=?, price=?
                WHERE id = ? AND seller_id = ?
            ")->execute([$title, $description, $category_id, $location, (float)$price, $itemId, $userId]);

            flashMessage('success', 'Barang berhasil diperbarui.');
            header('Location: item_detail.php?id=' . $itemId);
            exit;
        }
    }

    // Re-fetch photos after changes
    $photoStmt->execute([$itemId]);
    $existingPhotos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
}

$itemCategories = $pdo->query("
    SELECT id, name FROM categories
    ORDER BY CASE WHEN slug = 'lainnya' THEN 1 ELSE 0 END ASC,
             FIELD(slug,
                'elektronik',
                'buku-alat-tulis',
                'pakaian-fashion',
                'perabot-furnitur',
                'olahraga-hobi',
                'kendaraan-aksesori',
                'makanan-minuman',
                'lainnya'
             ) ASC
")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Edit Barang – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width:720px;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="item_detail.php?id=<?= $itemId ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Edit Barang</h4>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate id="editItemForm">

        <!-- Unified photo section (existing + new, Shopee-style) -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Foto Barang <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">Format JPG, JPEG, PNG. Maks 2MB per foto. Klik × untuk hapus.</p>

            <!-- Hidden inputs to track which existing photos to delete -->
            <div id="deletePhotoInputs"></div>

            <!-- Preview grid: existing + new + add button -->
            <div id="photoPreview" class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($existingPhotos as $photo): ?>
                <div class="position-relative existing-photo-wrapper" data-photo-id="<?= (int) $photo['id'] ?>" style="flex-shrink:0;">
                    <img src="<?= htmlspecialchars($photo['photo_path']) ?>"
                         style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6;display:block;">
                    <button type="button"
                            style="position:absolute;top:2px;right:2px;width:22px;height:22px;border-radius:50%;border:none;background:rgba(0,0,0,0.6);color:#fff;font-size:14px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;"
                            onclick="removeExistingPhoto(this, <?= (int) $photo['id'] ?>)">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Hidden file input for new photos -->
            <input type="file" name="new_photos[]" id="newPhotoInput" class="d-none" multiple accept="image/jpeg,image/png">
        </div>

        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title" class="form-control" maxlength="200" required
                   value="<?= htmlspecialchars($item['title']) ?>">
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
            <textarea name="description" id="description" class="form-control" rows="5" required
            ><?= htmlspecialchars($item['description']) ?></textarea>
        </div>

        <!-- Category -->
        <div class="mb-3">
            <label for="category_id" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($itemCategories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"
                        <?= ((int) $item['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Location -->
        <div class="mb-3">
            <label for="location" class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
            <input type="text" name="location" id="location" class="form-control" maxlength="150" required
                   placeholder="Contoh: Giri Loka, FEB, FIK, Techno Park"
                   value="<?= htmlspecialchars($item['location']) ?>">
        </div>

        <!-- Price -->
        <div class="mb-4">
            <label for="price" class="form-label fw-semibold">Harga <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" name="price" id="price" class="form-control" min="0" step="1000" required
                       value="<?= htmlspecialchars($item['price']) ?>">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
            </button>
            <a href="item_detail.php?id=<?= $itemId ?>" class="btn btn-outline-secondary btn-lg">Batal</a>
        </div>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>

<script>
(function () {
    var dt = new DataTransfer();
    var input   = document.getElementById('newPhotoInput');
    var preview = document.getElementById('photoPreview');
    var deleteInputs = document.getElementById('deletePhotoInputs');

    // Render the "+" add button at the end of the grid
    function renderAddButton() {
        var existing = preview.querySelector('.add-btn');
        if (existing) existing.remove();
        var addBtn = document.createElement('div');
        addBtn.className = 'add-btn';
        addBtn.style.cssText = 'width:100px;height:100px;border-radius:8px;border:2px dashed #dee2e6;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:2rem;color:#adb5bd;flex-shrink:0;';
        addBtn.innerHTML = '<i class="bi bi-plus"></i>';
        addBtn.title = 'Tambah foto';
        addBtn.addEventListener('click', function () { input.click(); });
        preview.appendChild(addBtn);
    }
    renderAddButton();

    // Remove existing (server-side) photo
    window.removeExistingPhoto = function(btn, photoId) {
        var wrapper = btn.closest('.existing-photo-wrapper');
        if (wrapper) wrapper.remove();
        // Add hidden input so PHP knows to delete this photo
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_photos[]';
        hidden.value = photoId;
        deleteInputs.appendChild(hidden);
        renderAddButton();
    };

    // Add new photos
    input.addEventListener('change', function () {
        Array.from(this.files).forEach(function (file) {
            if (!file.type.match(/image\/(jpeg|png)/)) return;
            if (file.size > 2 * 1024 * 1024) { alert(file.name + ' melebihi 2MB.'); return; }
            dt.items.add(file);
        });
        this.value = '';
        rebuildNewPreviews();
    });

    function rebuildNewPreviews() {
        // Remove old new-photo wrappers
        preview.querySelectorAll('.new-photo-wrapper').forEach(function(el){ el.remove(); });

        // Re-insert before the add button
        var addBtn = preview.querySelector('.add-btn');
        Array.from(dt.files).forEach(function (file, i) {
            var objectUrl = URL.createObjectURL(file);
            var wrapper = document.createElement('div');
            wrapper.className = 'position-relative new-photo-wrapper';
            wrapper.style.cssText = 'flex-shrink:0;';

            var img = document.createElement('img');
            img.src = objectUrl;
            img.setAttribute('data-obj-url', '1');
            img.style.cssText = 'width:100px;height:100px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6;display:block;';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&times;';
            btn.style.cssText = 'position:absolute;top:2px;right:2px;width:22px;height:22px;border-radius:50%;border:none;background:rgba(0,0,0,0.6);color:#fff;font-size:14px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;';
            btn.setAttribute('data-remove-index', i);
            btn.addEventListener('click', function () {
                URL.revokeObjectURL(img.src);
                var idx = parseInt(this.getAttribute('data-remove-index'));
                var newDt = new DataTransfer();
                Array.from(dt.files).forEach(function(f, j){ if (j !== idx) newDt.items.add(f); });
                dt = newDt;
                rebuildNewPreviews();
                syncInput();
            });

            wrapper.appendChild(img);
            wrapper.appendChild(btn);
            preview.insertBefore(wrapper, addBtn);
        });

        syncInput();
    }

    function syncInput() {
        input.files = dt.files;
    }
})();
</script>

<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if post_max_size was exceeded (PHP silently empties $_POST and $_FILES)
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxRaw    = ini_get('post_max_size');
    $postMaxBytes  = (int) $postMaxRaw * match(strtolower(substr(trim($postMaxRaw), -1))) {
        'g' => 1024 * 1024 * 1024,
        'm' => 1024 * 1024,
        'k' => 1024,
        default => 1,
    };
    if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
        flashMessage('error', 'Total ukuran foto terlalu besar. Kurangi jumlah atau ukuran foto (maks ' . $postMaxRaw . ').');
        header('Location: sell_item.php');
        exit;
    }
    // Validate required fields
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    $price       = trim($_POST['price'] ?? '');

    $errors = [];

    if ($title === '')       $errors[] = 'Judul wajib diisi.';
    if ($description === '') $errors[] = 'Deskripsi wajib diisi.';
    if ($category_id <= 0)   $errors[] = 'Kategori wajib dipilih.';
    if ($location === '')    $errors[] = 'Lokasi wajib diisi.';
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Harga wajib diisi dengan nilai yang valid.';
    }

    // Validate at least 1 photo uploaded
    if (empty($_FILES['photos']['name'][0])) {
        $errors[] = 'Minimal satu foto harus diunggah.';
    }

    if (!empty($errors)) {
        flashMessage('error', implode(' ', $errors));
        header('Location: sell_item.php');
        exit;
    }

    // Process photo uploads
    $uploadDir   = __DIR__ . '/../uploads/items/';
    $uploadedPaths = [];

    foreach ($_FILES['photos']['name'] as $i => $name) {
        if (empty($name)) continue;

        $file = [
            'name'     => $_FILES['photos']['name'][$i],
            'type'     => $_FILES['photos']['type'][$i],
            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
            'error'    => $_FILES['photos']['error'][$i],
            'size'     => $_FILES['photos']['size'][$i],
        ];

        $result = uploadPhoto($file, $uploadDir);

        if (isset($result['error'])) {
            flashMessage('error', $result['error']);
            header('Location: sell_item.php');
            exit;
        }

        $uploadedPaths[] = $result['path'];
    }

    if (empty($uploadedPaths)) {
        flashMessage('error', 'Minimal satu foto harus diunggah.');
        header('Location: sell_item.php');
        exit;
    }

    // Insert item
    $stmt = $pdo->prepare("
        INSERT INTO items (seller_id, category_id, title, description, price, location, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Tersedia')
    ");
    $stmt->execute([
        (int) $currentUser['id'],
        $category_id,
        $title,
        $description,
        (float) $price,
        $location,
    ]);
    $newItemId = (int) $pdo->lastInsertId();

    // Insert photos
    $photoStmt = $pdo->prepare("
        INSERT INTO item_photos (item_id, photo_path, is_primary) VALUES (?, ?, ?)
    ");
    foreach ($uploadedPaths as $idx => $path) {
        // Store relative path for web access
        $relativePath = '../uploads/items/' . basename($path);
        $photoStmt->execute([$newItemId, $relativePath, $idx === 0 ? 1 : 0]);
    }

    flashMessage('success', 'Barang berhasil diposting!');
    header('Location: item_detail.php?id=' . $newItemId);
    exit;
}

// GET: show form — order categories with 'Lainnya' always last
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

$pageTitle = 'Jual Barang – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width: 720px;">
    <h4 class="fw-bold mb-4">Jual Barang</h4>

    <form method="POST" enctype="multipart/form-data" novalidate>

        <!-- Photo Upload -->
        <div class="mb-4">
            <label class="form-label fw-semibold">Foto Barang <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">Minimal 1 foto. Format JPG, JPEG, PNG. Maks 2MB per foto.</p>

            <!-- Drop zone / click to add -->
            <div
                id="photoDropZone"
                class="border border-2 border-dashed rounded-3 p-4 text-center text-muted"
                style="cursor:pointer; border-color:#dee2e6 !important; transition:border-color .2s;"
                onclick="document.getElementById('photoInput').click()"
            >
                <i class="bi bi-images fs-2 d-block mb-2"></i>
                <span class="small">Klik untuk pilih foto, atau pilih lagi untuk menambah</span>
            </div>
            <input type="file" name="photos[]" id="photoInput" class="d-none" multiple accept="image/jpeg,image/png">

            <!-- Preview grid -->
            <div id="photoPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
        </div>

        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
            <input
                type="text"
                name="title"
                id="title"
                class="form-control"
                placeholder="Contoh: Laptop Asus VivoBook 14 bekas"
                maxlength="200"
                required
                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
            >
        </div>

        <!-- Description -->
        <div class="mb-3">
            <label for="description" class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
            <textarea
                name="description"
                id="description"
                class="form-control"
                rows="5"
                placeholder="Jelaskan kondisi, spesifikasi, dan informasi lain tentang barang..."
                required
            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <!-- Category -->
        <div class="mb-3">
            <label for="category_id" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($itemCategories as $cat): ?>
                    <option
                        value="<?= (int) $cat['id'] ?>"
                        <?= ((int)($_POST['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Location -->
        <div class="mb-3">
            <label for="location" class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
            <input
                type="text"
                name="location"
                id="location"
                class="form-control"
                placeholder="Contoh: Giri Loka, FEB, FIK, Techno Park"
                maxlength="150"
                required
                value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
            >
        </div>

        <!-- Price -->
        <div class="mb-4">
            <label for="price" class="form-label fw-semibold">Harga <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input
                    type="number"
                    name="price"
                    id="price"
                    class="form-control"
                    placeholder="Contoh: 500000"
                    min="0"
                    step="1000"
                    required
                    value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                >
            </div>
            <div class="form-text">Masukkan harga dalam Rupiah, tanpa titik atau koma.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-upload me-2"></i>Posting Barang
        </button>
    </form>
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>

<script>
(function () {
    let dt = new DataTransfer();
    const input    = document.getElementById('photoInput');
    const preview  = document.getElementById('photoPreview');
    const dropZone = document.getElementById('photoDropZone');
    const form     = input.closest('form');

    dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.style.borderColor = 'var(--color-primary)'; });
    dropZone.addEventListener('dragleave', function () { dropZone.style.borderColor = '#dee2e6'; });
    dropZone.addEventListener('drop', function (e) { e.preventDefault(); dropZone.style.borderColor = '#dee2e6'; addFiles(e.dataTransfer.files); });
    input.addEventListener('change', function () { addFiles(this.files); this.value = ''; });

    function addFiles(files) {
        Array.from(files).forEach(function (file) {
            if (!file.type.match(/image\/(jpeg|png)/)) return;
            if (file.size > 2 * 1024 * 1024) { alert(file.name + ' melebihi 2MB.'); return; }
            dt.items.add(file);
        });
        rebuildPreview();
    }

    function removeFile(index) {
        var newDt = new DataTransfer();
        Array.from(dt.files).forEach(function (f, i) { if (i !== index) newDt.items.add(f); });
        dt = newDt;
        rebuildPreview();
    }

    function rebuildPreview() {
        preview.querySelectorAll('img[data-obj-url]').forEach(function(img) {
            URL.revokeObjectURL(img.src);
        });
        preview.innerHTML = '';

        if (dt.files.length === 0) {
            dropZone.style.display = '';
            return;
        }
        dropZone.style.display = 'none';

        Array.from(dt.files).forEach(function (file, i) {
            var objectUrl = URL.createObjectURL(file);

            var wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
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
                removeFile(parseInt(this.getAttribute('data-remove-index')));
            });

            wrapper.appendChild(img);
            wrapper.appendChild(btn);
            preview.appendChild(wrapper);
        });

        // "+" add more button
        var addBtn = document.createElement('div');
        addBtn.style.cssText = 'width:100px;height:100px;border-radius:8px;border:2px dashed #dee2e6;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:2rem;color:#adb5bd;flex-shrink:0;';
        addBtn.innerHTML = '<i class="bi bi-plus"></i>';
        addBtn.title = 'Tambah foto';
        addBtn.addEventListener('click', function () { input.click(); });
        preview.appendChild(addBtn);
    }

    // On submit: inject accumulated files into the real input via DataTransfer,
    // then let the browser submit the form normally (no fetch needed).
    form.addEventListener('submit', function (e) {
        if (dt.files.length === 0) {
            // No files selected — let PHP show the validation error normally
            return;
        }

        // Try to assign dt.files directly to the input (works in Chrome/Firefox/Edge)
        try {
            input.files = dt.files;
        } catch (ex) {
            // Fallback: if direct assignment fails, do nothing — files already in dt
        }

        // If the input now has files, submit normally
        if (input.files && input.files.length > 0) {
            // Normal form submit — browser handles multipart encoding natively
            return;
        }

        // Last resort fallback: submit via fetch
        e.preventDefault();
        var fd = new FormData(form);
        fd.delete('photos[]');
        Array.from(dt.files).forEach(function(file) { fd.append('photos[]', file); });

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memposting...'; }

        fetch(window.location.href, { method: 'POST', body: fd, redirect: 'follow' })
        .then(function(r) {
            if (r.redirected || r.url.indexOf('item_detail.php') !== -1) {
                window.location.href = r.url;
                return;
            }
            return r.text().then(function(html) {
                document.open(); document.write(html); document.close();
            });
        })
        .catch(function(err) {
            console.error(err);
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Posting Barang'; }
            alert('Terjadi kesalahan. Coba lagi.');
        });
    });
})();
</script>

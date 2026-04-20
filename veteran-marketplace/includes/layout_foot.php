    <?php if (isset($extraJs)) echo $extraJs; ?>
<script>
// Fungsi untuk menampilkan notifikasi pop-up (toast) di pojok kanan atas
// Dipanggil otomatis kalau ada flash message dari PHP
function showToast(message, type, duration) {
    if (!type) type = 'success';
    if (!duration) duration = 4000;

    var colors = {
        success: '#198754',
        danger:  '#dc3545',
        warning: '#ffc107',
        info:    '#0dcaf0'
    };
    var icons = {
        success: 'bi-check-circle-fill',
        danger:  'bi-exclamation-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill'
    };

    var container = document.getElementById('toastContainer');
    if (!container) return;

    var id = 'toast_' + Date.now();
    var bgColor = colors[type] || colors.success;
    var icon = icons[type] || icons.success;

    var html = '<div id="' + id + '" class="toast align-items-center text-white border-0 show mb-2" role="alert"'
        + ' style="background:' + bgColor + ';min-width:280px;">'
        + '<div class="d-flex">'
        + '<div class="toast-body d-flex align-items-center gap-2">'
        + '<i class="bi ' + icon + '"></i>' + message
        + '</div>'
        + '<button type="button" class="btn-close btn-close-white me-2 m-auto"'
        + ' onclick="document.getElementById(\'' + id + '\').remove()"></button>'
        + '</div></div>';

    container.insertAdjacentHTML('beforeend', html);

    // Hapus otomatis setelah beberapa detik
    setTimeout(function() {
        var el = document.getElementById(id);
        if (el) el.remove();
    }, duration);
}

<?php
// Cek apakah ada flash message yang perlu ditampilkan sebagai toast
$toastMessages = [];
foreach (['success', 'error', 'info', 'warning'] as $key) {
    $msg = getFlash($key);
    if ($msg) {
        $type = ($key === 'error') ? 'danger' : $key;
        $toastMessages[] = ['msg' => $msg, 'type' => $type];
    }
}

if (!empty($toastMessages)):
?>
document.addEventListener('DOMContentLoaded', function() {
<?php foreach ($toastMessages as $t): ?>
    showToast(<?= json_encode(htmlspecialchars($t['msg'])) ?>, '<?= $t['type'] ?>');
<?php endforeach; ?>
});
<?php endif; ?>
</script>
</body>
</html>

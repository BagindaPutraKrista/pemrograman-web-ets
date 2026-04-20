// navbar.js - Update badge notifikasi dan chat di navbar secara otomatis

// Fungsi untuk mengambil jumlah pesan/notif yang belum dibaca dari server
function fetchUnreadCounts() {
    fetch('../api/notif_count.php')
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            updateBadge('badge-chat', data.chat);
            updateBadge('badge-notif', data.notif);
        })
        .catch(function() {
            // Kalau gagal, diam saja (tidak perlu tampilkan error)
        });
}

// Fungsi untuk update tampilan badge angka di navbar
function updateBadge(id, count) {
    var el = document.getElementById(id);
    if (!el) return;

    if (count > 0) {
        el.textContent = count;
        el.classList.remove('d-none');
    } else {
        el.textContent = '';
        el.classList.add('d-none');
    }
}

// Jalankan saat halaman pertama kali dibuka
fetchUnreadCounts();

// Ulangi setiap 30 detik
setInterval(fetchUnreadCounts, 30000);

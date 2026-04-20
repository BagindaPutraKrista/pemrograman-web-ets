// filter.js - Logika tombol Filter & Urutkan di navbar

// Tentukan path ke search.php berdasarkan halaman saat ini
function getSearchUrl() {
    var path = window.location.pathname;
    if (path.indexOf('/pages/') !== -1) {
        return 'search.php';
    }
    return 'pages/search.php';
}

// Ambil keyword pencarian dari URL atau input search di navbar
function getCurrentKeyword() {
    var params = new URLSearchParams(window.location.search);
    var q = params.get('q') || '';

    var searchInput = document.querySelector('input[name="q"]');
    if (searchInput && searchInput.value.trim()) {
        q = searchInput.value.trim();
    }
    return q;
}

// Terapkan filter: baca pilihan dari modal lalu redirect ke search.php
function applyFilter() {
    var sortRadio = document.querySelector('input[name="sort"]:checked');
    var sort = sortRadio ? sortRadio.value : 'newest';

    var checkedCategory = document.querySelector('.filter-category:checked');
    var categoryName = checkedCategory ? checkedCategory.value : '';

    var q = getCurrentKeyword();

    // Buat URL dengan parameter filter
    var params = new URLSearchParams();
    if (q) params.set('q', q);
    if (sort && sort !== 'relevance') params.set('sort', sort);
    if (categoryName) params.set('category_name', categoryName);

    var url = getSearchUrl();
    var qs = params.toString();
    if (qs) url += '?' + qs;

    // Tutup modal dulu, baru redirect
    var modalEl = document.getElementById('filterModal');
    if (modalEl && window.bootstrap) {
        var modal = window.bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    window.location.href = url;
}

// Reset filter: kosongkan semua pilihan lalu redirect
function resetFilter() {
    // Uncheck semua kategori
    document.querySelectorAll('.filter-category:checked').forEach(function(cb) {
        cb.checked = false;
    });

    // Reset sort ke default
    var relevanceRadio = document.querySelector('input[name="sort"][value="relevance"]');
    if (relevanceRadio) relevanceRadio.checked = true;

    var q = getCurrentKeyword();
    var url = getSearchUrl();
    if (q) url += '?q=' + encodeURIComponent(q);
    window.location.href = url;
}

// Pasang event listener setelah DOM siap
function initFilter() {
    var btnApply = document.getElementById('btnApplyFilter');
    var btnReset = document.getElementById('btnResetFilter');

    if (btnApply) {
        btnApply.removeAttribute('data-bs-dismiss');
        btnApply.addEventListener('click', applyFilter);
    }

    if (btnReset) {
        btnReset.addEventListener('click', resetFilter);
    }

    // Pre-select sort dan kategori dari URL saat ini
    var params = new URLSearchParams(window.location.search);

    var currentSort = params.get('sort') || 'relevance';
    var sortRadio = document.querySelector('input[name="sort"][value="' + currentSort + '"]');
    if (sortRadio) sortRadio.checked = true;

    var currentCategory = params.get('category_name') || '';
    if (currentCategory) {
        document.querySelectorAll('.filter-category').forEach(function(cb) {
            if (cb.value === currentCategory) cb.checked = true;
        });
    }
}

// Jalankan init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFilter);
} else {
    initFilter();
}

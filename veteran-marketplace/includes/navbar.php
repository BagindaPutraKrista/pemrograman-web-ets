<?php
/**
 * Navbar component.
 * Requires: $currentUser (array), $unreadCounts (array with 'chat' and 'notif' keys)
 * Both are set by auth_check.php.
 */
?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container-fluid px-3">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="../pages/home.php">
            <span class="d-flex align-items-center justify-content-center rounded-2 text-white" style="width:32px;height:32px;background:var(--color-primary);font-size:1.1rem;">
                <i class="bi bi-bag-heart-fill"></i>
            </span>
            <span>VetMarket</span>
        </a>

        <!-- Mobile toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Search bar -->
            <form class="d-flex flex-grow-1 mx-3 my-2 my-lg-0" method="GET" action="../pages/search.php">
                <div class="input-group">
                    <input
                        type="search"
                        name="q"
                        class="form-control"
                        placeholder="Cari barang..."
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    >
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Filter button -->
            <button
                type="button"
                class="btn btn-outline-secondary me-2 d-flex align-items-center gap-1"
                data-bs-toggle="modal"
                data-bs-target="#filterModal"
            >
                <i class="bi bi-sliders"></i>
                <span>Filter</span>
            </button>

            <!-- Right-side icons -->
            <ul class="navbar-nav align-items-center gap-1 ms-auto">

                <!-- Chat -->
                <li class="nav-item">
                    <a class="nav-link position-relative px-2" href="../pages/chat.php" title="Chat">
                        <i class="bi bi-chat-dots fs-5"></i>
                        <?php if ($unreadCounts['chat'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badge-chat">
                                <?= $unreadCounts['chat'] ?>
                            </span>
                        <?php else: ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="badge-chat"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Wishlist -->
                <li class="nav-item">
                    <a class="nav-link px-2" href="../pages/wishlist.php" title="Wishlist">
                        <i class="bi bi-heart fs-5"></i>
                    </a>
                </li>

                <!-- Notifications -->
                <li class="nav-item">
                    <a class="nav-link position-relative px-2" href="../pages/notification.php" title="Notifikasi">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($unreadCounts['notif'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badge-notif">
                                <?= $unreadCounts['notif'] ?>
                            </span>
                        <?php else: ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="badge-notif"></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Profile dropdown -->
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle d-flex align-items-center gap-1 px-2"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                    >
                        <?php if (!empty($currentUser['profile_photo'])): ?>
                            <img
                                src="<?= htmlspecialchars($currentUser['profile_photo']) ?>"
                                alt="Profil"
                                class="rounded-circle"
                                style="width:30px;height:30px;object-fit:cover;"
                            >
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5"></i>
                        <?php endif; ?>
                        <span class="d-none d-lg-inline"><?= htmlspecialchars($currentUser['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="../pages/profile.php?id=<?= (int) $currentUser['id'] ?>">
                                <i class="bi bi-person me-2"></i>Lihat Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../pages/edit_profile.php">
                                <i class="bi bi-pencil me-2"></i>Edit Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../pages/transaction_history.php">
                                <i class="bi bi-arrow-left-right me-2"></i>Transaksi
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../pages/settings.php">
                                <i class="bi bi-gear me-2"></i>Pengaturan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Sell button -->
                <li class="nav-item ms-1">
                    <a class="btn btn-success d-flex align-items-center gap-1" href="../pages/sell_item.php">
                        <i class="bi bi-plus-lg"></i>
                        <span>Jual</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- ── Filter Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">
                    <i class="bi bi-sliders me-2"></i>Filter &amp; Urutkan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Sort options -->
                <h6 class="fw-semibold mb-2">Urutkan</h6>
                <div class="d-flex flex-column gap-2 mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort" id="sortRelevant" value="relevance" checked>
                        <label class="form-check-label" for="sortRelevant">Paling Relevan</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort" id="sortNewest" value="newest">
                        <label class="form-check-label" for="sortNewest">Terbaru</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort" id="sortPriceHigh" value="price_high">
                        <label class="form-check-label" for="sortPriceHigh">Harga Tertinggi</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sort" id="sortPriceLow" value="price_low">
                        <label class="form-check-label" for="sortPriceLow">Harga Terendah</label>
                    </div>
                </div>

                <!-- Category filter -->
                <h6 class="fw-semibold mb-2">Kategori</h6>
                <div class="d-flex flex-column gap-2">
                    <?php
                    $navCategories = [
                        'Elektronik',
                        'Buku & Alat Tulis',
                        'Pakaian & Fashion',
                        'Perabot & Furnitur',
                        'Olahraga & Hobi',
                        'Kendaraan & Aksesori',
                        'Makanan & Minuman',
                        'Lainnya',
                    ];
                    foreach ($navCategories as $cat):
                        $catId = 'cat-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($cat));
                    ?>
                    <div class="form-check">
                        <input class="form-check-input filter-category" type="checkbox" id="<?= $catId ?>" value="<?= htmlspecialchars($cat) ?>">
                        <label class="form-check-label" for="<?= $catId ?>"><?= htmlspecialchars($cat) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="btnResetFilter">Reset</button>
                <button type="button" class="btn btn-primary" id="btnApplyFilter">
                    Terapkan Filter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Navbar JS (badge polling) -->
<script src="../assets/js/navbar.js"></script>
<!-- Filter JS (available on all pages) -->
<script src="../assets/js/filter.js"></script>

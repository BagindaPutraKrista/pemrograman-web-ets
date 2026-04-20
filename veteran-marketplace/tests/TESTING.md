# Veteran Marketplace — Testing Documentation

## Automated Tests

### Running the Test Suite

```bash
# Install dependencies (first time only)
cd veteran-marketplace
composer install

# Run all tests
./vendor/bin/phpunit

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit

# Run only integration tests
./vendor/bin/phpunit --testsuite Integration
```

### Test Coverage Summary

| File | Type | Property | Requirement |
|---|---|---|---|
| `tests/Unit/UploadPhotoTest.php` | Unit + Property | Property 7: File upload format & size | Req 6.5, 6.6, 12.5 |
| `tests/Unit/RatingValidationTest.php` | Unit + Property | Property 9: Rating range validity | Req 18.1, 18.2 |
| `tests/Integration/PasswordHashTest.php` | Integration + Property | Property 1: Password hash round-trip | Req 1.5 |
| `tests/Integration/WishlistIdempotenceTest.php` | Integration + Property | Property 2: Wishlist uniqueness | Req 9.5 |
| `tests/Integration/ReviewConstraintTest.php` | Integration + Property | Property 5: One review per transaction | Req 18.5, 18.6 |
| `tests/Integration/ChatPollingTest.php` | Integration + Property | Property 4: Chat message ordering | Req 8.3, 8.4 |

All property-based tests run a minimum of **100 iterations** as specified in the design document.

---

## Task 17.7 — Manual Testing Checklist

Perform the following manual tests in a browser with the application running via Laragon (or equivalent local server).

### 1. Registrasi & Login Flow

- [ ] Buka halaman `/auth/register.php`
- [ ] Isi semua field (nama, email, password, konfirmasi password) → klik Daftar
- [ ] Verifikasi redirect ke halaman login dengan pesan "Registrasi berhasil"
- [ ] Login dengan email dan password yang baru didaftarkan
- [ ] Verifikasi redirect ke halaman Home setelah login berhasil
- [ ] Coba login dengan password salah → verifikasi pesan "Email atau password salah"
- [ ] Klik Logout → verifikasi redirect ke halaman login

### 2. Upload Foto Item

- [ ] Buka halaman `/pages/sell_item.php`
- [ ] Upload foto berformat **JPG** (ukuran < 2MB) → verifikasi berhasil
- [ ] Upload foto berformat **PNG** (ukuran < 2MB) → verifikasi berhasil
- [ ] Upload foto berformat **GIF** → verifikasi pesan error format
- [ ] Upload foto berukuran **> 2MB** → verifikasi pesan error ukuran
- [ ] Coba submit form tanpa foto → verifikasi pesan error "minimal 1 foto"

### 3. Chat Realtime (AJAX Polling)

- [ ] Buka dua tab browser: Tab A login sebagai User A, Tab B login sebagai User B
- [ ] Tab A: buka item detail milik User B → klik "Chat Seller"
- [ ] Tab B: buka halaman Chat → pilih percakapan dengan User A
- [ ] Tab A: kirim pesan "Halo, barangnya masih ada?"
- [ ] Tab B: verifikasi pesan muncul dalam **≤ 3 detik** tanpa reload halaman
- [ ] Tab B: balas pesan
- [ ] Tab A: verifikasi balasan muncul dalam ≤ 3 detik
- [ ] Verifikasi badge unread count di navbar berkurang setelah membuka percakapan

### 4. Filter dan Search

- [ ] Ketik kata kunci di search bar → tekan Enter → verifikasi hasil muncul
- [ ] Buka filter modal → pilih kategori "Elektronik" → klik Terapkan
- [ ] Verifikasi hasil hanya menampilkan item kategori Elektronik (tanpa reload penuh)
- [ ] Kombinasikan keyword + kategori + sort "Harga Terendah" → verifikasi hasil sesuai
- [ ] Cari kata kunci yang tidak ada → verifikasi pesan "Tidak ada barang yang ditemukan"

### 5. Badge Status Transitions

- [ ] Buat listing baru → verifikasi badge "Tersedia" muncul di Home
- [ ] Sebagai Buyer: mulai transaksi untuk item tersebut
- [ ] Verifikasi badge berubah menjadi "Dipesan" di semua halaman (Home, Search, Wishlist)
- [ ] Sebagai Buyer: klik "Konfirmasi Terima Barang"
- [ ] Verifikasi badge berubah menjadi "Terjual"
- [ ] Verifikasi tombol "Chat Seller" dinonaktifkan pada item "Terjual"

### 6. Notifikasi

- [ ] Kirim pesan chat ke user lain → verifikasi notifikasi muncul di ikon navbar penerima
- [ ] Ubah status transaksi → verifikasi notifikasi muncul untuk kedua pihak
- [ ] Klik notifikasi → verifikasi redirect ke halaman yang relevan
- [ ] Verifikasi notifikasi ditandai "sudah dibaca" setelah diklik
- [ ] Verifikasi badge count berkurang setelah membaca notifikasi

### 7. Wishlist Toggle

- [ ] Buka item detail → klik tombol Wishlist → verifikasi ikon berubah aktif
- [ ] Buka halaman Wishlist → verifikasi item muncul
- [ ] Klik tombol Wishlist lagi (toggle off) → verifikasi item hilang dari Wishlist
- [ ] Tambah item yang sama dua kali → verifikasi hanya satu entry di Wishlist

### 8. Review Form

- [ ] Selesaikan transaksi → buka halaman Transaction History
- [ ] Klik "Beri Ulasan" → isi rating bintang dan komentar → submit
- [ ] Verifikasi review muncul di halaman Profile dan Seller Detail seller
- [ ] Coba submit review kedua untuk transaksi yang sama
- [ ] Verifikasi pesan "Anda sudah memberikan ulasan untuk transaksi ini"

---

## Task 17.8 — Responsive Mobile Testing (Bootstrap 5)

Gunakan Chrome DevTools (F12 → Toggle Device Toolbar) atau perangkat mobile fisik untuk menguji responsivitas.

### Breakpoints yang Diuji

| Breakpoint | Lebar | Perangkat Contoh |
|---|---|---|
| xs | < 576px | iPhone SE |
| sm | 576px | iPhone 12 |
| md | 768px | iPad Mini |
| lg | 992px | iPad Pro |

### Checklist Responsivitas

#### Halaman Home (`/pages/home.php`)
- [ ] Grid kartu item berubah dari 4 kolom (desktop) → 2 kolom (tablet) → 1 kolom (mobile)
- [ ] Navbar collapse menjadi hamburger menu di mobile
- [ ] Search bar tetap dapat digunakan di mobile

#### Halaman Item Detail (`/pages/item_detail.php`)
- [ ] Galeri foto tidak overflow di layar kecil
- [ ] Tombol "Chat Seller" dan "Wishlist" mudah diklik di mobile (ukuran touch target ≥ 44px)
- [ ] Informasi seller dan harga terbaca dengan jelas

#### Halaman Chat (`/pages/chat.php`)
- [ ] Layout dua kolom berubah menjadi satu kolom di mobile
- [ ] Daftar room dapat diakses (misalnya via tab atau scroll)
- [ ] Input pesan dan tombol kirim tidak tertutup keyboard virtual

#### Halaman Sell Item (`/pages/sell_item.php`)
- [ ] Form field tidak overflow
- [ ] Tombol upload foto mudah diakses
- [ ] Preview foto tampil dengan benar

#### Halaman Wishlist (`/pages/wishlist.php`)
- [ ] Kartu item tersusun rapi di mobile
- [ ] Tombol hapus mudah diklik

#### Halaman Transaction History (`/pages/transaction_history.php`)
- [ ] Tabel atau kartu transaksi tidak overflow secara horizontal
- [ ] Tombol aksi (Konfirmasi, Beri Ulasan) mudah diklik

#### Halaman Profile & Seller Detail
- [ ] Foto profil, nama, dan rating tampil dengan benar di mobile
- [ ] Daftar item dan review dapat di-scroll dengan nyaman

### Catatan Umum Responsivitas
- [ ] Tidak ada elemen yang overflow secara horizontal di semua halaman
- [ ] Font size minimal 14px untuk keterbacaan di mobile
- [ ] Semua tombol dan link memiliki touch target yang cukup besar
- [ ] Modal (filter, lightbox) berfungsi dengan baik di mobile
- [ ] Tidak ada konten yang tersembunyi atau tidak dapat diakses di mobile

# Tasks: Veteran Marketplace

## Task List

- [x] 1. Setup Proyek dan Konfigurasi Database
  - [x] 1.1 Buat struktur direktori proyek sesuai desain (config/, auth/, pages/, api/, includes/, uploads/, assets/)
  - [x] 1.2 Buat file `config/db.php` dengan koneksi PDO ke MySQL via Laragon
  - [x] 1.3 Buat file SQL untuk membuat semua tabel (users, categories, items, item_photos, wishlists, chat_rooms, chat_messages, transactions, reviews, notifications)
  - [x] 1.4 Seed data kategori awal (Elektronik, Buku, Pakaian, Perabot, Olahraga, dll.)
  - [x] 1.5 Buat file `includes/functions.php` dengan helper functions (uploadPhoto, formatPrice, timeAgo, dll.)

- [x] 2. Autentikasi (Registrasi, Login, Logout)
  - [x] 2.1 Buat halaman `auth/register.php` dengan form registrasi (nama, email, password, konfirmasi password)
  - [x] 2.2 Implementasi validasi registrasi: semua field wajib, email unik, password match
  - [x] 2.3 Implementasi penyimpanan user dengan `password_hash($pass, PASSWORD_BCRYPT)`
  - [x] 2.4 Buat halaman `auth/login.php` dengan form login (email, password)
  - [x] 2.5 Implementasi verifikasi login dengan `password_verify()` dan pembuatan session
  - [x] 2.6 Buat `auth/logout.php` yang menghapus session dan redirect ke login
  - [x] 2.7 Buat `includes/auth_check.php` sebagai middleware cek session

- [x] 3. Navbar dan Layout Utama
  - [x] 3.1 Buat `includes/navbar.php` dengan semua elemen: logo, search bar, ikon chat/wishlist/notif/profil, tombol "+ Jual"
  - [x] 3.2 Implementasi dropdown profil dengan menu: Lihat Profil, Edit Profil, Pengaturan, Logout
  - [x] 3.3 Buat `includes/item_card.php` sebagai komponen kartu item reusable
  - [x] 3.4 Buat layout base dengan Bootstrap 5 yang di-include di semua halaman

- [x] 4. Halaman Home
  - [x] 4.1 Buat `pages/home.php` yang menampilkan item dengan status "Tersedia" diurutkan terbaru
  - [x] 4.2 Tampilkan item dalam grid kartu menggunakan `item_card.php`
  - [x] 4.3 Implementasi klik kartu → redirect ke item detail

- [x] 5. Manajemen Item (Sell Item & Item Detail)
  - [x] 5.1 Buat `pages/sell_item.php` dengan form: foto (multiple upload), judul, deskripsi, kategori, lokasi, harga
  - [x] 5.2 Implementasi validasi form sell item (semua field wajib, minimal 1 foto)
  - [x] 5.3 Implementasi upload foto dengan `move_uploaded_file`, validasi format (jpg/jpeg/png) dan ukuran (≤2MB)
  - [x] 5.4 Simpan listing ke tabel `items` dan foto ke `item_photos`, set status awal "Tersedia"
  - [x] 5.5 Buat `pages/item_detail.php` yang menampilkan galeri foto, detail item, info seller, tombol Chat Seller dan Wishlist
  - [x] 5.6 Implementasi lightbox untuk foto item (klik foto → tampilan diperbesar)
  - [x] 5.7 Nonaktifkan tombol "Chat Seller" jika status item "Terjual" atau "Dipesan"

- [x] 6. Fitur Chat Realtime
  - [x] 6.1 Buat `pages/chat.php` dengan layout dua kolom (daftar room kiri, area pesan kanan)
  - [x] 6.2 Buat `api/chat_poll.php` — endpoint GET yang mengembalikan pesan baru (id > last_id) dalam JSON
  - [x] 6.3 Buat `api/chat_send.php` — endpoint POST untuk menyimpan pesan baru ke DB
  - [x] 6.4 Buat `assets/js/chat.js` dengan AJAX polling setiap 3 detik menggunakan `setInterval`
  - [x] 6.5 Implementasi mark-as-read saat user membuka percakapan
  - [x] 6.6 Tampilkan timestamp pada setiap pesan dan bedakan visual pesan sent vs received
  - [x] 6.7 Buat `api/notif_count.php` untuk mengembalikan jumlah pesan dan notifikasi belum dibaca
  - [x] 6.8 Implementasi pembuatan chat room otomatis saat Buyer klik "Chat Seller" dari item detail

- [x] 7. Fitur Wishlist
  - [x] 7.1 Buat `pages/wishlist.php` yang menampilkan semua item tersimpan user
  - [x] 7.2 Buat `api/wishlist_toggle.php` — endpoint POST untuk tambah/hapus wishlist (idempotent)
  - [x] 7.3 Implementasi tombol hapus item dari wishlist
  - [x] 7.4 Tampilkan pesan "Belum ada barang yang disimpan" jika wishlist kosong

- [x] 8. Fitur Notifikasi
  - [x] 8.1 Buat `pages/notification.php` yang menampilkan daftar notifikasi diurutkan terbaru
  - [x] 8.2 Implementasi helper function untuk membuat notifikasi (dipanggil saat event: pesan baru, perubahan status, deal confirmation)
  - [x] 8.3 Implementasi mark-as-read saat notifikasi diklik dan redirect ke halaman relevan
  - [x] 8.4 Tampilkan badge unread count di navbar (dari `api/notif_count.php`)
  - [x] 8.5 Tampilkan pesan "Tidak ada notifikasi" jika kosong

- [x] 9. Filter dan Pencarian
  - [x] 9.1 Buat `pages/search.php` yang menampilkan hasil pencarian berdasarkan keyword
  - [x] 9.2 Buat Filter popup modal Bootstrap dengan opsi sort (Relevan, Terbaru, Harga Tertinggi, Harga Terendah) dan filter kategori
  - [x] 9.3 Buat `api/filter_search.php` — endpoint GET yang mengembalikan item sesuai keyword + filter dalam JSON
  - [x] 9.4 Buat `assets/js/filter.js` untuk update hasil pencarian tanpa reload penuh
  - [x] 9.5 Tampilkan jumlah total item ditemukan dan pesan "Tidak ada barang ditemukan" jika kosong

- [x] 10. Halaman Category
  - [x] 10.1 Buat `pages/category.php` yang menampilkan semua item dalam kategori yang dipilih
  - [x] 10.2 Tampilkan nama kategori sebagai judul halaman
  - [x] 10.3 Tampilkan pesan "Belum ada barang dalam kategori ini" jika kosong

- [x] 11. Profil dan Edit Profil
  - [x] 11.1 Buat `pages/profile.php` yang menampilkan foto profil, nama, bio, kontak, item dijual, item terjual, dan review diterima
  - [x] 11.2 Tampilkan rata-rata rating seller dari tabel reviews
  - [x] 11.3 Tampilkan tombol "Lihat Toko" saat mengunjungi profil user lain
  - [x] 11.4 Buat `pages/edit_profile.php` dengan form: foto profil, nama, bio, kontak
  - [x] 11.5 Implementasi upload foto profil dengan validasi format dan ukuran
  - [x] 11.6 Validasi nama lengkap tidak boleh kosong, simpan perubahan ke DB

- [x] 12. Halaman Seller Detail
  - [x] 12.1 Buat `pages/seller_detail.php` yang menampilkan profil lengkap seller: foto, nama, bio, rating, jumlah transaksi selesai, semua item dijual, semua review
  - [x] 12.2 Tampilkan item seller dalam kartu dengan badge status

- [x] 13. Pengaturan Akun (Settings)
  - [x] 13.1 Buat `pages/settings.php` dengan form ganti password dan toggle preferensi notifikasi
  - [x] 13.2 Implementasi validasi ganti password: verifikasi password lama, password baru match
  - [x] 13.3 Simpan password baru yang di-hash ke DB
  - [x] 13.4 Implementasi toggle notifikasi chat dan transaksi, simpan ke kolom `notif_chat` dan `notif_transaction`

- [x] 14. Transaksi dan Deal Confirmation
  - [x] 14.1 Buat `pages/transaction_history.php` yang menampilkan riwayat transaksi sebagai Buyer dan Seller
  - [x] 14.2 Implementasi tombol "Konfirmasi Terima Barang" untuk Buyer (ubah status → Selesai, badge → Terjual)
  - [x] 14.3 Implementasi perubahan badge item saat transaksi diproses (Tersedia → Dipesan) dan selesai (Dipesan → Terjual)
  - [x] 14.4 Buat notifikasi otomatis saat status transaksi berubah untuk semua pihak yang terlibat
  - [x] 14.5 Tampilkan tombol "Beri Ulasan" pada transaksi Selesai yang belum memiliki review

- [x] 15. Sistem Review
  - [x] 15.1 Buat form review dengan rating bintang (1–5, input radio + CSS) dan textarea komentar
  - [x] 15.2 Implementasi validasi: rating wajib dipilih
  - [x] 15.3 Simpan review ke DB, enforce constraint UNIQUE(transaction_id) untuk mencegah duplikasi
  - [x] 15.4 Tampilkan review pada halaman Profile dan Seller Detail

- [x] 16. Badge Status Item
  - [x] 16.1 Implementasi tampilan badge pada semua halaman yang menampilkan kartu item (Home, Search, Category, Wishlist, Profile, Seller Detail)
  - [x] 16.2 Styling badge: "Tersedia" (hijau), "Dipesan" (kuning/oranye), "Terjual" (merah/abu)

- [x] 17. Testing dan Finalisasi
  - [x] 17.1 Tulis unit tests PHPUnit untuk fungsi `uploadPhoto()` (format valid/invalid, ukuran valid/invalid)
  - [x] 17.2 Tulis unit tests untuk validasi rating (range 1–5)
  - [x] 17.3 Tulis integration test untuk password hash round-trip (register → verify)
  - [x] 17.4 Tulis integration test untuk wishlist idempotence (tambah dua kali → satu entry)
  - [x] 17.5 Tulis integration test untuk one-review-per-transaction constraint
  - [x] 17.6 Tulis integration test untuk chat polling (hanya pesan dengan id > last_id dikembalikan)
  - [x] 17.7 Lakukan manual testing checklist (chat realtime, upload foto, filter, badge transitions, notifikasi)
  - [x] 17.8 Pastikan semua halaman responsive di mobile dengan Bootstrap 5

# Requirements Document

## Introduction

Veteran Marketplace adalah platform jual-beli barang bekas berbasis web untuk mahasiswa UPN "Veteran" Jawa Timur. Platform ini memungkinkan mahasiswa untuk menjual dan membeli barang bekas secara sesama mahasiswa, dilengkapi dengan fitur chat realtime, wishlist, notifikasi, sistem ulasan, dan konfirmasi transaksi. Dibangun menggunakan HTML + Bootstrap (frontend), PHP native (backend), MySQL (database), dan AJAX polling untuk chat realtime. Proyek ini merupakan tugas UTS mata kuliah Pemrograman Web, Fakultas Ilmu Komputer UPN Veteran Jawa Timur.

---

## Glossary

- **System**: Aplikasi web Veteran Marketplace secara keseluruhan
- **User**: Mahasiswa UPN Veteran Jawa Timur yang telah terdaftar dan login
- **Guest**: Pengunjung yang belum login
- **Seller**: User yang memposting barang untuk dijual
- **Buyer**: User yang membeli atau berminat membeli barang
- **Item**: Barang bekas yang diiklankan oleh Seller
- **Listing**: Postingan iklan barang yang dibuat oleh Seller
- **Chat**: Fitur pesan langsung antara Buyer dan Seller menggunakan AJAX polling
- **Wishlist**: Daftar Item yang disimpan/difavoritkan oleh User
- **Notification**: Pemberitahuan aktivitas yang relevan untuk User
- **Transaction**: Proses jual-beli antara Buyer dan Seller
- **Review**: Ulasan yang diberikan Buyer kepada Seller setelah transaksi selesai
- **Deal Confirmation**: Konfirmasi penerimaan barang oleh Buyer sebelum meninggalkan ulasan
- **Badge**: Label status pada Item: "Tersedia", "Terjual", atau "Dipesan"
- **Filter**: Kriteria penyaringan dan pengurutan hasil pencarian Item
- **Category**: Kategori pengelompokan Item (misalnya: Elektronik, Buku, Pakaian, dll.)
- **Navbar**: Bilah navigasi utama yang tampil di semua halaman setelah login
- **Profile**: Halaman publik yang menampilkan informasi User, Item yang dijual, dan ulasan
- **Poller**: Mekanisme AJAX polling pada sisi klien untuk mengambil pesan Chat baru secara berkala

---

## Requirements

### Requirement 1: Registrasi Akun

**User Story:** Sebagai Guest, saya ingin mendaftar akun baru, agar saya dapat mengakses fitur marketplace sebagai User.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman registrasi dengan form yang memuat field: nama lengkap, email, password, dan konfirmasi password.
2. WHEN Guest mengisi form registrasi dan menekan tombol daftar, THE System SHALL memvalidasi bahwa semua field terisi dan email belum terdaftar sebelumnya.
3. IF email yang dimasukkan sudah terdaftar, THEN THE System SHALL menampilkan pesan kesalahan "Email sudah digunakan".
4. IF password dan konfirmasi password tidak cocok, THEN THE System SHALL menampilkan pesan kesalahan "Password tidak cocok".
5. WHEN validasi registrasi berhasil, THE System SHALL menyimpan data User ke database dengan password yang di-hash menggunakan algoritma bcrypt.
6. WHEN registrasi berhasil, THE System SHALL mengarahkan User ke halaman login dengan pesan konfirmasi "Registrasi berhasil".

---

### Requirement 2: Login dan Logout

**User Story:** Sebagai Guest, saya ingin login ke akun saya, agar saya dapat menggunakan fitur marketplace.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman login dengan form yang memuat field: email dan password.
2. WHEN Guest mengisi form login dan menekan tombol masuk, THE System SHALL memverifikasi email dan password terhadap data di database.
3. IF email tidak ditemukan atau password salah, THEN THE System SHALL menampilkan pesan kesalahan "Email atau password salah".
4. WHEN login berhasil, THE System SHALL membuat sesi (session) untuk User dan mengarahkan User ke halaman Home.
5. WHEN User menekan tombol logout, THE System SHALL menghapus sesi User dan mengarahkan User ke halaman login.
6. WHILE User belum login, THE System SHALL membatasi akses ke semua halaman yang memerlukan autentikasi dan mengarahkan Guest ke halaman login.

---

### Requirement 3: Navbar

**User Story:** Sebagai User, saya ingin memiliki navigasi utama yang konsisten, agar saya dapat berpindah antar fitur dengan mudah.

#### Acceptance Criteria

1. THE System SHALL menampilkan Navbar pada semua halaman yang memerlukan autentikasi, memuat: logo UPN Jatim, search bar, tombol filter, ikon chat, ikon wishlist, ikon notifikasi, ikon profil (dropdown), dan tombol "+ Jual".
2. WHEN User mengetik kata kunci di search bar dan menekan Enter atau tombol cari, THE System SHALL mengarahkan User ke halaman Search Results dengan parameter pencarian yang sesuai.
3. WHEN User menekan tombol filter di Navbar, THE System SHALL menampilkan Filter popup modal.
4. WHEN User menekan ikon chat, THE System SHALL mengarahkan User ke halaman Chat.
5. WHEN User menekan ikon wishlist, THE System SHALL mengarahkan User ke halaman Wishlist.
6. WHEN User menekan ikon notifikasi, THE System SHALL mengarahkan User ke halaman Notification.
7. WHEN User menekan ikon profil, THE System SHALL menampilkan dropdown menu dengan pilihan: Lihat Profil, Edit Profil, Pengaturan, dan Logout.
8. WHEN User menekan tombol "+ Jual", THE System SHALL mengarahkan User ke halaman Sell Item.

---

### Requirement 4: Halaman Home

**User Story:** Sebagai User, saya ingin melihat barang-barang unggulan di halaman utama, agar saya dapat menemukan Item yang menarik dengan cepat.

#### Acceptance Criteria

1. THE System SHALL menampilkan halaman Home yang memuat daftar Item yang direkomendasikan atau terbaru.
2. THE System SHALL menampilkan setiap Item dalam bentuk kartu (card) yang memuat: foto utama Item, judul, harga, lokasi, dan Badge status.
3. WHEN User menekan kartu Item, THE System SHALL mengarahkan User ke halaman Item Detail untuk Item tersebut.
4. THE System SHALL hanya menampilkan Item dengan Badge "Tersedia" pada halaman Home.
5. THE System SHALL menampilkan Item diurutkan berdasarkan waktu posting terbaru secara default.

---

### Requirement 5: Halaman Item Detail

**User Story:** Sebagai User, saya ingin melihat detail lengkap sebuah Item, agar saya dapat memutuskan apakah ingin membeli atau menghubungi Seller.

#### Acceptance Criteria

1. THE System SHALL menampilkan halaman Item Detail yang memuat: galeri foto Item, judul, deskripsi, kategori, lokasi, harga, Badge status, dan informasi ringkas Seller.
2. THE System SHALL menampilkan tombol "Chat Seller" pada halaman Item Detail.
3. WHEN User menekan tombol "Chat Seller", THE System SHALL membuka atau membuat sesi Chat antara User dan Seller untuk Item tersebut, lalu mengarahkan User ke halaman Chat.
4. WHEN User menekan foto Item, THE System SHALL menampilkan foto dalam tampilan yang diperbesar.
5. THE System SHALL menampilkan tombol tambah ke Wishlist pada halaman Item Detail.
6. WHEN User menekan tombol tambah ke Wishlist, THE System SHALL menyimpan Item ke Wishlist User dan mengubah ikon menjadi aktif.
7. IF Item memiliki Badge "Terjual" atau "Dipesan", THEN THE System SHALL menonaktifkan tombol "Chat Seller" dan menampilkan keterangan status Item.

---

### Requirement 6: Halaman Jual Barang (Sell Item)

**User Story:** Sebagai User, saya ingin memposting barang yang ingin saya jual, agar Buyer dapat menemukan dan menghubungi saya.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Sell Item dengan form yang memuat field: foto barang (multiple upload, minimal 1 foto), judul, deskripsi, kategori, lokasi, dan harga.
2. WHEN User mengisi form dan menekan tombol "Posting", THE System SHALL memvalidasi bahwa semua field wajib terisi dan minimal satu foto diunggah.
3. IF validasi gagal, THEN THE System SHALL menampilkan pesan kesalahan pada field yang tidak valid.
4. WHEN validasi berhasil, THE System SHALL menyimpan Listing ke database dengan Badge awal "Tersedia" dan mengarahkan User ke halaman Item Detail untuk Listing tersebut.
5. THE System SHALL mendukung unggahan foto dalam format JPG, JPEG, dan PNG dengan ukuran maksimal 2MB per foto.
6. IF format atau ukuran foto tidak sesuai, THEN THE System SHALL menampilkan pesan kesalahan yang menjelaskan batasan yang berlaku.

---

### Requirement 7: Badge Status Item

**User Story:** Sebagai User, saya ingin melihat status ketersediaan sebuah Item dengan jelas, agar saya tidak menghubungi Seller untuk barang yang sudah terjual.

#### Acceptance Criteria

1. THE System SHALL menampilkan Badge pada setiap Item dengan salah satu dari tiga nilai: "Tersedia", "Dipesan", atau "Terjual".
2. WHEN Listing baru dibuat oleh Seller, THE System SHALL menetapkan Badge Item sebagai "Tersedia".
3. WHEN Buyer memulai proses transaksi untuk sebuah Item, THE System SHALL mengubah Badge Item menjadi "Dipesan".
4. WHEN Deal Confirmation dilakukan oleh Buyer, THE System SHALL mengubah Badge Item menjadi "Terjual".
5. THE System SHALL menampilkan Badge pada kartu Item di halaman Home, Search Results, Category, Wishlist, dan Profile.

---

### Requirement 8: Fitur Chat

**User Story:** Sebagai User, saya ingin berkomunikasi langsung dengan Seller atau Buyer, agar saya dapat bernegosiasi dan mengatur transaksi.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Chat yang menampilkan daftar percakapan aktif User di sisi kiri dan area pesan di sisi kanan.
2. WHEN User mengirim pesan, THE System SHALL menyimpan pesan ke database dan menampilkan pesan tersebut di area percakapan tanpa memuat ulang halaman.
3. THE Poller SHALL mengambil pesan baru dari server setiap 3 detik menggunakan AJAX request.
4. WHEN pesan baru tersedia, THE Poller SHALL menampilkan pesan baru di area percakapan tanpa memuat ulang halaman.
5. THE System SHALL menampilkan timestamp untuk setiap pesan.
6. THE System SHALL menampilkan indikator visual yang membedakan pesan yang dikirim User dengan pesan yang diterima User.
7. WHEN User membuka percakapan, THE System SHALL menandai semua pesan yang belum dibaca dalam percakapan tersebut sebagai sudah dibaca.
8. THE System SHALL menampilkan jumlah pesan belum dibaca pada ikon chat di Navbar.

---

### Requirement 9: Halaman Wishlist

**User Story:** Sebagai User, saya ingin menyimpan Item yang saya minati, agar saya dapat menemukannya kembali dengan mudah.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Wishlist yang menampilkan semua Item yang telah disimpan oleh User.
2. THE System SHALL menampilkan setiap Item dalam Wishlist dalam bentuk kartu yang memuat: foto utama, judul, harga, dan Badge status.
3. WHEN User menekan kartu Item di halaman Wishlist, THE System SHALL mengarahkan User ke halaman Item Detail untuk Item tersebut.
4. THE System SHALL menampilkan tombol hapus pada setiap Item di Wishlist.
5. WHEN User menekan tombol hapus pada sebuah Item, THE System SHALL menghapus Item tersebut dari Wishlist User.
6. IF Wishlist User kosong, THEN THE System SHALL menampilkan pesan "Belum ada barang yang disimpan".

---

### Requirement 10: Halaman Notifikasi

**User Story:** Sebagai User, saya ingin menerima notifikasi aktivitas yang relevan, agar saya tidak melewatkan pesan atau pembaruan transaksi.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Notification yang menampilkan daftar notifikasi User diurutkan dari yang terbaru.
2. THE System SHALL membuat notifikasi untuk kejadian berikut: pesan Chat baru, perubahan status Badge Item milik Seller, dan permintaan Deal Confirmation untuk Buyer.
3. THE System SHALL menampilkan setiap notifikasi dengan: ikon jenis notifikasi, teks deskripsi, dan waktu notifikasi.
4. WHEN User menekan sebuah notifikasi, THE System SHALL mengarahkan User ke halaman yang relevan dan menandai notifikasi tersebut sebagai sudah dibaca.
5. THE System SHALL menampilkan jumlah notifikasi belum dibaca pada ikon notifikasi di Navbar.
6. IF tidak ada notifikasi, THEN THE System SHALL menampilkan pesan "Tidak ada notifikasi".

---

### Requirement 11: Halaman Profil

**User Story:** Sebagai User, saya ingin melihat profil publik seorang pengguna, agar saya dapat mengetahui reputasi dan barang yang mereka jual.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Profile yang menampilkan: foto profil, nama, bio, informasi kontak, daftar Item yang sedang dijual, daftar Item yang sudah terjual, dan daftar Review yang diterima.
2. THE System SHALL menampilkan rata-rata rating Seller berdasarkan semua Review yang diterima.
3. WHEN User mengunjungi halaman Profile milik User lain, THE System SHALL menampilkan tombol "Lihat Toko" yang mengarahkan ke halaman Seller Detail.
4. THE System SHALL menampilkan Item yang sedang dijual dengan Badge "Tersedia" dan Item yang sudah terjual dengan Badge "Terjual".

---

### Requirement 12: Halaman Edit Profil

**User Story:** Sebagai User, saya ingin memperbarui informasi profil saya, agar data saya selalu akurat dan terkini.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Edit Profile dengan form yang memuat field: foto profil (upload), nama lengkap, bio, dan informasi kontak.
2. WHEN User mengisi form dan menekan tombol simpan, THE System SHALL memvalidasi bahwa field nama lengkap tidak kosong.
3. IF validasi gagal, THEN THE System SHALL menampilkan pesan kesalahan pada field yang tidak valid.
4. WHEN validasi berhasil, THE System SHALL menyimpan perubahan ke database dan mengarahkan User ke halaman Profile dengan pesan konfirmasi "Profil berhasil diperbarui".
5. THE System SHALL mendukung unggahan foto profil dalam format JPG, JPEG, dan PNG dengan ukuran maksimal 2MB.

---

### Requirement 13: Halaman Pengaturan (Settings)

**User Story:** Sebagai User, saya ingin mengubah pengaturan akun saya, agar saya dapat menjaga keamanan dan preferensi notifikasi.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Settings dengan opsi: ganti password dan pengaturan notifikasi.
2. WHEN User mengisi form ganti password dengan password lama, password baru, dan konfirmasi password baru, lalu menekan tombol simpan, THE System SHALL memverifikasi bahwa password lama sesuai dengan data di database.
3. IF password lama tidak sesuai, THEN THE System SHALL menampilkan pesan kesalahan "Password lama salah".
4. IF password baru dan konfirmasi password baru tidak cocok, THEN THE System SHALL menampilkan pesan kesalahan "Konfirmasi password tidak cocok".
5. WHEN ganti password berhasil, THE System SHALL menyimpan password baru yang di-hash ke database dan menampilkan pesan konfirmasi "Password berhasil diubah".
6. THE System SHALL menyediakan toggle untuk mengaktifkan atau menonaktifkan notifikasi Chat dan notifikasi transaksi.
7. WHEN User mengubah preferensi notifikasi, THE System SHALL menyimpan preferensi tersebut ke database.

---

### Requirement 14: Filter dan Pencarian

**User Story:** Sebagai User, saya ingin mencari dan menyaring Item berdasarkan kriteria tertentu, agar saya dapat menemukan barang yang saya cari dengan cepat.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Search Results yang menampilkan Item berdasarkan kata kunci yang dimasukkan di search bar.
2. THE System SHALL menampilkan Filter popup modal yang memuat opsi pengurutan: Paling Relevan, Terbaru, Harga Tertinggi, Harga Terendah; dan opsi filter berdasarkan Category.
3. WHEN User menerapkan Filter, THE System SHALL memperbarui hasil pencarian sesuai dengan kriteria Filter yang dipilih tanpa memuat ulang halaman penuh.
4. THE System SHALL mendukung kombinasi pencarian kata kunci dengan Filter secara bersamaan.
5. IF tidak ada Item yang sesuai dengan kriteria pencarian, THEN THE System SHALL menampilkan pesan "Tidak ada barang yang ditemukan".
6. THE System SHALL menampilkan jumlah total Item yang ditemukan pada halaman Search Results.

---

### Requirement 15: Halaman Category

**User Story:** Sebagai User, saya ingin menelusuri Item berdasarkan kategori, agar saya dapat menemukan barang dalam kategori yang saya minati.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Category yang menampilkan semua Item dalam kategori yang dipilih.
2. THE System SHALL menampilkan nama kategori sebagai judul halaman.
3. THE System SHALL menampilkan Item dalam bentuk kartu yang memuat: foto utama, judul, harga, dan Badge status.
4. WHEN User menekan kartu Item, THE System SHALL mengarahkan User ke halaman Item Detail untuk Item tersebut.
5. IF tidak ada Item dalam kategori yang dipilih, THEN THE System SHALL menampilkan pesan "Belum ada barang dalam kategori ini".

---

### Requirement 16: Halaman Seller Detail

**User Story:** Sebagai User, saya ingin melihat profil publik lengkap seorang Seller, agar saya dapat menilai reputasi Seller sebelum bertransaksi.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Seller Detail yang menampilkan: foto profil Seller, nama, bio, rata-rata rating, jumlah transaksi selesai, semua Item yang sedang dijual, dan semua Review yang diterima Seller.
2. THE System SHALL menampilkan Item Seller dalam bentuk kartu dengan Badge status.
3. WHEN User menekan kartu Item di halaman Seller Detail, THE System SHALL mengarahkan User ke halaman Item Detail untuk Item tersebut.

---

### Requirement 17: Transaksi dan Deal Confirmation

**User Story:** Sebagai Buyer, saya ingin mengkonfirmasi penerimaan barang, agar transaksi dapat diselesaikan dan saya dapat memberikan ulasan.

#### Acceptance Criteria

1. THE System SHALL menyediakan halaman Transaction History yang menampilkan riwayat transaksi User sebagai Buyer dan sebagai Seller, masing-masing dengan status: "Menunggu", "Diproses", atau "Selesai".
2. WHEN Seller menyetujui transaksi untuk sebuah Item, THE System SHALL mengubah status transaksi menjadi "Diproses" dan mengubah Badge Item menjadi "Dipesan".
3. WHEN Buyer menekan tombol konfirmasi penerimaan barang pada transaksi dengan status "Diproses", THE System SHALL mengubah status transaksi menjadi "Selesai" dan mengubah Badge Item menjadi "Terjual".
4. WHEN status transaksi berubah, THE System SHALL membuat Notification untuk User yang terlibat dalam transaksi tersebut.
5. THE System SHALL menampilkan tombol "Beri Ulasan" pada transaksi dengan status "Selesai" yang belum memiliki Review dari Buyer.

---

### Requirement 18: Sistem Review

**User Story:** Sebagai Buyer, saya ingin memberikan ulasan kepada Seller setelah transaksi selesai, agar Buyer lain dapat mengetahui reputasi Seller.

#### Acceptance Criteria

1. THE System SHALL menyediakan form Review yang memuat: rating bintang (skala 1 sampai 5) dan komentar teks.
2. WHEN Buyer mengisi form Review dan menekan tombol kirim, THE System SHALL memvalidasi bahwa rating bintang telah dipilih.
3. IF rating bintang belum dipilih, THEN THE System SHALL menampilkan pesan kesalahan "Pilih rating terlebih dahulu".
4. WHEN validasi berhasil, THE System SHALL menyimpan Review ke database dan menampilkan Review pada halaman Profile dan Seller Detail milik Seller yang bersangkutan.
5. THE System SHALL membatasi satu Review per transaksi per Buyer.
6. IF Buyer mencoba mengirim Review kedua untuk transaksi yang sama, THEN THE System SHALL menampilkan pesan kesalahan "Anda sudah memberikan ulasan untuk transaksi ini".

---

## Skema Database MySQL

### Tabel: `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| name | VARCHAR(100) NOT NULL | |
| email | VARCHAR(150) UNIQUE NOT NULL | |
| password | VARCHAR(255) NOT NULL | Hash bcrypt |
| bio | TEXT | |
| contact | VARCHAR(100) | |
| profile_photo | VARCHAR(255) | Path file foto |
| notif_chat | TINYINT(1) DEFAULT 1 | Preferensi notifikasi chat |
| notif_transaction | TINYINT(1) DEFAULT 1 | Preferensi notifikasi transaksi |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Tabel: `categories`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| name | VARCHAR(100) NOT NULL | |
| slug | VARCHAR(100) UNIQUE NOT NULL | |

### Tabel: `items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| seller_id | INT NOT NULL | FK → users.id |
| category_id | INT NOT NULL | FK → categories.id |
| title | VARCHAR(200) NOT NULL | |
| description | TEXT NOT NULL | |
| price | DECIMAL(15,2) NOT NULL | |
| location | VARCHAR(150) NOT NULL | |
| status | ENUM('Tersedia','Dipesan','Terjual') DEFAULT 'Tersedia' | Badge status |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Tabel: `item_photos`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| item_id | INT NOT NULL | FK → items.id |
| photo_path | VARCHAR(255) NOT NULL | |
| is_primary | TINYINT(1) DEFAULT 0 | Foto utama untuk kartu |

### Tabel: `wishlists`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| user_id | INT NOT NULL | FK → users.id |
| item_id | INT NOT NULL | FK → items.id |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| UNIQUE KEY | (user_id, item_id) | Mencegah duplikasi |

### Tabel: `chat_rooms`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| buyer_id | INT NOT NULL | FK → users.id |
| seller_id | INT NOT NULL | FK → users.id |
| item_id | INT NOT NULL | FK → items.id |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| UNIQUE KEY | (buyer_id, seller_id, item_id) | Satu room per pasangan per item |

### Tabel: `chat_messages`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| room_id | INT NOT NULL | FK → chat_rooms.id |
| sender_id | INT NOT NULL | FK → users.id |
| message | TEXT NOT NULL | |
| is_read | TINYINT(1) DEFAULT 0 | |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Tabel: `transactions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| item_id | INT NOT NULL | FK → items.id |
| buyer_id | INT NOT NULL | FK → users.id |
| seller_id | INT NOT NULL | FK → users.id |
| status | ENUM('Menunggu','Diproses','Selesai') DEFAULT 'Menunggu' | |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

### Tabel: `reviews`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| transaction_id | INT NOT NULL UNIQUE | FK → transactions.id |
| buyer_id | INT NOT NULL | FK → users.id |
| seller_id | INT NOT NULL | FK → users.id |
| rating | TINYINT NOT NULL | Nilai 1–5 |
| comment | TEXT | |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

### Tabel: `notifications`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT AUTO_INCREMENT PRIMARY KEY | |
| user_id | INT NOT NULL | FK → users.id (penerima) |
| type | ENUM('chat','transaction','review') NOT NULL | |
| message | TEXT NOT NULL | |
| link | VARCHAR(255) | URL tujuan saat notifikasi diklik |
| is_read | TINYINT(1) DEFAULT 0 | |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | |

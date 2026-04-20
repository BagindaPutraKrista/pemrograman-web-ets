# Design Document: Veteran Marketplace

## Overview

Veteran Marketplace adalah aplikasi web jual-beli barang bekas untuk mahasiswa UPN "Veteran" Jawa Timur. Aplikasi ini dibangun sebagai proyek UTS mata kuliah Pemrograman Web dengan stack: HTML + Bootstrap 5 (frontend), PHP native (backend), MySQL via Laragon (database), dan AJAX polling setiap 3 detik untuk fitur chat realtime.

Arsitektur yang digunakan adalah **Multi-Page Application (MPA)** berbasis PHP tradisional — setiap halaman adalah file `.php` terpisah yang di-render server-side. Interaktivitas minimal (chat polling, filter tanpa reload) ditangani dengan JavaScript vanilla + AJAX.

### Tujuan Utama
- Mahasiswa dapat menjual dan membeli barang bekas sesama mahasiswa UPN Veteran Jatim
- Komunikasi langsung antara Buyer dan Seller melalui chat realtime
- Sistem reputasi Seller melalui review dan rating
- Manajemen transaksi dengan konfirmasi penerimaan barang

---

## Architecture

### Pola Arsitektur: PHP MPA (Multi-Page Application)

```
Browser (HTML + Bootstrap 5 + JS)
        │
        │ HTTP Request / AJAX
        ▼
PHP Pages (server-side rendering)
        │
        │ PDO / MySQLi
        ▼
MySQL Database (via Laragon)
```

### Struktur Direktori

```
veteran-marketplace/
├── index.php                  # Redirect ke home atau login
├── config/
│   └── db.php                 # Koneksi PDO ke MySQL
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── pages/
│   ├── home.php
│   ├── item_detail.php
│   ├── sell_item.php
│   ├── search.php
│   ├── category.php
│   ├── chat.php
│   ├── wishlist.php
│   ├── notification.php
│   ├── profile.php
│   ├── edit_profile.php
│   ├── settings.php
│   ├── seller_detail.php
│   └── transaction_history.php
├── api/
│   ├── chat_poll.php          # AJAX endpoint: ambil pesan baru
│   ├── chat_send.php          # AJAX endpoint: kirim pesan
│   ├── wishlist_toggle.php    # AJAX endpoint: tambah/hapus wishlist
│   ├── filter_search.php      # AJAX endpoint: filter + search
│   └── notif_count.php        # AJAX endpoint: jumlah notif belum dibaca
├── includes/
│   ├── navbar.php             # Komponen navbar
│   ├── item_card.php          # Komponen kartu item
│   ├── auth_check.php         # Middleware cek session
│   └── functions.php          # Helper functions
├── uploads/
│   ├── items/                 # Foto barang
│   └── profiles/              # Foto profil
└── assets/
    ├── css/
    │   └── style.css
    └── js/
        ├── chat.js            # Logic AJAX polling chat
        └── filter.js          # Logic filter modal
```

### Alur Request Umum

1. Browser mengirim HTTP request ke file `.php`
2. `auth_check.php` di-include untuk memverifikasi session
3. PHP query ke MySQL via PDO
4. PHP render HTML dengan data yang diambil
5. Response HTML dikirim ke browser

### Alur AJAX (Chat Polling)

1. `chat.js` menjalankan `setInterval` setiap 3000ms
2. AJAX GET ke `api/chat_poll.php?room_id=X&last_id=Y`
3. Server query pesan baru dengan `id > last_id`
4. Response JSON berisi array pesan baru
5. JavaScript append pesan ke DOM tanpa reload

---

## Components and Interfaces

### 1. Authentication Module (`auth/`)

**`register.php`**
- GET: Tampilkan form registrasi
- POST: Proses registrasi
  - Validasi: semua field wajib, email unik, password match
  - Hash password dengan `password_hash($pass, PASSWORD_BCRYPT)`
  - Insert ke tabel `users`
  - Redirect ke login dengan flash message

**`login.php`**
- GET: Tampilkan form login
- POST: Proses login
  - Query user by email
  - Verifikasi dengan `password_verify()`
  - Set `$_SESSION['user_id']` dan `$_SESSION['user_name']`
  - Redirect ke `pages/home.php`

**`logout.php`**
- `session_destroy()` → redirect ke login

### 2. Middleware (`includes/auth_check.php`)

```php
// Di-include di awal setiap halaman yang butuh autentikasi
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}
```

### 3. Navbar Component (`includes/navbar.php`)

Komponen yang di-include di semua halaman terautentikasi. Menampilkan:
- Logo UPN Jatim
- Search bar (form GET ke `pages/search.php`)
- Tombol filter (trigger modal Bootstrap)
- Ikon chat + badge unread count (dari `api/notif_count.php`)
- Ikon wishlist
- Ikon notifikasi + badge unread count
- Dropdown profil
- Tombol "+ Jual"

### 4. Item Card Component (`includes/item_card.php`)

Reusable card yang digunakan di Home, Search, Category, Wishlist, Profile, Seller Detail.

```php
// Dipanggil dengan: include_with_vars('item_card.php', ['item' => $item])
// Menampilkan: foto utama, judul, harga, lokasi, badge status
```

### 5. Chat Module (`pages/chat.php` + `api/`)

**`pages/chat.php`**
- Layout dua kolom: daftar room (kiri) + area pesan (kanan)
- Saat room dipilih, load pesan via AJAX

**`api/chat_poll.php`** (GET)
- Parameter: `room_id`, `last_id`
- Response JSON: `{ messages: [...], last_id: N }`

**`api/chat_send.php`** (POST)
- Parameter: `room_id`, `message`
- Insert ke `chat_messages`
- Response JSON: `{ success: true, message: {...} }`

**`assets/js/chat.js`**
```javascript
// Polling setiap 3 detik
setInterval(function() {
    fetch(`/api/chat_poll.php?room_id=${roomId}&last_id=${lastId}`)
        .then(r => r.json())
        .then(data => {
            data.messages.forEach(appendMessage);
            if (data.messages.length > 0) lastId = data.last_id;
        });
}, 3000);
```

### 6. File Upload Handler

Digunakan di `sell_item.php` dan `edit_profile.php`:

```php
function uploadPhoto($file, $dir) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['error' => 'Format tidak didukung'];
    if ($file['size'] > 2 * 1024 * 1024) return ['error' => 'Ukuran melebihi 2MB'];
    $filename = uniqid() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . $filename);
    return ['path' => $dir . $filename];
}
```

### 7. Filter & Search (`api/filter_search.php`)

- Parameter GET: `q` (keyword), `sort` (relevance/newest/price_high/price_low), `category_id`
- Query MySQL dengan `LIKE` untuk keyword, `ORDER BY` untuk sort
- Response JSON: array item cards
- `assets/js/filter.js` update DOM tanpa reload penuh

### 8. Transaction & Review Module

**`pages/transaction_history.php`**
- Tampilkan transaksi sebagai Buyer dan Seller
- Tombol "Konfirmasi Terima" untuk Buyer (status Diproses)
- Tombol "Beri Ulasan" untuk transaksi Selesai tanpa review

**Review Form**
- Rating bintang (1–5) dengan input radio + CSS styling
- Textarea komentar
- Validasi: rating wajib dipilih
- Constraint DB: `UNIQUE(transaction_id)` mencegah duplikasi

---

## Data Models

Data model mengikuti skema database yang telah didefinisikan di requirements. Berikut adalah relasi dan constraint penting:

### Entity Relationship

```
users ──< items (seller_id)
users ──< wishlists (user_id)
items ──< wishlists (item_id)
users ──< chat_rooms (buyer_id, seller_id)
items ──< chat_rooms (item_id)
chat_rooms ──< chat_messages (room_id)
users ──< transactions (buyer_id, seller_id)
items ──< transactions (item_id)
transactions ──1 reviews (transaction_id UNIQUE)
users ──< notifications (user_id)
items ──< item_photos (item_id)
categories ──< items (category_id)
```

### Constraint Penting

| Tabel | Constraint | Tujuan |
|---|---|---|
| `users` | `UNIQUE(email)` | Mencegah duplikasi akun |
| `wishlists` | `UNIQUE(user_id, item_id)` | Mencegah duplikasi wishlist |
| `chat_rooms` | `UNIQUE(buyer_id, seller_id, item_id)` | Satu room per pasangan per item |
| `reviews` | `UNIQUE(transaction_id)` | Satu review per transaksi |
| `items` | `status ENUM` | Hanya nilai valid yang diterima |

### PHP Data Access Pattern

Semua akses database menggunakan PDO dengan prepared statements:

```php
// Contoh query item dengan foto utama
$stmt = $pdo->prepare("
    SELECT i.*, ip.photo_path, u.name as seller_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    WHERE i.id = ?
");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Session Data

```php
$_SESSION['user_id']   // INT: ID user yang login
$_SESSION['user_name'] // STRING: Nama user untuk display
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Password hashing round-trip

*For any* plaintext password submitted during registration, the stored hash must satisfy `password_verify(plaintext, hash) === true`.

**Validates: Requirements 1.5**

### Property 2: Email uniqueness enforcement

*For any* two registered users, their email addresses must be distinct — attempting to register with an already-used email must be rejected.

**Validates: Requirements 1.3**

### Property 3: Wishlist uniqueness

*For any* user and item pair, adding the item to the wishlist multiple times must result in exactly one wishlist entry (idempotent insert).

**Validates: Requirements 9.5**

### Property 4: Badge status transition validity

*For any* item, its status must follow the valid transition path: `Tersedia → Dipesan → Terjual`. A direct transition from `Tersedia` to `Terjual` without going through `Dipesan` must not occur.

**Validates: Requirements 7.2, 7.3, 7.4**

### Property 5: One review per transaction

*For any* transaction, there must be at most one review. Attempting to submit a second review for the same transaction must be rejected.

**Validates: Requirements 18.5, 18.6**

### Property 6: Chat message ordering

*For any* chat room, messages returned by the polling endpoint must be ordered by `created_at` ascending — newer messages always appear after older ones.

**Validates: Requirements 8.3, 8.4**

### Property 7: File upload format and size validation

*For any* file upload (item photo or profile photo), only files with extension `jpg`, `jpeg`, or `png` and size ≤ 2MB must be accepted; all others must be rejected with an appropriate error message.

**Validates: Requirements 6.5, 6.6, 12.5**

### Property 8: Search filter consistency

*For any* search query with a keyword and/or filter applied, all returned items must satisfy the filter criteria — no item outside the selected category or not matching the keyword should appear in results.

**Validates: Requirements 14.1, 14.4**

### Property 9: Rating range validity

*For any* review, the rating value must be an integer in the range [1, 5] inclusive. Values outside this range must be rejected.

**Validates: Requirements 18.1, 18.2**

### Property 10: Unread count consistency

*For any* user, the unread notification count displayed on the navbar badge must equal the number of notifications in the `notifications` table where `user_id = current_user` AND `is_read = 0`.

**Validates: Requirements 10.5**

---

## Error Handling

### Validation Errors (Form)

Semua form menggunakan pola PHP flash message:
```php
// Set error
$_SESSION['errors']['email'] = 'Email sudah digunakan';
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;

// Display di view
if (isset($_SESSION['errors']['email'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['errors']['email'] . '</div>';
    unset($_SESSION['errors']['email']);
}
```

### Database Errors

- Semua query menggunakan try/catch PDOException
- Error production: log ke file, tampilkan pesan generik ke user
- Error development: tampilkan detail error (via `APP_DEBUG` config)

### File Upload Errors

| Kondisi | Pesan Error |
|---|---|
| Format tidak didukung | "Format foto tidak didukung. Gunakan JPG, JPEG, atau PNG." |
| Ukuran > 2MB | "Ukuran foto maksimal 2MB." |
| Upload gagal (server) | "Gagal mengunggah foto. Coba lagi." |

### AJAX Error Handling

```javascript
fetch(url)
    .then(r => {
        if (!r.ok) throw new Error('Server error');
        return r.json();
    })
    .catch(err => {
        console.error(err);
        // Tampilkan toast error ke user
        showToast('Gagal memuat data. Coba lagi.', 'danger');
    });
```

### Authentication Errors

- Akses halaman tanpa session → redirect ke login
- Session expired → redirect ke login dengan pesan "Sesi Anda telah berakhir"

### HTTP Error Pages

- 404: Halaman tidak ditemukan
- 403: Akses ditolak (misal: edit item milik orang lain)

---

## Testing Strategy

### Pendekatan Testing

Karena ini adalah proyek PHP native MPA dengan operasi CRUD, UI rendering, dan integrasi database, **property-based testing tidak sepenuhnya applicable** untuk semua lapisan. Testing difokuskan pada:

1. **Unit tests** untuk fungsi-fungsi helper murni (validasi, upload, format)
2. **Integration tests** untuk alur database (transaksi, review, wishlist)
3. **Example-based tests** untuk alur autentikasi dan form submission
4. **Manual testing** untuk UI/UX dan AJAX polling

### Unit Tests (PHPUnit)

Fungsi yang dapat diuji secara unit:

| Fungsi | Test |
|---|---|
| `uploadPhoto()` | Format valid/invalid, ukuran valid/invalid |
| Validasi email | Format email valid/invalid |
| Validasi password match | Password cocok/tidak cocok |
| Rating validation | Range 1–5 valid, di luar range invalid |
| Badge transition | Urutan status valid/invalid |

### Integration Tests

| Skenario | Verifikasi |
|---|---|
| Registrasi user baru | User tersimpan di DB, password ter-hash |
| Login dengan kredensial valid | Session terbuat |
| Tambah item ke wishlist dua kali | Hanya satu entry di DB |
| Submit review kedua untuk transaksi sama | Ditolak dengan error |
| Chat polling | Hanya pesan dengan `id > last_id` yang dikembalikan |

### Property-Based Tests (untuk fungsi murni)

Untuk fungsi-fungsi yang memiliki input space besar, gunakan library seperti **Eris** (PHP) atau implementasi sederhana dengan data generator:

| Property | Implementasi |
|---|---|
| Password hash round-trip | Generate random string → hash → verify |
| File extension validation | Generate random extensions → cek accept/reject |
| Search filter consistency | Generate random item set → filter → verifikasi semua hasil sesuai kriteria |
| Rating range | Generate random integers → cek valid/invalid |

Setiap property test dijalankan minimal **100 iterasi**.

Tag format: `Feature: veteran-marketplace, Property {N}: {property_text}`

### Manual Testing Checklist

- [ ] Registrasi dan login flow
- [ ] Upload foto item (format valid, invalid, ukuran batas)
- [ ] Chat polling (buka dua tab, kirim pesan, verifikasi muncul dalam 3 detik)
- [ ] Filter dan search (kombinasi keyword + kategori + sort)
- [ ] Badge status berubah sesuai alur transaksi
- [ ] Notifikasi muncul saat ada pesan baru / perubahan status
- [ ] Wishlist toggle (tambah dan hapus)
- [ ] Review form (submit valid, submit duplikat)
- [ ] Responsive layout di mobile (Bootstrap 5)

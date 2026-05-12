# 📌 PinShare — Website Sosmed ala Pinterest

Website berbagi inspirasi bergaya Pinterest, dibuat dengan **PHP + MySQL + HTML + CSS + JavaScript**.
Cocok untuk tugas SMK jurusan TKJ!

---

## 📁 STRUKTUR FOLDER

```
pinshare/
├── index.php            ← Halaman utama (masonry feed)
├── login.php            ← Login & Register
├── logout.php           ← Proses keluar
├── upload.php           ← Upload pin baru
├── pin.php              ← Detail pin + komentar
├── profile.php          ← Halaman profil user
├── settings.php         ← Edit profil & ganti password
├── notifications.php    ← Notifikasi
├── following.php        ← Feed dari user yang diikuti
├── edit_pin.php         ← Edit pin
│
├── css/
│   ├── style.css        ← Stylesheet utama
│   └── feed.css         ← Style halaman detail & profil
│
├── js/
│   ├── main.js          ← JS utama (toast, modal, like, save, follow)
│   └── feed.js          ← JS upload preview, komentar, profil
│
├── php/
│   ├── config.php       ← Koneksi DB & fungsi helper
│   ├── like.php         ← AJAX: toggle like
│   ├── save_pin.php     ← AJAX: simpan pin ke board
│   ├── get_boards.php   ← AJAX: ambil daftar board
│   ├── create_board.php ← AJAX: buat board baru
│   ├── add_comment.php  ← AJAX: tambah komentar
│   ├── delete_comment.php ← AJAX: hapus komentar
│   ├── delete_pin.php   ← Hapus pin
│   ├── follow.php       ← AJAX: follow/unfollow user
│   └── delete_account.php ← Hapus akun
│
├── includes/
│   └── navbar.php       ← Komponen navbar
│
├── sql/
│   └── database.sql     ← Script buat database
│
└── uploads/             ← Folder gambar (dibuat otomatis)
    └── avatars/         ← Folder avatar user
```

---

## 🚀 LANGKAH-LANGKAH INSTALASI

### Step 1 — Install XAMPP
1. Download XAMPP dari https://www.apachefriends.org
2. Install dan jalankan
3. Aktifkan **Apache** dan **MySQL** di XAMPP Control Panel

### Step 2 — Salin File Project
1. Copy folder `pinshare` ke `C:\xampp\htdocs\`
2. Pastikan strukturnya: `C:\xampp\htdocs\pinshare\`

### Step 3 — Buat Database
1. Buka browser → http://localhost/phpmyadmin
2. Klik tab **Import** di menu atas
3. Klik **Choose File** → pilih file `sql/database.sql`
4. Klik tombol **Go** / **Import**
5. Database `pinshare_db` akan terbuat otomatis

### Step 4 — Konfigurasi Database
Buka file `php/config.php`, sesuaikan baris ini:
```php
define('DB_HOST', 'localhost');   // biasanya localhost
define('DB_USER', 'root');        // username MySQL (default XAMPP: root)
define('DB_PASS', '');            // password MySQL (default XAMPP: kosong)
define('DB_NAME', 'pinshare_db'); // nama database
define('SITE_URL', 'http://localhost/pinshare');
```

### Step 5 — Buat Folder Uploads
Buat folder ini di dalam folder `pinshare`:
```
uploads/
uploads/avatars/
```
Pastikan folder ini bisa ditulis (write permission).

### Step 6 — Buka Website
Buka browser → **http://localhost/pinshare**

---

## ✨ FITUR LENGKAP

| Fitur | Keterangan |
|-------|------------|
| 📝 Register / Login | Akun dengan email & password (bcrypt) |
| 📌 Upload Pin | Upload gambar + judul + deskripsi + kategori |
| 🎯 Masonry Grid | Layout dinamis ala Pinterest |
| ❤️ Like Pin | Toggle like dengan AJAX (realtime) |
| 💾 Simpan ke Board | Simpan pin ke koleksi board pribadi |
| 💬 Komentar | Komentar & reply bertingkat |
| 👤 Follow User | Ikuti user lain |
| 🔔 Notifikasi | Like, komentar, follow, save |
| 🔍 Search | Cari pin berdasarkan judul/deskripsi |
| 🗂️ Kategori | Filter pin berdasarkan kategori |
| 🏠 Following Feed | Feed dari user yang diikuti |
| 👤 Profil User | Tampilkan pin, board, tersimpan |
| ⚙️ Edit Profil | Nama, bio, website, foto profil |
| 🔒 Ganti Password | Keamanan akun |
| ✏️ Edit Pin | Ubah judul, deskripsi, kategori |
| 🗑️ Hapus Pin | Hapus pin beserta file gambar |
| 📱 Responsive | Mobile-friendly |
| 🔗 Share Pin | Salin link pin |

---

## 🛠️ TEKNOLOGI YANG DIGUNAKAN

- **HTML5** — Struktur halaman
- **CSS3** — Tampilan, animasi, responsive (tanpa framework)
- **JavaScript (Vanilla)** — Interaktivitas, AJAX, DOM manipulation
- **PHP 7+** — Backend, session, koneksi database
- **MySQL** — Database (relasional)
- **XAMPP** — Local development server

---

## 🔐 KEAMANAN YANG SUDAH DITERAPKAN

- Password di-hash dengan `password_hash()` (bcrypt)
- Input disanitasi dengan `htmlspecialchars()` & `real_escape_string()`
- Prepared statements untuk mencegah SQL Injection
- Validasi tipe & ukuran file upload
- Session check untuk semua halaman yang memerlukan login
- CSRF protection dasar

---

## 💡 PENGEMBANGAN SELANJUTNYA (Ide Tambahan)

- [ ] Sistem DM (pesan langsung antar user)
- [ ] Dark mode
- [ ] Infinite scroll (load more tanpa reload)
- [ ] Tag/hastag pada pin
- [ ] Pin video (YouTube embed)
- [ ] Admin panel
- [ ] Share ke media sosial (API)
- [ ] Progressive Web App (PWA)

---

*Dibuat untuk tugas SMK TKJ — Semangat ngoding! 💪*

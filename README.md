# RuangBaca – Sistem Informasi Perpustakaan Digital

**RuangBaca** adalah aplikasi web **Sistem Informasi Perpustakaan Digital** berbasis **PHP Native** dan **PDO MySQL** yang dirancang dengan pendekatan editorial hangat (*Warm Paper & Deep Navy*). Aplikasi ini memungkinkan pengelolaan koleksi buku digital berformat PDF secara terpusat oleh Administrator serta memberikan akses katalog, pembaca buku daring (*PDF Reader*), pengunduhan dokumen, dan pencatatan riwayat aktivitas baca/unduh bagi Anggota perpustakaan.

---

## Daftar Isi

1. [Deskripsi RuangBaca](#1-deskripsi-ruangbaca)
2. [Tujuan Aplikasi](#2-tujuan-aplikasi)
3. [Fitur Utama](#3-fitur-utama)
4. [Teknologi yang Digunakan](#4-teknologi-yang-digunakan)
5. [Hak Akses Pengguna (Role-Based Access Control)](#5-hak-akses-pengguna-role-based-access-control)
6. [Struktur Folder Proyek](#6-struktur-folder-proyek)
7. [Persyaratan Instalasi](#7-persyaratan-instalasi)
8. [Instalasi pada XAMPP](#8-instalasi-pada-xampp)
9. [Pembuatan Database MySQL](#9-pembuatan-database-mysql)
10. [Import Skema Database (`database.sql` & `database_seed.sql`)](#10-import-skema-database-databasesql--database_seedsql)
11. [Konfigurasi Koneksi PDO (`config/database.php`)](#11-konfigurasi-koneksi-pdo-configdatabasephp)
12. [Cara Membuat Akun Admin Pertama Secara Aman](#12-cara-membuat-akun-admin-pertama-secara-aman)
13. [Cara Menjalankan Aplikasi](#13-cara-menjalankan-aplikasi)
14. [Pengamanan Penyimpanan Berkas (`storage/`)](#14-pengamanan-penyimpanan-berkas-storage)
15. [Informasi Deployment Online & Kompatibilitas Hosting](#15-informasi-deployment-online--kompatibilitas-hosting)

---

## 1. Deskripsi RuangBaca

**RuangBaca** menyediakan ekosistem perpustakaan digital yang mengutamakan kenyamanan membaca dan kemudahan pengelolaan koleksi akademik maupun literatur umum. Setiap berkas buku digital (PDF) dan gambar sampul disimpan secara terstruktur di dalam direktori `storage/` yang dilindungi dari akses URL langsung, dan disajikan melalui *PHP streaming endpoint* yang terverifikasi hak aksesnya.

---

## 2. Tujuan Aplikasi

- Menyediakan sarana distribusi literatur digital yang rapi, terorganisasi berdasarkan kategori, dan mudah dicari berdasarkan judul maupun penulis.
- Memfasilitasi anggota perpustakaan untuk membaca buku secara langsung di peramban (*in-browser PDF Reader*) atau mengunduh salinan PDF untuk pembelajaran mandiri.
- Mencatat riwayat aktivitas membaca dan mengunduh setiap anggota secara akurat dengan mekanisme deduplikasi sesi baca.
- Menerapkan standar keamanan aplikasi web modern pada PHP Native, meliputi **PDO Prepared Statements**, **CSRF Token Protection**, **XSS Output Escaping**, **Password Hashing (`bcrypt`)**, **Session Cookie Hardening (`HttpOnly`, `SameSite=Lax`)**, dan **Validasi MIME Fisik (`finfo`)**.

---

## 3. Fitur Utama

- **Autentikasi & Keamanan Sesi**:
  - Registrasi akun Anggota baru dengan validasi panjang karakter UTF-8 dan kecocokan kata sandi.
  - Login dengan proteksi *session fixation* (`session_regenerate_id(true)`) dan pesan kesalahan generik untuk mencegah *user enumeration*.
  - Logout aman berbasis metode `POST` disertai verifikasi token CSRF.
- **Katalog & Eksplorasi Koleksi (`catalog.php`, `detail_book.php`)**:
  - Pencarian *real-time* berdasarkan kata kunci judul atau nama penulis.
  - Penyaringan (*filtering*) instan berdasarkan *pill* kategori buku.
  - Pengurutan koleksi berdasarkan waktu terbaru (`terbaru`), abjad judul (`judul`), atau tahun terbit (`tahun`).
  - Halaman detail buku yang menampilkan metadata lengkap (penerbit, tahun terbit, kategori, ukuran berkas PDF, dan sinopsis).
- **Pembaca Buku Digital & Unduhan (`read_book.php`, `stream_book.php`, `download_book.php`)**:
  - **PDF Reader Interaktif** dengan kontrol perbesaran (*zoom* `85%`, `100%`, `110%`, `125%`, `150%`), tombol `+`/`−`, dan mode perluasan area baca (*Lebar halaman*).
  - Pengunduhan berkas PDF dengan nama file yang telah disanitasi.
- **Riwayat Aktivitas Baca & Unduh (`history.php`)**:
  - Pencatatan otomatis setiap kali anggota membaca (`read`) atau mengunduh (`download`) buku.
  - Kebijakan deduplikasi riwayat baca: maksimal **1 catatan `read` per pengguna dan buku yang sama dalam rentang waktu 10 menit** agar pemuatan ulang (*refresh*) halaman tidak menduplikasi riwayat.
- **Panel Manajemen Admin (`dashboard.php`, `books.php`, `categories.php`)**:
  - Ringkasan statistik koleksi buku, total kategori, jumlah anggota terdaftar, total aktivitas akses, serta daftar aktivitas terbaru.
  - **CRUD Buku Lengkap**: Tambah buku, edit metadata, ganti sampul/PDF, dan hapus buku dengan kebijakan **RESTRICT** (buku yang sudah memiliki riwayat aktivitas anggota dilindungi agar data riwayat tetap utuh).
  - **CRUD Kategori Lengkap**: Tambah, edit, dan hapus kategori dengan pratinjau langsung (*live preview*) serta proteksi penghapusan kategori yang masih digunakan oleh buku.
- **Profil & Pengaturan Akun (`profile.php`)**:
  - Pembaruan nama lengkap dan penggantian kata sandi dengan verifikasi kata sandi saat ini.

---

## 4. Teknologi yang Digunakan

| Komponen | Teknologi |
| :--- | :--- |
| **Backend** | PHP Native (Kompatibel dengan PHP 8.0+ / 8.1+ / 8.2+) |
| **Database Layer** | PDO MySQL (`PDO::ATTR_EMULATE_PREPARES => false`, `utf8mb4`) |
| **Database Server** | MySQL 5.7+ / MariaDB 10.4+ |
| **Frontend** | HTML5 Semantik, CSS3 Custom Properties (Responsive Desktop & Mobile), Vanilla JavaScript |
| **Tipografi** | Google Fonts (`Fraunces`, `Plus Jakarta Sans`, `JetBrains Mono`) |
| **Web Server Lokal** | Apache (XAMPP) dengan dukungan `.htaccess` |

---

## 5. Hak Akses Pengguna (Role-Based Access Control)

Aplikasi membagi hak akses menjadi **3 tingkatan** yang diverifikasi di sisi server melalui `includes/auth.php`:

| Fitur / Halaman | Guest (Belum Login) | Member (`role = 'member'`) | Admin (`role = 'admin'`) |
| :--- | :---: | :---: | :---: |
| Melihat Katalog & Cari Buku (`catalog.php`) | Ya | Ya | Ya |
| Melihat Detail Buku & Sampul (`detail_book.php`, `cover.php`) | Ya | Ya | Ya |
| Registrasi & Login (`register.php`, `login.php`) | Ya | Dialihkan ke Dashboard | Dialihkan ke Dashboard |
| Dashboard (`dashboard.php`) | Dialihkan ke Login | Dashboard Anggota | Dashboard Panel Admin |
| Membaca & Mengunduh PDF (`read_book.php`, `stream_book.php`, `download_book.php`) | Dialihkan ke Login | Ya | Ya |
| Riwayat Aktivitas Pribadi (`history.php`) | Dialihkan ke Login | Ya | Ya |
| Profil & Ubah Password (`profile.php`) | Dialihkan ke Login | Ya | Ya |
| Manajemen Buku — CRUD (`books.php`, `add_book.php`, `edit_book.php`, `delete_book.php`) | Dialihkan ke Login | Ditolak (`HTTP 403`) | Ya |
| Manajemen Kategori — CRUD (`categories.php`, `add_category.php`, `edit_category.php`, `delete_category.php`) | Dialihkan ke Login | Ditolak (`HTTP 403`) | Ya |

---

## 6. Struktur Folder Proyek

```text
perpustakaan-digital/
├── assets/
│   ├── css/
│   │   └── style.css               # Stylesheet utama (Desktop, Tablet, & Mobile)
│   ├── img/
│   │   └── .gitkeep                # Direktori aset gambar statis
│   └── js/
│       └── app.js                  # Interaktivitas UI (PDF Reader zoom, drawer mobile, live preview)
├── config/
│   ├── database.example.php        # Template konfigurasi koneksi PDO MySQL (aman untuk Git)
│   └── database.php                # Konfigurasi koneksi PDO lokal (dikecualikan oleh .gitignore)
├── includes/
│   └── auth.php                    # Manajemen session, autentikasi, RBAC, CSRF, XSS, & layout helper
├── storage/
│   ├── .htaccess                   # Proteksi Apache agar isi folder storage tidak bisa diakses langsung
│   ├── books/
│   │   └── .gitkeep                # Penyimpanan berkas PDF buku (isi file dikecualikan dari Git)
│   └── covers/
│       └── .gitkeep                # Penyimpanan gambar sampul buku (isi file dikecualikan dari Git)
├── .gitignore                      # Aturan pengecualian file sensitif, backup, dan upload
├── add_book.php                    # Halaman Admin: Tambah buku baru + upload sampul & PDF
├── add_category.php                # Halaman Admin: Tambah kategori buku baru
├── books.php                       # Halaman Admin: Daftar & manajemen koleksi buku
├── catalog.php                     # Halaman Publik/Member: Katalog buku, pencarian, & filter kategori
├── categories.php                  # Halaman Admin: Daftar & manajemen kategori buku
├── cover.php                       # Endpoint aman untuk menyajikan gambar sampul buku
├── dashboard.php                   # Halaman Dashboard (otomatis menyesuaikan tampilan Admin / Member)
├── database.sql                    # Skema struktur tabel MySQL/MariaDB tanpa data pribadi
├── database_seed.sql               # Data demonstrasi sintetis (kategori & akun demo)
├── delete_book.php                 # Endpoint POST Admin: Hapus buku (kebijakan RESTRICT terhadap riwayat)
├── delete_category.php             # Endpoint POST Admin: Hapus kategori (jika tidak digunakan buku)
├── detail_book.php                 # Halaman Publik/Member: Informasi lengkap buku
├── download_book.php               # Endpoint Member/Admin: Unduh PDF & catat riwayat 'download'
├── edit_book.php                   # Halaman Admin: Edit metadata buku & pembaruan sampul/PDF
├── edit_category.php               # Halaman Admin: Edit nama & deskripsi kategori
├── history.php                     # Halaman Member/Admin: Riwayat aktivitas baca & unduh pribadi
├── index.php                       # Titik masuk utama (mengarahkan ke dashboard.php atau catalog.php)
├── login.php                       # Halaman masuk pengguna
├── logout.php                      # Endpoint POST untuk mengakhiri sesi pengguna secara aman
├── profile.php                     # Halaman profil & penggantian kata sandi
├── read_book.php                   # Halaman antarmuka pembaca PDF (PDF Reader)
├── README.md                       # Dokumentasi proyek
├── register.php                    # Halaman pendaftaran akun Anggota baru
└── stream_book.php                 # Endpoint streaming PDF ke PDF Reader & pencatatan riwayat 'read'
```

---

## 7. Persyaratan Instalasi

Pastikan lingkungan pengembangan lokal atau server Anda memenuhi spesifikasi berikut:
- **PHP**: Versi **8.0** atau lebih baru (direkomendasikan PHP 8.0 / 8.1 / 8.2).
- **Ekstensi PHP Wajib**:
  - `pdo_mysql` (koneksi database MySQL/MariaDB menggunakan PDO)
  - `fileinfo` (validasi tipe MIME fisik berkas PDF dan gambar sampul menggunakan `finfo`)
  - `mbstring` (perhitungan panjang karakter multibyte UTF-8 dan manipulasi teks)
- **Database**: MySQL 5.7+ atau MariaDB 10.4+ dengan dukungan mesin penyimpanan `InnoDB` dan karakter set `utf8mb4`.
- **Web Server**: Apache HTTP Server (disertakan dalam paket **XAMPP**).

---

## 8. Instalasi pada XAMPP

1. Unduh dan pasang **XAMPP** (pastikan komponen **Apache** dan **MySQL** terpilih).
2. Clone atau salin folder proyek ini ke dalam direktori `htdocs` pada instalasi XAMPP Anda:
   ```bash
   cd C:\xampp\htdocs
   git clone <url-repository-anda> perpustakaan-digital
   ```
3. Pastikan folder proyek berada di lokasi:
   ```text
   C:\xampp\htdocs\perpustakaan-digital
   ```
4. Buka **XAMPP Control Panel**, lalu klik tombol **Start** pada modul **Apache** dan **MySQL**.

---

## 9. Pembuatan Database MySQL

1. Buka peramban web dan akses **phpMyAdmin** melalui alamat:
   ```text
   http://localhost/phpmyadmin
   ```
2. Klik menu **Databases** (Basis Data) di bagian atas.
3. Masukkan nama database:
   ```text
   perpustakaan_digital
   ```
4. Pilih *Collation* **`utf8mb4_unicode_ci`**, lalu klik **Create** (Buat).

---

## 10. Import Skema Database (`database.sql` & `database_seed.sql`)

1. Di dalam **phpMyAdmin**, klik database **`perpustakaan_digital`** yang baru dibuat.
2. Pilih tab **Import** di bagian atas halaman.
3. Klik **Choose File** (Pilih File) dan pilih berkas **`database.sql`** dari folder proyek `perpustakaan-digital`, lalu klik **Go** / **Import** di bagian bawah untuk membuat keempat tabel (`categories`, `users`, `books`, dan `book_access_history`).
4. *(Opsional — untuk data demonstrasi)* Ulangi langkah **Import** dan pilih berkas **`database_seed.sql`** untuk mengisi 6 kategori dasar beserta akun demonstrasi sintetis.

---

## 11. Konfigurasi Koneksi PDO (`config/database.php`)

Karena berkas `config/database.php` dikecualikan dari Git (`.gitignore`) demi melindungi kredensial, Anda perlu membuatnya dari berkas template `config/database.example.php`:

1. Salin berkas `config/database.example.php` menjadi `config/database.php`:
   - **Melalui Command Prompt / PowerShell (Windows):**
     ```powershell
     Copy-Item config\database.example.php config\database.php
     ```
   - **Melalui Terminal (Linux / macOS):**
     ```bash
     cp config/database.example.php config/database.php
     ```
2. Buka berkas `config/database.php` dengan editor teks dan sesuaikan kredensial MySQL Anda (untuk instalasi standar XAMPP):
   ```php
   $host = "localhost";
   $dbname = "perpustakaan_digital";
   $username = "root";
   $password = "";
   ```

---

## 12. Cara Membuat Akun Admin Pertama Secara Aman

Demi keamanan, file `database.sql` dan `database_seed.sql` tidak menyertakan akun bawaan atau kata sandi default. Pendaftaran melalui halaman `register.php` secara otomatis memberikan hak akses **`member`**. Untuk menyiapkan akun **Administrator (`admin`)** pertama Anda secara aman, ikuti langkah berikut:

1. Buka `http://localhost/perpustakaan-digital/register.php` dan daftarkan akun baru menggunakan nama lengkap, alamat email, serta kata sandi yang kuat (minimal 8 karakter).
2. Buka **phpMyAdmin**, pilih database `perpustakaan_digital`, lalu jalankan perintah SQL berikut pada tab **SQL** (sesuaikan alamat email dengan akun yang baru Anda daftarkan):
   ```sql
   UPDATE users
   SET role = 'admin'
   WHERE email = 'email_admin_anda@example.com';
   ```
3. Apabila Anda sedang dalam keadaan masuk (*login*), keluar (*Logout*) terlebih dahulu lalu masuk kembali melalui `login.php` agar sesi Anda diperbarui menjadi **Panel Admin**.

---

## 13. Cara Menjalankan Aplikasi

Setelah layanan Apache dan MySQL berjalan di XAMPP serta konfigurasi `config/database.php` selesai:

1. Buka peramban web dan kunjungi alamat:
   ```text
   http://localhost/perpustakaan-digital/
   ```
2. Pengunjung yang belum login akan diarahkan ke halaman **Katalog Buku** (`catalog.php`).
3. Masuk sebagai **Admin** untuk mengelola kategori (`categories.php`) dan mengunggah koleksi buku PDF beserta sampulnya (`add_book.php`).
4. Masuk sebagai **Member** untuk membaca buku secara daring (`read_book.php`), mengunduh PDF (`download_book.php`), dan memantau catatan aktivitas di halaman **Riwayat** (`history.php`).

---

## 14. Pengamanan Penyimpanan Berkas (`storage/`)

RuangBaca memisahkan penyimpanan berkas sampul dan dokumen PDF ke dalam dua subdirektori:
- `storage/covers/` — menyimpan gambar sampul (`JPG`, `PNG`, `WEBP`, maksimal **2 MB**).
- `storage/books/` — menyimpan dokumen buku (`PDF`, maksimal **10 MB** pada tambah buku / **20 MB** pada pembaruan buku).

Lapisan pengamanan yang diterapkan:
1. **Blokir Akses Langsung (`.htaccess`)**: Berkas `storage/.htaccess` berisi aturan `Deny from all` sehingga berkas di dalam `storage/books/` maupun `storage/covers/` tidak dapat diakses langsung melalui URL peramban.
2. **Validasi MIME Fisik (`finfo_file`)**: Aplikasi memeriksa *magic bytes* fisik berkas menggunakan ekstensi `Fileinfo` (bukan sekadar memeriksa ekstensi nama file).
3. **Penamaan Berkas Acak Kriptografis (`random_bytes`)**: Nama berkas asli dari pengguna tidak pernah dipakai langsung di sistem file server, melainkan diganti dengan string heksadesimal acak sepanjang 32 karakter (`bin2hex(random_bytes(16))`).
4. **Validasi Path (`realpath`)**: Endpoint `cover.php`, `stream_book.php`, `download_book.php`, dan `delete_book.php` memverifikasi bahwa lokasi fisik berkas benar-benar berada di dalam direktori `storage/` untuk mencegah serangan *Path Traversal*.

---

## 15. Informasi Deployment Online & Kompatibilitas Hosting

Saat melakukan *deployment* ke layanan *shared hosting* berbasis PHP & MySQL (seperti **InfinityFree**, **Hostinger**, atau **cPanel Hosting**):

1. **Penyesuaian Konfigurasi Database**:
   - Buat database MySQL melalui panel kontrol hosting Anda dan impor `database.sql`.
   - Buat berkas `config/database.php` di server dan isi `$host`, `$dbname`, `$username`, serta `$password` sesuai kredensial MySQL yang diberikan penyedia hosting.
2. **Izin Direktori (*Folder Permissions*)**:
   - Pastikan direktori `storage/books/` dan `storage/covers/` memiliki izin tulis (*writable*, umumnya `0755` pada lingkungan Linux shared hosting).
3. **Catatan Khusus Mengenai Kebijakan Berkas PDF pada InfinityFree (Free Tier)**:
   - Layanan *free hosting* **InfinityFree** menerapkan kebijakan server yang memblokir penyimpanan atau pengunduhan berkas berformat `.pdf` secara langsung di server gratis mereka (sering kali dialihkan atau dihapus otomatis oleh filter ekstensi mereka).
   - Apabila aplikasi di-deploy pada InfinityFree Free Tier, disarankan menggunakan penyimpanan objek eksternal yang aman (seperti Cloudflare R2, AWS S3, atau Backblaze B2 dengan *signed URL* / *proxy stream*) atau menggunakan layanan hosting PHP yang mengizinkan penyimpanan dokumen PDF.

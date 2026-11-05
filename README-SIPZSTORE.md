# Aplikasi Keuangan & Pesanan SipzStore

**Rancang Bangun Aplikasi Keuangan Berbasis Web sebagai Upaya Peningkatan Akuntabilitas pada UMKM SIPZ Store Bogor**

Aplikasi web Laravel + MySQL untuk SipzStore (percetakan ID card, holder, kartu debit instan, tumbler). Aplikasi ini dipakai tiga peran: **Owner, Admin, dan Operator**.

---

## 1. Kesesuaian dengan Proposal PKM

| No | Permasalahan mitra (Tabel 1 proposal) | Fitur di aplikasi |
|---|---|---|
| 1 | Pesanan dicatat manual di papan tulis, buku, kuitansi | **Pesanan Baru**: nomor pesanan otomatis, item, spesifikasi, file desain, target selesai, nota siap cetak |
| 2 | Satu mesin, antrean diatur manual | **Antrean Mesin**: jadwal otomatis sesuai kapasitas harian, urutan pertama masuk pertama dikerjakan, hanya 1 pesanan boleh diproduksi sekaligus |
| 3 | Pesan ulang harus scroll chat WhatsApp | **Riwayat pelanggan** + tombol **Pesan ulang** yang menyalin item & file desain lama |
| 4 | Pemasukan & pengeluaran belum terstruktur | **Kas Masuk & Keluar**: DP/pelunasan tercatat otomatis, pengeluaran wajib nota, saldo per akun (tunai/bank/QRIS) |
| 5 | Koordinasi Owner–Admin–Operator lisan | **Papan alur pesanan** (Masuk → Antre → Diproduksi → Siap Diambil → Diserahkan) dengan hak akses per peran |
| 6 | Data tidak bisa dipantau dari jauh | Web responsif, bisa dibuka dari HP; tampilan khusus ponsel untuk Operator |
| Saran 2 | Hak akses, enkripsi sandi, keamanan | Login per peran, kata sandi terenkripsi (bcrypt), batas percobaan login, akun bisa dinonaktifkan |
| Saran 3 | Laporan laba rugi, arus kas, rekap, ekspor | **Laporan**: Laba Rugi, Arus Kas, Penjualan per Produk; cetak/simpan PDF & ekspor Excel |

### Fitur akuntabilitas (inti judul)

1. **Nomor bukti otomatis & berurutan**: PSN-YYMMDD-NN untuk pesanan, KM-YYMM-NNNN untuk kas masuk, KK-YYMM-NNNN untuk kas keluar.
2. **Nama pencatat** tersimpan di setiap transaksi.
3. **Nota wajib** dilampirkan untuk setiap pengeluaran.
4. **Persetujuan Owner** untuk pengeluaran Admin di atas batas, bawaan Rp 500.000 dan bisa diubah.
5. **Tidak ada hapus transaksi.** Kesalahan dibatalkan Owner dengan alasan, dan transaksinya tetap tampil dicoret.
6. **Pesanan tidak bisa diserahkan sebelum lunas.**
7. **Jejak Audit**: siapa melakukan apa, kapan, dari IP mana, termasuk nilai sebelum dan sesudah perubahan.
8. **Laporan dari data asli**: hanya transaksi berstatus "Tercatat" yang dihitung. Laporan cetak memuat kolom tanda tangan Admin (penyusun) dan Owner (penyetuju).

---

## 2. Kebutuhan

- **XAMPP** (PHP 8.2 atau lebih baru, dan MySQL/MariaDB). Cek versi PHP dengan `php -v`.
- **Composer**.

---

## 3. Cara Pasang (XAMPP + MySQL)

### Langkah 1 — Buat project Laravel baru

Buka Command Prompt di folder tempat menyimpan project, lalu jalankan:

```bash
composer create-project laravel/laravel sipzstore
cd sipzstore
```

### Langkah 2 — Salin isi ZIP

Ekstrak ZIP ini **langsung di dalam folder `sipzstore`**. Jika ditanya menimpa file (`routes/web.php`, `bootstrap/app.php`, `app/Models/User.php`, `app/Providers/AppServiceProvider.php`, `database/seeders/DatabaseSeeder.php`), pilih **Ya / Replace**. Ini aman karena project-nya masih baru.

### Langkah 3 — Buat database MySQL

1. Buka **XAMPP Control Panel**, lalu klik **Start** pada **MySQL** (dan **Apache** bila ingin membuka phpMyAdmin).
2. Buka **http://localhost/phpmyadmin**.
3. Klik **New** / **Baru**, isi nama database `sipzstore`, pilih collation `utf8mb4_unicode_ci`, lalu klik **Create**.

### Langkah 4 — Atur file `.env`

Buka file `.env` di folder `sipzstore`. Cari baris `DB_CONNECTION=sqlite`, lalu ganti blok database menjadi:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sipzstore
DB_USERNAME=root
DB_PASSWORD=
```

Hapus baris lama yang diawali `# DB_...` jika ada, supaya tidak dobel. Ubah juga:

```env
APP_NAME=SipzStore
APP_LOCALE=id
APP_URL=http://127.0.0.1:8000
```

### Langkah 5 — Buat tabel & data contoh

```bash
php artisan config:clear
php artisan migrate:fresh --seed
```

Hasilnya: semua tabel di database `sipzstore`, tiga akun demo, produk, pelanggan, dan transaksi contoh 6 bulan terakhir.

> **Peringatan:** `migrate:fresh` **menghapus semua data**. Pakai hanya saat pemasangan pertama atau untuk mengulang demo. Untuk pemakaian sungguhan, lihat bagian 6.

### Langkah 6 — Jalankan

```bash
php artisan serve
```

Buka **http://127.0.0.1:8000**.

---

## 4. Akun Demo

Kata sandi semua akun: **password**

| Peran | Username | Yang bisa dilakukan |
|---|---|---|
| Owner | `anggi` | Semua menu, menyetujui pengeluaran, membatalkan transaksi, laporan, audit, pengguna, pengaturan |
| Admin | `rina` | Pesanan, pelanggan, mencatat kas (pengeluaran besar menunggu Owner) |
| Operator | `dedi` | Antrean mesin: mulai & selesaikan produksi (tampilan ponsel) |

Setelah demo, ganti kata sandi lewat menu **Profil** (klik nama di kiri bawah).

---

## 5. Skenario Demo (±10 menit)

1. **Login sebagai Admin (`rina`)** → **Pesanan Baru**, pilih "SMAN 1 Kemang". Riwayat muncul, lalu klik **Pesan ulang**. Item dan desain otomatis tersalin. Pilih DP 50%, lalu Simpan. Nomor pesanan dan jadwal mesin muncul otomatis.
2. Klik **Cetak nota** untuk menunjukkan nota bernomor.
3. **Kas Masuk & Keluar** → **Catat pengeluaran** Rp 1.200.000 dengan foto nota. Statusnya menjadi **Menunggu Owner**.
4. **Login sebagai Operator (`dedi`)**, sebaiknya dari HP. Di **Antrean Mesin**, klik **Tandai selesai**, lalu **Mulai produksi** untuk pesanan berikutnya. Coba mulai dua pesanan sekaligus: sistem menolak karena hanya ada 1 mesin.
5. **Login sebagai Owner (`anggi`)**. Di Dashboard, **Setujui** pengeluaran tadi. Lalu **batalkan** satu transaksi dengan alasan, dan tunjukkan bahwa transaksi itu tetap tampil dicoret.
6. Buka pesanan yang sudah selesai dan coba **Serahkan**. Sistem menolak sebelum lunas. Catat pelunasan, lalu serahkan.
7. **Laporan** → Laba Rugi, Arus Kas, Penjualan per Produk → **Cetak / PDF** (ada kolom tanda tangan) dan **Ekspor Excel**.
8. **Jejak Audit**: tunjukkan semua langkah di atas tercatat beserta nama dan jamnya.

---

## 6. Memulai Pemakaian Sungguhan (tanpa data contoh)

```bash
php artisan migrate:fresh
php artisan tinker
```

Lalu ketik di tinker (ganti nama, username, dan sandi):

```php
App\Models\User::create(['name' => 'Anggi', 'username' => 'anggi', 'role' => 'owner', 'email' => 'anggi@sipzstore.local', 'password' => 'GantiSandiIni123']);
```

Ketik `exit`. Setelah itu login sebagai Owner, lalu tambahkan **Produk & Harga**, **Pengguna** (Admin & Operator), dan **Pengaturan** (kapasitas mesin, batas persetujuan, alamat toko).

---

## 7. Struktur Database

| Tabel | Isi |
|---|---|
| `users` | Pengguna + `role` (owner/admin/operator), `username`, `is_active`, `last_login_at` |
| `customers` | Pelanggan (perorangan/instansi), kontak, alamat |
| `products` | Daftar produk & harga per kategori laporan |
| `orders` | Pesanan: kode, pelanggan, status, tanggal, jadwal mesin, total, dibayar, file desain, pembuat, operator |
| `order_items` | Rincian item pesanan (nama, spesifikasi, jumlah, harga, subtotal) |
| `cash_transactions` | Buku kas: kode bukti, jenis masuk/keluar, kategori, jumlah, akun, nota, status, pencatat, penyetuju, pembatal + alasan |
| `audit_logs` | Jejak audit: pengguna, aksi, data yang diubah, nilai lama/baru, IP, waktu |
| `settings` | Pengaturan toko yang bisa diubah Owner |

Relasi: `customers 1–n orders`, `orders 1–n order_items`, `orders 1–n cash_transactions` (pembayaran), `users 1–n orders / cash_transactions / audit_logs`.

Skema lengkap dalam format MySQL ada di `database/struktur-tabel-mysql.sql`, bisa dipakai untuk lampiran laporan.

---

## 8. Daftar File

```
app/Http/Controllers/   AuthController, DashboardController, OrderController, ProductionController,
                        CustomerController, CashController, ReportController, AuditController,
                        ProductController, UserController, SettingController, ProfileController
app/Http/Middleware/RoleMiddleware.php   Hak akses per peran
app/Models/             User, Customer, Product, Order, OrderItem, CashTransaction, AuditLog, Setting
app/Services/           OrderService (alur pesanan), QueueService (antrean mesin),
                        CashService (aturan kas & persetujuan), ReportService (laporan),
                        CodeGenerator (nomor bukti otomatis)
app/Support/            Audit (pencatat jejak), Fmt (format rupiah & tanggal Indonesia)
bootstrap/app.php       Daftar middleware peran + trustProxies (demo lewat Cloudflare)
config/sipz.php         Nilai bawaan pengaturan, kategori kas, kategori produk
database/migrations/2026_11_05_000001_create_sipz_tables.php
database/seeders/DatabaseSeeder.php      Data demo
lang/id/validation.php  Pesan kesalahan bahasa Indonesia
public/css/sipz.css     Tampilan (warna merek di bagian :root)
public/js/sipz.js       Interaksi form (tanpa library)
resources/views/        Semua halaman Blade
routes/web.php          Semua route + pembagian hak akses
```

---

## 9. Demo Online Lewat Cloudflare Tunnel

Sama seperti proyek sebelumnya. Jalankan `php artisan serve` di terminal 1, lalu di terminal 2:

```bash
cloudflared tunnel --url http://localhost:8000
```

Bagikan link `https://....trycloudflare.com` yang muncul. Pengaturan `trustProxies` sudah ada di `bootstrap/app.php`.

---

## 10. Masalah Umum

| Gejala | Solusi |
|---|---|
| `SQLSTATE[HY000] [2002]` / *Connection refused* | MySQL di XAMPP belum di-Start, atau `DB_HOST`/`DB_PORT` di `.env` salah |
| `Unknown database 'sipzstore'` | Database belum dibuat di phpMyAdmin (Langkah 3) |
| `Access denied for user 'root'` | Isi `DB_PASSWORD` sesuai password MySQL Anda |
| Tampilan polos tanpa warna | Pastikan `public/css/sipz.css` ada, lalu tekan Ctrl + F5 |
| Upload file gagal | Batas file: nota 5 MB, desain 20 MB. Cek juga `upload_max_filesize` di `php.ini` XAMPP (minimal 20M) |
| Perubahan `.env` tidak berpengaruh | Jalankan `php artisan config:clear` |

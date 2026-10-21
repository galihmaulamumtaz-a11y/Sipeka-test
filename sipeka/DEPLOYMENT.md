# Panduan Deployment & Instalasi SIPEKA
## Sistem Pengaduan Kampus — Jurusan Teknologi Informasi Politeknik Negeri Malang

Dokumen ini berisi panduan komprehensif untuk menjalankan aplikasi **SIPEKA** baik di lingkungan pengembangan lokal (*Local Development*) maupun di server produksi (*Live Cloud Hosting*).

---

## 1. Opsi A: Menjalankan Langsung di Browser (Frontend Dev / Presentasi Cepat)

SIPEKA dilengkapi dengan arsitektur **Intelligent Dual-Engine**:
Jika Anda membuka file langsung di browser tanpa web server (menggunakan protokol `file:///`), sistem secara otomatis beralih ke engine *Reactive Local Storage* dengan data *mock* yang persisten.

1. Buka folder proyek:
   ```
   sipeka/
   ```
2. Buka file `auth/login.php` atau `index.php` di Google Chrome / Microsoft Edge / Firefox.
3. Anda dapat langsung menguji alur login, registrasi akun baru, pembuatan laporan, pelacakan status linimasa, serta verifikasi pada sisi unit.

---

## 2. Opsi B: Menggunakan Laragon (Rekomendasi Utama Mahasiswa & Backend)

Laragon adalah lingkungan pengembangan PHP lokal standar yang paling mudah dikonfigurasi di Windows:

### Langkah-langkah:
1. **Clone atau Letakkan Folder Proyek:**
   Pindahkan folder `sipeka` ke direktori root Laragon:
   ```
   C:\laragon\www\sipeka
   ```
2. **Aktifkan Ekstensi PostgreSQL:**
   - Buka aplikasi Laragon.
   - Klik menu **Menu > PHP > Extensions**.
   - Pastikan opsi **pdo_pgsql** dan **pgsql** dicentang (aktif).
3. **Konfigurasi Database PostgreSQL di Laragon:**
   - Jalankan service PostgreSQL di Laragon (atau melalui pgAdmin).
   - Buat database baru bernama `sipeka_db`.
   - Buka SQL Query Tool dan jalankan isi file `database/init.sql` untuk membuat 11 tabel berelasi beserta data awal (*seed data*).
4. **Konfigurasi Environment `.env`:**
   Salin `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   Sesuaikan kredensial di file `.env`:
   ```ini
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=sipeka_db
   DB_USERNAME=postgres
   DB_PASSWORD=secret
   ```
5. **Akses Aplikasi:**
   Buka browser dan kunjungi:
   ```
   http://localhost/sipeka/
   ```
   Sistem akan secara otomatis me-redirect ke `http://localhost/sipeka/auth/login.php`.

---

## 3. Opsi C: Menggunakan Docker Compose (Apache + PHP 8.2 + PostgreSQL 15 + Adminer)

Jika komputer Anda atau dosen penguji memiliki **Docker Desktop**:

1. **Jalankan Seluruh Layanan:**
   Di terminal root proyek `sipeka`:
   ```bash
   docker compose up -d
   ```
   Perintah ini akan secara otomatis:
   - Membangun container `sipeka_web` (Apache + PHP 8.2 + `pdo_pgsql`).
   - Menjalankan container `sipeka_db` (PostgreSQL 15) dan otomatis mengeksekusi `database/init.sql`.
   - Menjalankan container `sipeka_adminer` (GUI Database berbasis web).

2. **Akses Layanan:**
   - **Aplikasi Web SIPEKA:** [http://localhost:8000](http://localhost:8000)
   - **Database GUI (Adminer):** [http://localhost:8080](http://localhost:8080)
     - *System:* PostgreSQL
     - *Server:* `db`
     - *Username:* `postgres`
     - *Password:* `secret`
     - *Database:* `sipeka_db`

3. **Menghentikan Container:**
   ```bash
   docker compose down
   ```

---

## 4. Opsi D: Deployment ke Cloud Hosting (Railway / Render / VPS)

Untuk memenuhi target penilaian akhir PBL di mana aplikasi harus dapat diakses secara daring:

### Deployment ke Railway.app (Rekomendasi Cepat & Gratis):
1. **Buat Database PostgreSQL:**
   - Login ke [Railway.app](https://railway.app).
   - Klik **New Project > Provision PostgreSQL**.
   - Di tab **Data**, jalankan query dari file `database/init.sql`.
2. **Deploy Aplikasi Web:**
   - Hubungkan repositori GitHub proyek SIPEKA.
   - Di menu *Variables*, masukkan variabel environment dari database PostgreSQL yang dibuat:
     ```
     DB_HOST=<PGHOST dari Railway>
     DB_PORT=<PGPORT dari Railway>
     DB_DATABASE=<PGDATABASE dari Railway>
     DB_USERNAME=<PGUSER dari Railway>
     DB_PASSWORD=<PGPASSWORD dari Railway>
     ```
   - Railway akan secara otomatis membaca `Dockerfile` dan mendeploy aplikasi.
3. **Generate Domain:**
   - Buka tab *Settings > Networking > Generate Domain*.
   - Aplikasi SIPEKA Anda langsung *live* di internet dengan HTTPS gratis!

---

## 5. Akun Pengujian Resmi (Seed Accounts)

Seluruh password akun demo awal di-*hash* menggunakan standar **BCRYPT**: `secret`.

| No | Peran / Unit | Email / Akun | Password | Wewenang Penanganan |
|---|---|---|---|---|
| 1 | **Mahasiswa (Pelapor)** | `254107020095@student.polinema.ac.id` | `secret` | Mengajukan laporan, memantau riwayat linimasa |
| 2 | **Sekretariat Jurusan (Sekjur)** | `sekjur@polinema.ac.id` | `secret` | Menangani Fasilitas Kampus (AC, Lab, Proyektor) |
| 3 | **Admin Program Studi** | `prodi@polinema.ac.id` | `secret` | Menangani Pelayanan Akademik & KRS |
| 4 | **Ketua Jurusan (Kajur)** | `kajur@polinema.ac.id` | `secret` | Menangani Perilaku Dosen & Pungli |
| 5 | **Ketua Program Studi (KPS)** | `kps@polinema.ac.id` | `secret` | Menangani Pelanggaran Mahasiswa |

*Catatan: Anda juga dapat mendaftarkan akun mahasiswa baru langsung melalui formulir [auth/register.php](auth/register.php).*

---

## 6. Verifikasi & Audit Keamanan

SIPEKA telah melalui pengujian audit otomatis meliputi:
- **Proteksi SQL Injection:** 100% query database menggunakan PDO Prepared Statements dengan parameter binding terpisah.
- **Proteksi XSS:** Fungsi sanitasi `e()` mengubah karakter entitas HTML pada seluruh tampilan dinamis.
- **Proteksi CSRF:** Token kriptografis 64-karakter heksadesimal pada setiap transaksi pengiriman data.
- **Keamanan Unggah Berkas:** Pengecekan MIME asli menggunakan ekstensi `finfo`, batas berkas 5 MB, dan pencegahan path traversal.
- **Pengujian Audit:** Jalankan perintah `php scratch/test_security_audit.php` untuk memvalidasi seluruh rangkaian tes keamanan.

# SIPEKA - Sistem Pengaduan Kampus JTI Polinema

[![Stack](https://img.shields.io/badge/Stack-HTML5%20%7C%20CSS3%20%7C%20JavaScript%20%7C%20PHP%20%7C%20PostgreSQL-blue)](https://github.com)
[![OBE/OBA](https://img.shields.io/badge/Kurikulum-PBL%20Semester%203%20(TI--2F)-green)](https://github.com)

Aplikasi Web Sistem Pengaduan Terpusat berbasis **Direct Routing** untuk Jurusan Teknologi Informasi, Politeknik Negeri Malang. Menampung dan mendisposisikan laporan mahasiswa secara langsung ke 4 unit penanggung jawab (**Sekjur, Admin Prodi, Kajur, KPS**) tanpa melalui admin utama.

---

## 👥 Profil Tim Pengembang (PBL TI 2F)

| Nama Mahasiswa | NIM | Peran Utama | Tanggung Jawab |
| :--- | :--- | :--- | :--- |
| **Salsabila Widyadhana** | 254107020200 | Project Manager | Jadwal sprint 16 minggu, dokumentasi, koordinasi dosen pengampu |
| **Calvin Ardiansyah** | 254107020214 | Backend Developer | PostgreSQL, skema database, autentikasi, API PHP native (PDO) |
| **Ghafin Fitrah Ramadhan** | 254107020170 | UI/UX Designer | Riset pengguna, alur user flow, perancangan prototipe Figma |
| **Galih Maula Mumtaza** | 254107020095 | Frontend Developer | Implementasi HTML5/CSS3/JS responsif, interaktivitas linimasa tracking |

---

## 🏛️ Struktur Arsitektur Proyek (Professional Directory Layout)

```text
sipeka/
├── .env.example               # Template variabel environment (DB, Port, Session)
├── .gitignore                 # File pengabaian Git (node_modules, logs, data docker)
├── package.json               # Konfigurasi skrip dev & dependensi frontend
├── Dockerfile                 # Konfigurasi container PHP 8.2 Apache + driver pdo_pgsql
├── docker-compose.yml         # Orkestrasi container Web + PostgreSQL + Adminer GUI
├── README.md                  # Dokumentasi teknis & panduan instalasi tim
│
├── database/
│   └── init.sql               # Skema 11 tabel relasional PostgreSQL + seed data awal
│
├── assets/
│   ├── css/
│   │   └── style.css          # Desain sistem global (warna kampus, responsive, modul)
│   └── js/
│       ├── store.js           # Central Data Store (localStorage persistence & mock API)
│       ├── ui.js              # Komponen interaktif (Toast notification, Modals, Badges)
│       └── app.js             # Controller utama navigasi, filter tabel & tracking linimasa
│
├── auth/
│   └── login.html             # M01: Split-screen Login Mahasiswa & Role Switcher
│
├── mahasiswa/
│   ├── dashboard.html         # M02: Statistik real-time & daftar pengaduan terbaru
│   ├── kategori.html          # M03: Pemilihan 5 kategori direct routing
│   ├── buat-laporan.html      # M04: Formulir laporan lengkap + preview upload bukti
│   ├── konfirmasi.html        # M05: Ringkasan laporan sebelum dikirim
│   └── riwayat.html           # M06: Pencarian langsung, filter status & modal linimasa
│
└── unit/
    ├── dashboard.html         # S03: Dashboard verifikasi unit, filter per divisi
    ├── verifikasi.html        # S04: Detail pengesahan laporan valid / tolak
    └── penanganan.html        # S05: Form pencatatan tindakan & upload bukti penyelesaian
```

---

## ⚡ Cara Menjalankan Aplikasi

Kamu memiliki 3 opsi fleksibel untuk menjalankan proyek ini:

### Opsi 1: Langsung via Browser (Paling Cepat / Zero Install)
1. Buka folder `sipeka/auth/login.html` langsung di browser Chrome/Edge (klik dua kali).
2. Sistem langsung berjalan penuh dengan **localStorage store** yang mensimulasikan penyimpanan database, linimasa tracking, pencarian, dan validasi unit!

### Opsi 2: Menggunakan Node Dev Server
```bash
npm run dev
# Server lokal akan aktif di http://localhost:3000
```

### Opsi 3: Menggunakan Docker (Stack Penuh PHP + PostgreSQL + Adminer)
Khusus untuk kolaborasi lanjutan dengan Calvin (Backend):
```bash
# Jalankan seluruh service container di background
docker compose up -d

# Akses layanan:
# - Web Application : http://localhost:8000
# - Adminer (DB GUI): http://localhost:8080 (Server: db, User: postgres, Pass: secret, DB: sipeka_db)
```

---

## 🛠️ Alur Kolaborasi Git (Git Branching Model)

Untuk menjaga kode tetap rapi selama 16 minggu perkuliahan:

```
[ main ]          -> Kode stabil siap demo UTS / UAS
   ↑
[ develop ]       -> Cabang integrasi mingguan
   ├── feature/frontend-auth      (Galih)
   ├── feature/frontend-tracking  (Galih)
   ├── feature/backend-auth       (Calvin)
   └── feature/backend-crud       (Calvin)
```

1. **Buat cabang baru**: `git checkout -b feature/nama-fitur`
2. **Simpan perubahan**: `git commit -m "feat: tambah modal tracking linimasa"`
3. **Gabungkan ke develop**: Buat Pull Request (PR) ke cabang `develop`.

---

## 🔄 Panduan Reverse Engineering untuk Galih

Jika ingin mempelajari bagaimana kode ini bekerja:
1. **Bagaimana data disimpan tanpa database sungguhan?**  
   Buka `assets/js/store.js`. Fungsi `localStorage.setItem()` digunakan untuk menyimpan daftar tiket. Ketika kamu menambah laporan baru, data otomatis tersimpan di memori browser.
2. **Bagaimana angka dashboard terupdate otomatis?**  
   Buka fungsi `getStats()` di `store.js`. Fungsi ini menghitung jumlah laporan berdasarkan status (`Menunggu Validasi`, `Dalam Penanganan`, `Selesai`, `Tidak Valid`).
3. **Bagaimana menghubungkan ke PHP Calvin nanti?**  
   Di file `store.js`, nantinya fungsi `getAllReports()` cukup diubah memanggil `fetch('/api/laporan.php')` yang dibuat oleh Calvin. Tampilan HTML dan CSS tidak perlu dirombak ulang sama sekali!

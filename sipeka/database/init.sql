-- ==============================================================================
-- SIPEKA (Sistem Pengaduan Kampus) - Database Schema (PostgreSQL)
-- Berdasarkan Dokumen Proposal PBL TI 2F (11 Entitas Relasional)
-- ==============================================================================

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id_user SERIAL PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('mahasiswa', 'unit', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Unit Penanggung Jawab
CREATE TABLE IF NOT EXISTS unit (
    id_unit SERIAL PRIMARY KEY,
    id_user INT REFERENCES users(id_user) ON DELETE SET NULL,
    kode_unit VARCHAR(50) UNIQUE NOT NULL,
    nama_unit VARCHAR(100) NOT NULL,
    deskripsi TEXT
);

-- 3. Tabel Mahasiswa
CREATE TABLE IF NOT EXISTS mahasiswa (
    id_mahasiswa SERIAL PRIMARY KEY,
    id_user INT NOT NULL REFERENCES users(id_user) ON DELETE CASCADE,
    nim VARCHAR(20) UNIQUE NOT NULL,
    nama_mahasiswa VARCHAR(150) NOT NULL,
    program_studi VARCHAR(100) NOT NULL,
    jurusan VARCHAR(100) DEFAULT 'Teknologi Informasi',
    nomor_telepon VARCHAR(20)
);

-- 4. Tabel Kategori Pengaduan
CREATE TABLE IF NOT EXISTS kategori (
    id_kategori SERIAL PRIMARY KEY,
    id_unit_default INT REFERENCES unit(id_unit),
    kode_kategori VARCHAR(50) UNIQUE NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT
);

-- 5. Tabel Status Laporan
CREATE TABLE IF NOT EXISTS status_laporan (
    id_status SERIAL PRIMARY KEY,
    nama_status VARCHAR(50) UNIQUE NOT NULL,
    keterangan TEXT
);

-- 6. Tabel Laporan (Entitas Inti)
CREATE TABLE IF NOT EXISTS laporan (
    id_laporan SERIAL PRIMARY KEY,
    no_tiket VARCHAR(25) UNIQUE NOT NULL,
    id_mahasiswa INT NOT NULL REFERENCES mahasiswa(id_mahasiswa),
    id_kategori INT NOT NULL REFERENCES kategori(id_kategori),
    id_unit INT NOT NULL REFERENCES unit(id_unit),
    judul_laporan VARCHAR(255) NOT NULL,
    lokasi VARCHAR(150) NOT NULL,
    tanggal_kejadian DATE NOT NULL,
    kronologi TEXT NOT NULL,
    tanggal_lapor TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Menunggu Validasi',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Tabel Bukti Laporan
CREATE TABLE IF NOT EXISTS bukti_laporan (
    id_bukti SERIAL PRIMARY KEY,
    id_laporan INT NOT NULL REFERENCES laporan(id_laporan) ON DELETE CASCADE,
    nama_file VARCHAR(255) NOT NULL,
    path_file VARCHAR(255) NOT NULL,
    jenis_file VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Tabel Riwayat Status (Linimasa)
CREATE TABLE IF NOT EXISTS riwayat_status (
    id_riwayat SERIAL PRIMARY KEY,
    id_laporan INT NOT NULL REFERENCES laporan(id_laporan) ON DELETE CASCADE,
    id_status INT REFERENCES status_laporan(id_status),
    status_text VARCHAR(50) NOT NULL,
    tanggal_perubahan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT
);

-- 9. Tabel Tanggapan
CREATE TABLE IF NOT EXISTS tanggapan (
    id_tanggapan SERIAL PRIMARY KEY,
    id_laporan INT NOT NULL REFERENCES laporan(id_laporan) ON DELETE CASCADE,
    id_unit INT NOT NULL REFERENCES unit(id_unit),
    isi_tanggapan TEXT NOT NULL,
    tanggal_tanggapan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Tabel Penanganan
CREATE TABLE IF NOT EXISTS penanganan (
    id_penanganan SERIAL PRIMARY KEY,
    id_laporan INT NOT NULL REFERENCES laporan(id_laporan) ON DELETE CASCADE,
    id_unit INT NOT NULL REFERENCES unit(id_unit),
    tanggal_penanganan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keterangan_penanganan TEXT NOT NULL
);

-- 11. Tabel Bukti Penanganan
CREATE TABLE IF NOT EXISTS bukti_penanganan (
    id_bukti_penanganan SERIAL PRIMARY KEY,
    id_penanganan INT NOT NULL REFERENCES penanganan(id_penanganan) ON DELETE CASCADE,
    file VARCHAR(255) NOT NULL,
    tanggal_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_laporan_no_tiket ON laporan(no_tiket);
CREATE INDEX IF NOT EXISTS idx_laporan_id_mhs ON laporan(id_mahasiswa);
CREATE INDEX IF NOT EXISTS idx_laporan_id_unit ON laporan(id_unit);
CREATE INDEX IF NOT EXISTS idx_laporan_status ON laporan(status);

-- ==============================================================================
-- SEED DATA AWAL (Initial Master Data & Accounts)
-- Password default: 'secret' (hashed dengan BCRYPT)
-- ==============================================================================

-- 1. Seed Users (Mahasiswa & 4 Unit Kampus)
INSERT INTO users (id_user, nama, email, password, role) VALUES
(1, 'Galih Maula Mumtaza', '254107020095@student.polinema.ac.id', '$2y$10$5CRb1XiehaOkLBj.md3CveHKOQpmgSo2f8AAWOh19T9wd9BcW1wlS', 'mahasiswa'),
(2, 'Staff Sekretariat Jurusan', 'sekjur@polinema.ac.id', '$2y$10$5CRb1XiehaOkLBj.md3CveHKOQpmgSo2f8AAWOh19T9wd9BcW1wlS', 'unit'),
(3, 'Admin Program Studi', 'prodi@polinema.ac.id', '$2y$10$5CRb1XiehaOkLBj.md3CveHKOQpmgSo2f8AAWOh19T9wd9BcW1wlS', 'unit'),
(4, 'Ketua Jurusan TI', 'kajur@polinema.ac.id', '$2y$10$5CRb1XiehaOkLBj.md3CveHKOQpmgSo2f8AAWOh19T9wd9BcW1wlS', 'unit'),
(5, 'Ketua Program Studi TI', 'kps@polinema.ac.id', '$2y$10$5CRb1XiehaOkLBj.md3CveHKOQpmgSo2f8AAWOh19T9wd9BcW1wlS', 'unit')
ON CONFLICT (id_user) DO UPDATE SET password = EXCLUDED.password;

-- 2. Seed Profil Mahasiswa
INSERT INTO mahasiswa (id_mahasiswa, id_user, nim, nama_mahasiswa, program_studi, jurusan, nomor_telepon) VALUES
(1, 1, '254107020095', 'Galih Maula Mumtaza', 'D4 Teknik Informatika', 'Teknologi Informasi', '081234567890')
ON CONFLICT (id_mahasiswa) DO NOTHING;

-- 3. Seed Unit
INSERT INTO unit (id_unit, id_user, kode_unit, nama_unit, deskripsi) VALUES
(1, 2, 'sekjur', 'Sekretariat Jurusan (Sekjur)', 'Menangani perbaikan dan perawatan fasilitas sarana dan prasarana kampus.'),
(2, 3, 'prodi', 'Admin Program Studi (Admin Prodi)', 'Menangani urusan akademik, jadwal, KRS, dan administrasi perkuliahan.'),
(3, 4, 'kajur', 'Ketua Jurusan (Kajur)', 'Menangani pengaduan terkait perilaku dosen/staf dan dugaan pungutan liar.'),
(4, 5, 'kps', 'Ketua Program Studi (KPS)', 'Menangani pengaduan ketertiban dan pelanggaran tata tertib sesama mahasiswa.')
ON CONFLICT (id_unit) DO NOTHING;

-- 4. Seed Kategori Pengaduan
INSERT INTO kategori (id_kategori, id_unit_default, kode_kategori, nama_kategori, deskripsi) VALUES
(1, 1, 'fasilitas', 'Fasilitas Kampus', 'Kerusakan ruang kelas, lab, proyektor, AC, lift, toilet umum.'),
(2, 2, 'akademik', 'Pelayanan Akademik', 'Kendala KRS, pengurusan nilai, surat izin, dan administrasi akademik.'),
(3, 3, 'dosen', 'Perilaku Dosen atau Staf', 'Ketidakhadiran kuliah, transparansi nilai, dan pelanggaran etika.'),
(4, 3, 'pungli', 'Pungutan Liar (Pungli)', 'Pungutan tidak resmi di luar biaya UKT resmi kampus.'),
(5, 4, 'mahasiswa', 'Pelanggaran Mahasiswa', 'Kecurangan ujian, vandalisme fasilitas, dan pelanggaran tata tertib.')
ON CONFLICT (id_kategori) DO NOTHING;

-- 5. Seed Status
INSERT INTO status_laporan (id_status, nama_status, keterangan) VALUES
(1, 'Menunggu Validasi', 'Laporan baru dibuat dan menunggu verifikasi kelengkapan bukti oleh unit.'),
(2, 'Dalam Penanganan', 'Laporan telah diverifikasi dan sedang dalam proses perbaikan/tindakan teknisi.'),
(3, 'Selesai', 'Masalah telah diselesaikan dan bukti penyelesaian telah diunggah.'),
(4, 'Tidak Valid', 'Laporan ditolak karena data tidak akurat atau tidak memenuhi kriteria pengaduan.')
ON CONFLICT (id_status) DO NOTHING;

-- 6. Seed Contoh Laporan Awal
INSERT INTO laporan (id_laporan, no_tiket, id_mahasiswa, id_kategori, id_unit, judul_laporan, lokasi, tanggal_kejadian, kronologi, status) VALUES
(1, 'KP-0012', 1, 1, 1, 'Kerusakan AC Gedung Sipil Lt. 4', 'Gedung Sipil Lt. 4 Ruang 4.02', '2026-05-10', 'AC tidak dingin dan mengeluarkan tetesan air cukup deras di atas meja mahasiswa.', 'Dalam Penanganan'),
(2, 'KP-0011', 1, 2, 2, 'Koreksi Nilai Praktikum Basis Data', 'Ruang Dosen JTI', '2026-05-08', 'Nilai tugas praktikum 3 belum terinput di portal akademik padahal sudah dinilai.', 'Selesai'),
(3, 'KP-0013', 1, 1, 1, 'Kerusakan Proyektor & Stop Kontak Lab 3', 'Gedung Sipil Lt. 7 Lab 3', '2026-10-06', 'Proyektor mati mendadak saat sesi praktikum dan stop kontak mengeluarkan percikan kecil.', 'Menunggu Validasi')
ON CONFLICT (id_laporan) DO NOTHING;

-- 7. Seed Riwayat Status Awal
INSERT INTO riwayat_status (id_laporan, id_status, status_text, keterangan) VALUES
(1, 1, 'Laporan Diajukan', 'Laporan berhasil dibuat oleh mahasiswa.'),
(1, 2, 'Dalam Penanganan', 'Staff Sekjur memvalidasi laporan dan menugaskan teknisi sarpras.'),
(2, 1, 'Laporan Diajukan', 'Laporan dibuat oleh mahasiswa.'),
(2, 2, 'Dalam Penanganan', 'Admin Prodi mengonfirmasi ke dosen terkait.'),
(2, 3, 'Selesai', 'Nilai telah diperbarui di sistem akademik.'),
(3, 1, 'Menunggu Validasi', 'Laporan diajukan dan diteruskan ke Sekretariat Jurusan.')
ON CONFLICT DO NOTHING;

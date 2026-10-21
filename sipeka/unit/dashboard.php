<?php
/**
 * SIPEKA - Central Unit Dispatcher Hub
 * Mengarahkan petugas unit ke dashboard khusus masing-masing (Sekjur, Admin Prodi, Kajur, KPS).
 */
require_once __DIR__ . '/../includes/auth.php';

$user = get_authenticated_user();
if ($user && ($user['role'] ?? '') === 'unit') {
    $kodeUnit = $user['kode_unit'] ?? 'sekjur';
    $dest = get_unit_dashboard_url($kodeUnit, '.');
    header("Location: $dest");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Unit Penanggung Jawab - SIPEKA Polinema</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .hub-container {
      max-width: 1040px;
      margin: 2.5rem auto;
      padding: 0 1.5rem;
    }
    .unit-hub-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-top: 2rem;
    }
    .unit-hub-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 1.75rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
    }
    .unit-hub-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 24px -6px rgba(0,0,0,0.1);
      border-color: var(--accent-blue);
    }
    .unit-hub-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
    }
    .unit-hub-card.sekjur::before { background: #2563eb; }
    .unit-hub-card.prodi::before { background: #0284c7; }
    .unit-hub-card.kajur::before { background: #b45309; }
    .unit-hub-card.kps::before { background: #4338ca; }

    .unit-icon-badge {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }
    .unit-hub-card.sekjur .unit-icon-badge { background: #eff6ff; }
    .unit-hub-card.prodi .unit-icon-badge { background: #f0fdf4; }
    .unit-hub-card.kajur .unit-icon-badge { background: #fef3c7; }
    .unit-hub-card.kps .unit-icon-badge { background: #ede9fe; }

    @media (max-width: 768px) {
      .unit-hub-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body style="background-color: #f1f5f9; min-height: 100vh;">

  <div class="hub-container">
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="display: inline-flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
        <span style="font-size: 1.8rem;">🏢</span>
        <h1 style="color: var(--primary-navy); font-size: 1.75rem; margin: 0;">Portal Penanganan Unit Resmi SIPEKA</h1>
      </div>
      <p style="color: var(--text-muted); max-width: 650px; margin: 0 auto; font-size: 0.95rem;">
        Setiap unit kampus memiliki panel kerja terpisah tanpa opsi percampuran peran, sesuai wewenang dan disposisi otomatis dokumen proposal PBL TI 2F.
      </p>
    </div>

    <div class="unit-hub-grid">
      <!-- 1. Sekjur -->
      <div class="unit-hub-card sekjur">
        <div>
          <div class="unit-icon-badge">🏛️</div>
          <h3 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.2rem;">Sekretariat Jurusan (Sekjur)</h3>
          <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px;">
            Fasilitas Kampus &amp; Sarpras
          </span>
          <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-top: 0.75rem;">
            Wewenang penanganan: Kerusakan ruang kelas, lab komputer, proyektor, AC, stop kontak, toilet umum, dan perawatan sarana prasarana fisik Gedung Sipil &amp; JTI.
          </p>
        </div>
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
          <a href="sekjur/dashboard.php" class="btn-primary" style="text-decoration: none; text-align: center; flex: 1; padding: 0.6rem 1rem;">Buka Dashboard Sekjur &rarr;</a>
          <a href="sekjur/profil.php" class="btn-detail" style="text-decoration: none; text-align: center; padding: 0.6rem 0.85rem;">Profil</a>
        </div>
      </div>

      <!-- 2. Admin Prodi -->
      <div class="unit-hub-card prodi">
        <div>
          <div class="unit-icon-badge">🎓</div>
          <h3 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.2rem;">Admin Program Studi (Admin Prodi)</h3>
          <span class="badge" style="background: #f0fdf4; color: #15803d; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px;">
            Pelayanan Akademik
          </span>
          <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-top: 0.75rem;">
            Wewenang penanganan: Kendala KRS, sinkronisasi dan koreksi nilai praktikum/teori, jadwal perkuliahan, pengurusan surat izin, dan administrasi program studi.
          </p>
        </div>
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
          <a href="admin-prodi/dashboard.php" class="btn-primary" style="text-decoration: none; text-align: center; flex: 1; padding: 0.6rem 1rem; background-color: #0284c7;">Buka Dashboard Prodi &rarr;</a>
          <a href="admin-prodi/profil.php" class="btn-detail" style="text-decoration: none; text-align: center; padding: 0.6rem 0.85rem;">Profil</a>
        </div>
      </div>

      <!-- 3. Kajur -->
      <div class="unit-hub-card kajur">
        <div>
          <div class="unit-icon-badge">⚖️</div>
          <h3 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.2rem;">Ketua Jurusan (Kajur)</h3>
          <span class="badge" style="background: #fef3c7; color: #b45309; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px;">
            Etika Dosen &amp; Pungli
          </span>
          <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-top: 0.75rem;">
            Wewenang penanganan: Kode etik tenaga pengajar dan staf, transparansi kehadiran kuliah, serta pengawasan dan pencegahan segala bentuk pungutan liar.
          </p>
        </div>
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
          <a href="kajur/dashboard.php" class="btn-primary" style="text-decoration: none; text-align: center; flex: 1; padding: 0.6rem 1rem; background-color: #b45309;">Buka Dashboard Kajur &rarr;</a>
          <a href="kajur/profil.php" class="btn-detail" style="text-decoration: none; text-align: center; padding: 0.6rem 0.85rem;">Profil</a>
        </div>
      </div>

      <!-- 4. KPS -->
      <div class="unit-hub-card kps">
        <div>
          <div class="unit-icon-badge">📜</div>
          <h3 style="color: var(--primary-navy); margin: 0 0 0.5rem; font-size: 1.2rem;">Ketua Program Studi (KPS)</h3>
          <span class="badge" style="background: #ede9fe; color: #6d28d9; padding: 0.25rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px;">
            Tata Tertib Mahasiswa
          </span>
          <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-top: 0.75rem;">
            Wewenang penanganan: Pelanggaran tata tertib mahasiswa, kecurangan akademik, vandalisme fasilitas, dan pembinaan mediasi ketertiban kampus.
          </p>
        </div>
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
          <a href="kps/dashboard.php" class="btn-primary" style="text-decoration: none; text-align: center; flex: 1; padding: 0.6rem 1rem; background-color: #4338ca;">Buka Dashboard KPS &rarr;</a>
          <a href="kps/profil.php" class="btn-detail" style="text-decoration: none; text-align: center; padding: 0.6rem 0.85rem;">Profil</a>
        </div>
      </div>
    </div>

    <div style="text-align: center; margin-top: 2.5rem; display: flex; gap: 1rem; justify-content: center;">
      <a href="../auth/login.php" class="btn-detail" style="text-decoration: none; padding: 0.65rem 1.25rem;">&larr; Kembali ke Halaman Login</a>
      <a href="../mahasiswa/dashboard.php" class="btn-detail" style="text-decoration: none; padding: 0.65rem 1.25rem;">Portal Mahasiswa Pelapor</a>
    </div>
  </div>

</body>
</html>

<?php
/**
 * API: Create New Complaint (Mahasiswa)
 * Method: POST (multipart/form-data)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
}

// Opsional: Cek autentikasi atau fallback ke akun default demo
$user = get_authenticated_user();
$mhsId = $user && isset($user['id_mahasiswa']) ? $user['id_mahasiswa'] : 1; // Default: Galih

$kategoriKey = trim($_POST['kategoriKey'] ?? $_POST['kategori'] ?? 'fasilitas');
$judul       = trim($_POST['judul'] ?? '');
$lokasi      = trim($_POST['lokasi'] ?? '');
$tanggal     = trim($_POST['tanggal'] ?? date('Y-m-d'));
$kronologi   = trim($_POST['kronologi'] ?? '');

if (empty($judul) || empty($lokasi) || empty($kronologi)) {
    json_response(['success' => false, 'message' => 'Judul, lokasi, dan kronologi wajib diisi.'], 400);
}

$pdo = Database::getConnection();

// Cari id_kategori dan id_unit default (Direct Routing)
$stmtKat = $pdo->prepare("SELECT id_kategori, id_unit_default, nama_kategori FROM kategori WHERE kode_kategori = :kode LIMIT 1");
$stmtKat->execute([':kode' => $kategoriKey]);
$kat = $stmtKat->fetch();

if (!$kat) {
    // Fallback ke Fasilitas jika kode tidak ditemukan
    $stmtKat = $pdo->prepare("SELECT id_kategori, id_unit_default, nama_kategori FROM kategori LIMIT 1");
    $stmtKat->execute();
    $kat = $stmtKat->fetch();
}

$idKategori = (int) $kat['id_kategori'];
$idUnit     = (int) $kat['id_unit_default'];

// Generate Nomor Tiket Urut KP-XXXX
$stmtCount = $pdo->query("SELECT COUNT(*) as total FROM laporan");
$totalLaporan = (int) $stmtCount->fetch()['total'];
$nextNumber = $totalLaporan + 1;
$noTiket = sprintf("KP-%04d", $nextNumber);

// Pastikan no_tiket benar-benar unik
$stmtCheck = $pdo->prepare("SELECT id_laporan FROM laporan WHERE no_tiket = :tiket");
$stmtCheck->execute([':tiket' => $noTiket]);
if ($stmtCheck->fetch()) {
    $noTiket = sprintf("KP-%04d-%d", $nextNumber, time() % 1000);
}

try {
    $pdo->beginTransaction();

    // 1. Simpan Data Laporan Inti
    $sqlInsert = "
        INSERT INTO laporan (no_tiket, id_mahasiswa, id_kategori, id_unit, judul_laporan, lokasi, tanggal_kejadian, kronologi, status)
        VALUES (:tiket, :mhs_id, :kat_id, :unit_id, :judul, :lokasi, :tgl, :kronologi, 'Menunggu Validasi')
        RETURNING id_laporan
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':tiket'     => $noTiket,
        ':mhs_id'    => $mhsId,
        ':kat_id'    => $idKategori,
        ':unit_id'   => $idUnit,
        ':judul'     => $judul,
        ':lokasi'    => $lokasi,
        ':tgl'       => $tanggal,
        ':kronologi' => $kronologi
    ]);

    $insertedRow = $stmtInsert->fetch();
    $idLaporan = (int) ($insertedRow['id_laporan'] ?? $pdo->lastInsertId());

    // 2. Simpan Riwayat Status Awal
    $sqlRiwayat = "
        INSERT INTO riwayat_status (id_laporan, id_status, status_text, keterangan)
        VALUES (:id_lap, 1, 'Laporan Diajukan', :ket)
    ";
    $stmtRiwayat = $pdo->prepare($sqlRiwayat);
    $stmtRiwayat->execute([
        ':id_lap' => $idLaporan,
        ':ket'    => 'Laporan berhasil dibuat oleh mahasiswa dan otomatis didisposisikan ke unit penanggung jawab.'
    ]);

    // 3. Proses Unggah Berkas Bukti (Jika Ada)
    $uploadResult = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handle_secure_upload($_FILES['bukti'], 'bukti');

        if ($uploadResult['success']) {
            $sqlBukti = "
                INSERT INTO bukti_laporan (id_laporan, nama_file, path_file, jenis_file)
                VALUES (:id_lap, :nama, :path, :jenis)
            ";
            $stmtBukti = $pdo->prepare($sqlBukti);
            $stmtBukti->execute([
                ':id_lap' => $idLaporan,
                ':nama'   => $uploadResult['nama_asli'],
                ':path'   => $uploadResult['path_relatif'],
                ':jenis'  => $uploadResult['jenis_file']
            ]);
        }
    }

    $pdo->commit();

    json_response([
        'success'  => true,
        'message'  => "Pengaduan {$noTiket} berhasil diajukan!",
        'data'     => [
            'id_laporan' => $idLaporan,
            'no_tiket'   => $noTiket,
            'kategori'   => $kat['nama_kategori'],
            'status'     => 'Menunggu Validasi',
            'bukti'      => $uploadResult
        ]
    ], 201);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'success' => false,
        'message' => 'Gagal menyimpan pengaduan: ' . $e->getMessage()
    ], 500);
}

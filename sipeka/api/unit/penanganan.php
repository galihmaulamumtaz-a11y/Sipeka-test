<?php
/**
 * API: Unit Resolution Action (Record Solution & Mark Selesai)
 * Method: POST (multipart/form-data)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
}

$noTiket   = trim($_POST['noTiket'] ?? $_POST['no_tiket'] ?? '');
$tindakan  = trim($_POST['tindakan'] ?? '');

if (empty($noTiket) || empty($tindakan)) {
    json_response(['success' => false, 'message' => 'Nomor tiket dan keterangan tindakan penanganan wajib diisi.'], 400);
}

$pdo = Database::getConnection();

// Cari id_laporan & id_unit
$stmt = $pdo->prepare("SELECT id_laporan, id_unit FROM laporan WHERE no_tiket = :tiket");
$stmt->execute([':tiket' => $noTiket]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['success' => false, 'message' => 'Laporan tidak ditemukan.'], 404);
}

$idLaporan = (int) $report['id_laporan'];
$idUnit    = (int) $report['id_unit'];

try {
    $pdo->beginTransaction();

    // 1. Simpan catatan penanganan
    $sqlPenanganan = "
        INSERT INTO penanganan (id_laporan, id_unit, keterangan_penanganan)
        VALUES (:id_lap, :id_unit, :tindakan)
        RETURNING id_penanganan
    ";
    $stmtPen = $pdo->prepare($sqlPenanganan);
    $stmtPen->execute([
        ':id_lap'   => $idLaporan,
        ':id_unit'  => $idUnit,
        ':tindakan' => $tindakan
    ]);

    $insertedPen = $stmtPen->fetch();
    $idPenanganan = (int) ($insertedPen['id_penanganan'] ?? $pdo->lastInsertId());

    // 2. Upload Bukti Penanganan (Jika Ada)
    if (isset($_FILES['bukti_selesai']) && $_FILES['bukti_selesai']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = handle_secure_upload($_FILES['bukti_selesai'], 'penanganan');
        if ($uploadRes['success']) {
            $stmtBukti = $pdo->prepare("INSERT INTO bukti_penanganan (id_penanganan, file) VALUES (:id_pen, :path)");
            $stmtBukti->execute([':id_pen' => $idPenanganan, ':path' => $uploadRes['path_relatif']]);
        }
    }

    // 3. Update status laporan menjadi 'Selesai'
    $stmtUpdate = $pdo->prepare("UPDATE laporan SET status = 'Selesai', updated_at = CURRENT_TIMESTAMP WHERE id_laporan = :id");
    $stmtUpdate->execute([':id' => $idLaporan]);

    // 4. Catat ke riwayat_status linimasa
    $stmtRiwayat = $pdo->prepare("INSERT INTO riwayat_status (id_laporan, id_status, status_text, keterangan) VALUES (:id, 3, 'Selesai', :ket)");
    $stmtRiwayat->execute([
        ':id'  => $idLaporan,
        ':ket' => "Penanganan tuntas: {$tindakan}. Laporan dinyatakan selesai dan ditutup."
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => "Pengaduan {$noTiket} berhasil diselesaikan!",
        'data'    => [
            'no_tiket' => $noTiket,
            'status'   => 'Selesai'
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Gagal menyelesaikan penanganan: ' . $e->getMessage()], 500);
}

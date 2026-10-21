<?php
/**
 * API: Get Single Report Detail & Complete Status Timeline
 * Method: GET
 * Params: ?tiket=KP-XXXX or ?id=1
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

$noTiket = trim($_GET['tiket'] ?? '');
$idLap   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (empty($noTiket) && $idLap === 0) {
    json_response(['success' => false, 'message' => 'Parameter tiket atau id wajib disertakan.'], 400);
}

$pdo = Database::getConnection();

// Query Laporan Utama
$sql = "
    SELECT 
        l.*,
        k.nama_kategori,
        k.kode_kategori,
        u.nama_unit,
        u.kode_unit,
        m.nama_mahasiswa,
        m.nim,
        m.program_studi,
        m.jurusan,
        m.nomor_telepon
    FROM laporan l
    JOIN kategori k ON l.id_kategori = k.id_kategori
    JOIN unit u ON l.id_unit = u.id_unit
    JOIN mahasiswa m ON l.id_mahasiswa = m.id_mahasiswa
    WHERE l.no_tiket = :tiket OR l.id_laporan = :id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':tiket' => $noTiket, ':id' => $idLap]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['success' => false, 'message' => 'Laporan pengaduan tidak ditemukan.'], 404);
}

$reportId = (int) $report['id_laporan'];

// Query Bukti Lampiran
$stmtBukti = $pdo->prepare("SELECT id_bukti, nama_file, path_file, jenis_file, uploaded_at FROM bukti_laporan WHERE id_laporan = :id ORDER BY id_bukti ASC");
$stmtBukti->execute([':id' => $reportId]);
$attachments = $stmtBukti->fetchAll();

// Query Riwayat Status (Linimasa)
$stmtRiwayat = $pdo->prepare("SELECT id_riwayat, status_text, keterangan, tanggal_perubahan FROM riwayat_status WHERE id_laporan = :id ORDER BY id_riwayat ASC");
$stmtRiwayat->execute([':id' => $reportId]);
$timeline = $stmtRiwayat->fetchAll();

// Query Tanggapan Unit
$stmtTanggapan = $pdo->prepare("
    SELECT t.id_tanggapan, t.isi_tanggapan, t.tanggal_tanggapan, u.nama_unit
    FROM tanggapan t
    JOIN unit u ON t.id_unit = u.id_unit
    WHERE t.id_laporan = :id
    ORDER BY t.id_tanggapan DESC
");
$stmtTanggapan->execute([':id' => $reportId]);
$responses = $stmtTanggapan->fetchAll();

// Query Penanganan & Bukti Selesai
$stmtPenanganan = $pdo->prepare("
    SELECT p.*, u.nama_unit
    FROM penanganan p
    JOIN unit u ON p.id_unit = u.id_unit
    WHERE p.id_laporan = :id
    ORDER BY p.id_penanganan DESC
    LIMIT 1
");
$stmtPenanganan->execute([':id' => $reportId]);
$resolution = $stmtPenanganan->fetch();

$report['lampiran']   = $attachments;
$report['riwayat']    = $timeline;
$report['tanggapan']  = $responses;
$report['penanganan'] = $resolution;

json_response([
    'success' => true,
    'data'    => $report
]);

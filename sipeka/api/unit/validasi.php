<?php
/**
 * API: Unit Verification Action (Approve / Reject)
 * Method: POST
 * Payload: { "noTiket": "KP-0013", "decision": "approve"|"reject", "keterangan": "..." }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$noTiket    = trim($input['noTiket'] ?? $input['no_tiket'] ?? '');
$decision   = trim($input['decision'] ?? 'approve'); // 'approve' | 'reject'
$keterangan = trim($input['keterangan'] ?? '');

if (empty($noTiket)) {
    json_response(['success' => false, 'message' => 'Nomor tiket wajib disertakan.'], 400);
}

$pdo = Database::getConnection();

// Cari id_laporan
$stmt = $pdo->prepare("SELECT id_laporan, id_unit, status FROM laporan WHERE no_tiket = :tiket");
$stmt->execute([':tiket' => $noTiket]);
$report = $stmt->fetch();

if (!$report) {
    json_response(['success' => false, 'message' => 'Laporan tidak ditemukan.'], 404);
}

$idLaporan = (int) $report['id_laporan'];
$newStatus = ($decision === 'approve') ? 'Dalam Penanganan' : 'Tidak Valid';
$idStatus  = ($decision === 'approve') ? 2 : 4;

$catatanDefault = ($decision === 'approve')
    ? 'Staff unit memvalidasi kebenaran laporan. Laporan telah disetujui dan teknisi ditugaskan untuk melakukan penanganan.'
    : 'Laporan ditolak oleh unit karena bukti tidak memadai atau tidak memenuhi kriteria pengaduan.';

$catatanFinal = !empty($keterangan) ? $keterangan : $catatanDefault;

try {
    $pdo->beginTransaction();

    // 1. Update status di tabel laporan
    $stmtUpdate = $pdo->prepare("UPDATE laporan SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id_laporan = :id");
    $stmtUpdate->execute([':status' => $newStatus, ':id' => $idLaporan]);

    // 2. Tambah catatan ke riwayat_status
    $stmtRiwayat = $pdo->prepare("INSERT INTO riwayat_status (id_laporan, id_status, status_text, keterangan) VALUES (:id, :stat_id, :status, :ket)");
    $stmtRiwayat->execute([
        ':id'      => $idLaporan,
        ':stat_id' => $idStatus,
        ':status'  => $newStatus,
        ':ket'     => $catatanFinal
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => "Tiket {$noTiket} berhasil divalidasi. Status sekarang: {$newStatus}.",
        'data'    => [
            'no_tiket' => $noTiket,
            'status'   => $newStatus
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Gagal memperbarui validasi: ' . $e->getMessage()], 500);
}

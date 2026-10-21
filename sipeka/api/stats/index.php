<?php
/**
 * API: Aggregate Statistics Metrics for Dashboards
 * Method: GET
 * Params: ?unit=...
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

$pdo = Database::getConnection();
$user = get_authenticated_user();

$unitParam = trim($_GET['unit'] ?? 'all');

$sql = "
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN l.status = 'Menunggu Validasi' THEN 1 END) as menunggu,
        COUNT(CASE WHEN l.status = 'Dalam Penanganan' THEN 1 END) as proses,
        COUNT(CASE WHEN l.status = 'Selesai' THEN 1 END) as selesai,
        COUNT(CASE WHEN l.status = 'Tidak Valid' THEN 1 END) as ditolak
    FROM laporan l
    JOIN unit u ON l.id_unit = u.id_unit
    WHERE 1=1
";

$params = [];

if ($user && $user['role'] === 'unit' && !empty($user['id_unit'])) {
    $sql .= " AND l.id_unit = :auth_unit";
    $params[':auth_unit'] = $user['id_unit'];
} elseif ($unitParam !== 'all') {
    $sql .= " AND u.kode_unit = :kode_unit";
    $params[':kode_unit'] = $unitParam;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats = $stmt->fetch();

json_response([
    'success' => true,
    'data'    => [
        'total'    => (int) ($stats['total'] ?? 0),
        'menunggu' => (int) ($stats['menunggu'] ?? 0),
        'proses'   => (int) ($stats['proses'] ?? 0),
        'selesai'  => (int) ($stats['selesai'] ?? 0),
        'ditolak'  => (int) ($stats['ditolak'] ?? 0)
    ]
]);

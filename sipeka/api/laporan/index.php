<?php
/**
 * API: List Reports with Filters & Search
 * Method: GET
 * Params: ?unit=...&status=...&q=...
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

$user = get_authenticated_user();
$pdo = Database::getConnection();

$statusFilter = trim($_GET['status'] ?? 'all');
$unitFilter   = trim($_GET['unit'] ?? 'all');
$searchQuery  = trim($_GET['q'] ?? '');

// Base Query dengan JOIN tabel relasional
$sql = "
    SELECT 
        l.id_laporan,
        l.no_tiket,
        l.judul_laporan,
        l.lokasi,
        l.tanggal_kejadian,
        l.tanggal_lapor,
        l.status,
        k.nama_kategori,
        k.kode_kategori,
        u.nama_unit,
        u.kode_unit,
        m.nama_mahasiswa,
        m.nim,
        m.program_studi
    FROM laporan l
    JOIN kategori k ON l.id_kategori = k.id_kategori
    JOIN unit u ON l.id_unit = u.id_unit
    JOIN mahasiswa m ON l.id_mahasiswa = m.id_mahasiswa
    WHERE 1=1
";

$params = [];

// Filter berdasarkan role user aktif jika login
if ($user) {
    if ($user['role'] === 'unit' && !empty($user['id_unit'])) {
        $sql .= " AND l.id_unit = :auth_unit";
        $params[':auth_unit'] = $user['id_unit'];
    } elseif ($user['role'] === 'mahasiswa' && !empty($user['id_mahasiswa']) && $unitFilter === 'my') {
        $sql .= " AND l.id_mahasiswa = :auth_mhs";
        $params[':auth_mhs'] = $user['id_mahasiswa'];
    }
}

// Filter unit opsional
if ($unitFilter !== 'all' && $unitFilter !== 'my') {
    $sql .= " AND u.kode_unit = :kode_unit";
    $params[':kode_unit'] = $unitFilter;
}

// Filter status
if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " AND l.status = :status";
    $params[':status'] = $statusFilter;
}

// Search Query (Pencarian Tiket, Judul, atau Nama Pelapor)
if (!empty($searchQuery)) {
    $sql .= " AND (l.no_tiket ILIKE :q OR l.judul_laporan ILIKE :q OR m.nama_mahasiswa ILIKE :q OR k.nama_kategori ILIKE :q)";
    $params[':q'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY l.id_laporan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

json_response([
    'success' => true,
    'total'   => count($reports),
    'data'    => $reports
]);

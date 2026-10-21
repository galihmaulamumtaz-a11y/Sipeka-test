<?php
/**
 * API: User Login & Session Initiation
 * Method: POST
 * Payload: { "emailOrNim": "...", "password": "...", "role": "..." }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.'], 405);
}

// Baca JSON input atau fallback ke $_POST
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$identifier = trim($input['emailOrNim'] ?? $input['username'] ?? '');
$password   = trim($input['password'] ?? '');
$targetRole = trim($input['role'] ?? 'mahasiswa');

if (empty($identifier) || empty($password)) {
    json_response(['success' => false, 'message' => 'Email/NIM dan Password wajib diisi.'], 400);
}

$pdo = Database::getConnection();

// Query user dengan PDO Prepared Statement (Bebas SQL Injection)
$sql = "SELECT id_user, nama, email, password, role FROM users WHERE email = :ident OR id_user IN (SELECT id_user FROM mahasiswa WHERE nim = :ident)";
$stmt = $pdo->prepare($sql);
$stmt->execute([':ident' => $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(['success' => false, 'message' => 'Kredensial login tidak cocok atau akun tidak ditemukan.'], 401);
}

$userData = [
    'id_user' => (int) $user['id_user'],
    'nama'    => $user['nama'],
    'email'   => $user['email'],
    'role'    => $user['role']
];

// Lengkapi data spesifik role
if ($user['role'] === 'mahasiswa') {
    $stmtMhs = $pdo->prepare("SELECT id_mahasiswa, nim, program_studi, jurusan FROM mahasiswa WHERE id_user = :uid");
    $stmtMhs->execute([':uid' => $user['id_user']]);
    $mhs = $stmtMhs->fetch();

    if ($mhs) {
        $userData['id_mahasiswa'] = (int) $mhs['id_mahasiswa'];
        $userData['nim']          = $mhs['nim'];
        $userData['program_studi'] = $mhs['program_studi'];
        $userData['jurusan']       = $mhs['jurusan'];
    }
} elseif ($user['role'] === 'unit') {
    $stmtUnit = $pdo->prepare("SELECT id_unit, kode_unit, nama_unit FROM unit WHERE id_user = :uid");
    $stmtUnit->execute([':uid' => $user['id_user']]);
    $unit = $stmtUnit->fetch();

    if ($unit) {
        $userData['id_unit']   = (int) $unit['id_unit'];
        $userData['kode_unit'] = $unit['kode_unit'];
        $userData['nama_unit'] = $unit['nama_unit'];
    }
}

// Simpan sesi aman
set_authenticated_user($userData);

// Generate CSRF Token
$csrfToken = generate_csrf_token();

$redirectUrl = ($user['role'] === 'unit')
    ? get_unit_dashboard_url($userData['kode_unit'] ?? 'sekjur', '../unit')
    : '../mahasiswa/dashboard.php';

json_response([
    'success'    => true,
    'message'    => 'Login berhasil.',
    'user'       => $userData,
    'redirect'   => $redirectUrl,
    'csrf_token' => $csrfToken
]);

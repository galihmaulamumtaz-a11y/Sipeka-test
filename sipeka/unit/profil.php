<?php
/**
 * SIPEKA - Profil Unit Router
 * Mengarahkan petugas unit ke halaman profil masing-masing.
 */
require_once __DIR__ . '/../includes/auth.php';

$user = get_authenticated_user();
if ($user && ($user['role'] ?? '') === 'unit') {
    $kodeUnit = $user['kode_unit'] ?? 'sekjur';
    $map = [
        'sekjur' => 'sekjur/profil.php',
        'prodi'  => 'admin-prodi/profil.php',
        'kajur'  => 'kajur/profil.php',
        'kps'    => 'kps/profil.php'
    ];
    $dest = $map[$kodeUnit] ?? 'sekjur/profil.php';
    header("Location: $dest");
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}

<?php
/**
 * SIPEKA - Verifikasi Unit Router
 */
require_once __DIR__ . '/../includes/auth.php';

$user = get_authenticated_user();
$kodeUnit = ($user && !empty($user['kode_unit'])) ? $user['kode_unit'] : 'sekjur';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$map = [
    'sekjur' => "sekjur/verifikasi.php$query",
    'prodi'  => "admin-prodi/verifikasi.php$query",
    'kajur'  => "kajur/verifikasi.php$query",
    'kps'    => "kps/verifikasi.php$query"
];
$dest = $map[$kodeUnit] ?? "sekjur/verifikasi.php$query";
header("Location: $dest");
exit;

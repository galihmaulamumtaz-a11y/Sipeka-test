<?php
/**
 * SIPEKA - Penanganan Unit Router
 */
require_once __DIR__ . '/../includes/auth.php';

$user = get_authenticated_user();
$kodeUnit = ($user && !empty($user['kode_unit'])) ? $user['kode_unit'] : 'sekjur';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$map = [
    'sekjur' => "sekjur/penanganan.php$query",
    'prodi'  => "admin-prodi/penanganan.php$query",
    'kajur'  => "kajur/penanganan.php$query",
    'kps'    => "kps/penanganan.php$query"
];
$dest = $map[$kodeUnit] ?? "sekjur/penanganan.php$query";
header("Location: $dest");
exit;

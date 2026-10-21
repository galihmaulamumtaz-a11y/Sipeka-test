<?php
/**
 * SIPEKA Central Routing Entry Point
 * Melakukan auto-routing berdasarkan status sesi pengguna aktif:
 * - Mahasiswa -> mahasiswa/dashboard.php
 * - Unit Staff -> unit/dashboard.php
 * - Belum Login -> auth/login.php
 */

require_once __DIR__ . '/includes/auth.php';

$user = get_authenticated_user();

if ($user) {
    if (($user['role'] ?? '') === 'unit') {
        $dest = get_unit_dashboard_url($user['kode_unit'] ?? 'sekjur', 'unit');
        header("Location: $dest");
        exit;
    } else {
        header('Location: mahasiswa/dashboard.php');
        exit;
    }
} else {
    header('Location: auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SIPEKA - Mengarahkan...</title>
  <script src="assets/js/store.js"></script>
  <script>
    // Client-side fallback routing untuk browser direct file access (file:///)
    document.addEventListener('DOMContentLoaded', () => {
      const localUser = window.sipekaStore ? window.sipekaStore.getUser() : null;
      if (localUser) {
        if (localUser.role === 'unit') {
          const map = {
            'sekjur': 'unit/sekjur/dashboard.php',
            'prodi':  'unit/admin-prodi/dashboard.php',
            'kajur':  'unit/kajur/dashboard.php',
            'kps':    'unit/kps/dashboard.php'
          };
          window.location.href = map[localUser.kode_unit || localUser.unit_id] || 'unit/sekjur/dashboard.php';
        } else {
          window.location.href = 'mahasiswa/dashboard.php';
        }
      } else {
        window.location.href = 'auth/login.php';
      }
    });
  </script>
</head>
<body>
  <p>Mengarahkan ke halaman SIPEKA... Jika tidak berpindah otomatis, <a href="auth/login.php">klik di sini</a>.</p>
</body>
</html>

<?php
/**
 * SIPEKA - File Koneksi Database
 * Lokasi: auth/koneksi.php
 * 
 * File ini menginisialisasi koneksi basis data PDO (PostgreSQL / MySQL)
 * untuk digunakan dalam modul autentikasi, registrasi, maupun modul sistem lainnya.
 * 
 * Cara Penggunaan di file PHP lain:
 *   require_once __DIR__ . '/koneksi.php';
 *   // Anda dapat menggunakan variabel $koneksi, $conn, atau $pdo
 *   $stmt = $koneksi->prepare("SELECT * FROM users WHERE email = :email");
 *   $stmt->execute([':email' => $email]);
 *   $user = $stmt->fetch();
 */

// 1. Muat konfigurasi dari .env jika tersedia
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    $envFile = dirname(__DIR__) . '/.env.example';
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}

// 2. Parameter Konfigurasi Database (Bisa disesuaikan langsung di sini atau lewat file .env)
$db_driver   = getenv('DB_CONNECTION') ?: 'pgsql';
$db_host     = getenv('DB_HOST') ?: '127.0.0.1';
$db_port     = getenv('DB_PORT') ?: ($db_driver === 'pgsql' ? '5432' : '3306');
$db_database = getenv('DB_DATABASE') ?: 'sipeka_db';
$db_username = getenv('DB_USERNAME') ?: 'postgres';
$db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'secret';

// Variabel referensi koneksi global
$koneksi = null;
$conn    = null;
$pdo     = null;
$koneksi_status = false;
$koneksi_error  = null;

// 3. Bangun DSN (Data Source Name)
if ($db_driver === 'pgsql') {
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_database}";
} else {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_database};charset=utf8mb4";
}

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Inisialisasi Koneksi PDO
try {
    $pdo = new PDO($dsn, $db_username, $db_password, $pdo_options);
    $koneksi = $pdo;
    $conn    = $pdo;
    $koneksi_status = true;
} catch (PDOException $e) {
    $koneksi_error = $e->getMessage();
    $koneksi_status = false;
}

/**
 * Fungsi pembantu untuk mengambil objek koneksi PDO
 */
function getKoneksi(): ?PDO {
    global $koneksi;
    return $koneksi;
}

// 5. Jika file ini diakses langsung melalui browser (misal: localhost/.../auth/koneksi.php)
// Tampilkan halaman diagnostik dan tes koneksi interaktif
$isDirectAccess = (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__);

if ($isDirectAccess) {
    // Jika diakses via AJAX/API/curl, berikan format JSON
    $isJsonRequested = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) 
                    || (isset($_GET['format']) && $_GET['format'] === 'json');

    if ($isJsonRequested) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'   => $koneksi_status ? 'success' : 'error',
            'message'  => $koneksi_status ? 'Koneksi ke database SIPEKA berhasil!' : 'Gagal terhubung ke database.',
            'driver'   => $db_driver,
            'host'     => $db_host,
            'port'     => $db_port,
            'database' => $db_database,
            'username' => $db_username,
            'error'    => $koneksi_error,
            'available_drivers' => PDO::getAvailableDrivers()
        ], JSON_PRETTY_PRINT);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tes Koneksi Database - SIPEKA Polinema</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .conn-container {
      max-width: 680px;
      margin: 3rem auto;
      padding: 2rem;
      background: #ffffff;
      border-radius: var(--radius-lg, 12px);
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 1.5rem;
    }
    .status-badge.success {
      background: #dcfce7;
      color: #15803d;
    }
    .status-badge.error {
      background: #fee2e2;
      color: #b91c1c;
    }
    .param-table {
      width: 100%;
      border-collapse: collapse;
      margin: 1.5rem 0;
      font-size: 0.9rem;
    }
    .param-table th, .param-table td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #e2e8f0;
      text-align: left;
    }
    .param-table th {
      background: #f8fafc;
      color: #475569;
      width: 35%;
    }
    .param-table td {
      font-family: monospace;
      color: #1e293b;
    }
    .tip-box {
      background: #f0fdf4;
      border-left: 4px solid #22c55e;
      padding: 1rem;
      border-radius: 4px;
      font-size: 0.85rem;
      color: #166534;
      margin-top: 1.5rem;
    }
    .error-box {
      background: #fef2f2;
      border-left: 4px solid #ef4444;
      padding: 1rem;
      border-radius: 4px;
      font-size: 0.85rem;
      color: #991b1b;
      margin-top: 1.5rem;
      word-break: break-all;
    }
    .action-links {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }
    .btn-action {
      display: inline-block;
      padding: 0.65rem 1.25rem;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.875rem;
    }
    .btn-primary {
      background: #1A4F8B;
      color: #ffffff;
    }
    .btn-secondary {
      background: #e2e8f0;
      color: #334155;
    }
  </style>
</head>
<body style="background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center;">

  <div class="conn-container">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
      <div>
        <h2 style="margin: 0; color: #0F2744; font-size: 1.4rem;">Diagnostik Koneksi Basis Data</h2>
        <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">SIPEKA — Jurusan Teknologi Informasi Politeknik Negeri Malang</p>
      </div>
      <div>
        <?php if ($koneksi_status): ?>
          <span class="status-badge success">● Database Terhubung</span>
        <?php else: ?>
          <span class="status-badge error">● Gagal Terhubung</span>
        <?php endif; ?>
      </div>
    </div>

    <p style="font-size: 0.9rem; color: #334155; line-height: 1.5;">
      File koneksi ini berada di <code>auth/koneksi.php</code> dan menyediakan variabel <code>$koneksi</code>, <code>$conn</code>, serta <code>$pdo</code> untuk seluruh query aplikasi.
    </p>

    <table class="param-table">
      <tr>
        <th>Driver Basis Data</th>
        <td><?= htmlspecialchars($db_driver) ?></td>
      </tr>
      <tr>
        <th>Host / Server</th>
        <td><?= htmlspecialchars($db_host) ?></td>
      </tr>
      <tr>
        <th>Port</th>
        <td><?= htmlspecialchars((string)$db_port) ?></td>
      </tr>
      <tr>
        <th>Nama Database</th>
        <td><?= htmlspecialchars($db_database) ?></td>
      </tr>
      <tr>
        <th>Username</th>
        <td><?= htmlspecialchars($db_username) ?></td>
      </tr>
      <tr>
        <th>Driver PDO PHP Aktif</th>
        <td><?= htmlspecialchars(implode(', ', PDO::getAvailableDrivers()) ?: 'Tidak ada driver aktif') ?></td>
      </tr>
    </table>

    <?php if ($koneksi_status): ?>
      <div class="tip-box">
        <strong>Status: Siap Digunakan!</strong><br>
        Aplikasi SIPEKA berhasil terhubung ke basis data <strong><?= htmlspecialchars($db_database) ?></strong>. Semua query CRUD, login, registrasi, dan disposisi otomatis dapat berjalan langsung.
      </div>
    <?php else: ?>
      <div class="error-box">
        <strong>Pesan Kesalahan:</strong><br>
        <?= htmlspecialchars($koneksi_error) ?>
      </div>

      <div style="margin-top: 1.5rem; background: #fffbeb; border: 1px solid #fef3c7; padding: 1rem; border-radius: 6px; font-size: 0.85rem; color: #92400e;">
        <strong>Panduan Troubleshooting:</strong>
        <ol style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
          <?php if (!in_array($db_driver, PDO::getAvailableDrivers())): ?>
            <li style="margin-bottom: 0.35rem;">
              <strong>Ekstensi PDO <?= strtoupper($db_driver) ?> belum aktif di PHP:</strong>
              Buka file <code>php.ini</code> Anda, cari baris <code>;extension=pdo_<?= $db_driver ?></code>, hapus tanda titik koma (<code>;</code>) lalu restart web server (Apache/Laragon).
            </li>
          <?php endif; ?>
          <li style="margin-bottom: 0.35rem;">
            <strong>Layanan Basis Data Belum Berjalan:</strong> Pastikan PostgreSQL (atau Docker / Laragon) sudah dalam kondisi running/aktif.
          </li>
          <li style="margin-bottom: 0.35rem;">
            <strong>Database Belum Dibuat:</strong> Buat database <code><?= htmlspecialchars($db_database) ?></code> lalu impor skema dari <code>database/init.sql</code>.
          </li>
          <li>
            <strong>Ubah Kredensial:</strong> Sesuaikan password atau username pada file <code>.env</code> di root proyek atau edit langsung di <code>auth/koneksi.php</code>.
          </li>
        </ol>
      </div>
    <?php endif; ?>

    <div class="action-links">
      <a href="login.php" class="btn-action btn-primary">Kembali ke Halaman Login</a>
      <a href="register.php" class="btn-action btn-secondary">Halaman Registrasi</a>
      <a href="koneksi.php" class="btn-action btn-secondary" style="margin-left: auto;">Muat Ulang Tes</a>
    </div>
  </div>

</body>
</html>
<?php
}

<?php
/**
 * SIPEKA Security & Utility Helpers
 * Mengimplementasikan proteksi XSS, CSRF, Secure File Upload, dan JSON Responder.
 */

// Helper: Output escaping untuk mematikan celah XSS
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: JSON Response standar API
function json_response(array $data, int $statusCode = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper: CSRF Token Management
function generate_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Helper: Secure File Upload Pipeline
function handle_secure_upload(array $file, string $subDir = 'bukti'): array {
    // 1. Cek error bawaan PHP upload
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Parameter berkas tidak valid.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'Ukuran berkas melebihi batas konfigurasi server.',
            UPLOAD_ERR_FORM_SIZE  => 'Ukuran berkas melebihi batas formulir.',
            UPLOAD_ERR_PARTIAL    => 'Berkas hanya terunggah sebagian.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada berkas yang diunggah.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server hilang.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas ke penyimpanan.',
            UPLOAD_ERR_EXTENSION  => 'Ekstensi PHP membatalkan unggahan.'
        ];
        return ['success' => false, 'message' => $uploadErrors[$file['error']] ?? 'Terjadi kesalahan unggah.'];
    }

    // 2. Batas ukuran maksimal: 5 MB
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran berkas terlalu besar. Maksimal 5 MB.'];
    }

    // 3. Validasi MIME Type asli menggunakan finfo
    $allowedMimes = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedMimes)) {
        return [
            'success' => false,
            'message' => 'Format berkas tidak diizinkan. Hanya menerima JPG, PNG, WEBP, atau PDF.'
        ];
    }

    $extension = $allowedMimes[$mimeType];

    // 4. Pastikan direktori tujuan tersedia dan terproteksi
    $targetDir = __DIR__ . '/../uploads/' . trim($subDir, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 5. Generate nama berkas acak yang aman (Mencegah Path Traversal & Penimpaan berkas)
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $targetDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Gagal memindahkan berkas ke folder penyimpanan.'];
    }

    return [
        'success'      => true,
        'nama_asli'    => basename($file['name']),
        'nama_simpan'  => $safeName,
        'path_relatif' => 'uploads/' . trim($subDir, '/') . '/' . $safeName,
        'jenis_file'   => $mimeType,
        'ukuran'       => $file['size']
    ];
}

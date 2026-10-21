<?php
/**
 * SIPEKA Authentication & Session Middleware
 * Mengamankan sesi, mencegah Session Fixation, dan membatasi akses peran.
 */

require_once __DIR__ . '/security.php';

function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
        }
        @session_start();
    }
}

function get_authenticated_user(): ?array {
    start_secure_session();
    return $_SESSION['user'] ?? null;
}

function require_auth(): array {
    $user = get_authenticated_user();
    if (!$user) {
        json_response([
            'success' => false,
            'message' => 'Akses ditolak. Silakan login terlebih dahulu.',
            'error_code' => 'UNAUTHORIZED'
        ], 401);
    }
    return $user;
}

function require_role(array $allowedRoles): array {
    $user = require_auth();
    if (!in_array($user['role'], $allowedRoles, true)) {
        json_response([
            'success' => false,
            'message' => 'Anda tidak memiliki wewenang untuk mengakses sumber daya ini.',
            'error_code' => 'FORBIDDEN'
        ], 403);
    }
    return $user;
}

/**
 * Helper Mengambil URL Dashboard Unit Sesuai Kode Unit
 */
function get_unit_dashboard_url(?string $kodeUnit, string $prefix = '../unit'): string {
    $unitMap = [
        'sekjur' => "$prefix/sekjur/dashboard.php",
        'prodi'  => "$prefix/admin-prodi/dashboard.php",
        'kajur'  => "$prefix/kajur/dashboard.php",
        'kps'    => "$prefix/kps/dashboard.php"
    ];
    return $unitMap[$kodeUnit ?? ''] ?? "$prefix/sekjur/dashboard.php";
}

/**
 * Helper Routing Halaman Web (Guard Antar-Halaman & Antar-Unit)
 * Memastikan mahasiswa tidak tersesat ke halaman unit, dan unit tidak saling menyusup.
 */
function guard_page_access(?string $allowedRole = null, ?string $allowedUnit = null): ?array {
    $user = get_authenticated_user();
    if ($user && $allowedRole !== null) {
        if ($user['role'] !== $allowedRole) {
            // Arahkan ke dashboard yang sesuai dengan peran
            if ($user['role'] === 'unit') {
                $targetUrl = get_unit_dashboard_url($user['kode_unit'] ?? null, '../unit');
                header("Location: $targetUrl");
                exit;
            } elseif ($user['role'] === 'mahasiswa') {
                header('Location: ../mahasiswa/dashboard.php');
                exit;
            }
        } elseif ($allowedRole === 'unit' && $allowedUnit !== null) {
            // Jika masuk ke unit tertentu, pastikan kode_unit cocok
            if (!empty($user['kode_unit']) && $user['kode_unit'] !== $allowedUnit) {
                $myUrl = get_unit_dashboard_url($user['kode_unit'], '../../unit');
                header("Location: $myUrl");
                exit;
            }
        }
    }
    return $user;
}

function set_authenticated_user(array $user): void {
    start_secure_session();
    // Regenerasi session ID untuk mematikan celah Session Fixation
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_regenerate_id(true);
    }
    $_SESSION['user'] = $user;
    $_SESSION['logged_in_at'] = time();
}

function logout_user(): void {
    start_secure_session();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    @session_destroy();
}

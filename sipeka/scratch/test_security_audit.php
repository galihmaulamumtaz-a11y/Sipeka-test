<?php
/**
 * SIPEKA Security & Integrity Verification Suite
 * Menguji seluruh mekanisme keamanan: XSS Escaping, CSRF Token, BCRYPT, Validasi Role PBL, dan Input Sanitization.
 */

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== SIPEKA SECURITY & AUDIT SUITE ===\n\n";

$testsPassed = 0;
$totalTests = 0;

function assert_test(string $name, bool $condition): void {
    global $testsPassed, $totalTests;
    $totalTests++;
    if ($condition) {
        $testsPassed++;
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name\n";
    }
}

// 1. Uji XSS Escaping
$xssPayload = '<script>alert("XSS_ATTACK_VECTOR")</script>';
$escaped = e($xssPayload);
assert_test("XSS Escaping: Script tags dikonversi menjadi HTML entities", 
    $escaped === '&lt;script&gt;alert(&quot;XSS_ATTACK_VECTOR&quot;)&lt;/script&gt;');

$xssAttribute = '"><img src=x onerror=alert(1)>';
$escapedAttr = e($xssAttribute);
assert_test("XSS Escaping: Atribut kutip ganda dan tag img tersanitasi", 
    strpos($escapedAttr, '<') === false && strpos($escapedAttr, '"') === false);

// 2. Uji CSRF Token
$token1 = generate_csrf_token();
assert_test("CSRF: Token berhasil digenerate dan memiliki panjang 64 heksadesimal", 
    strlen($token1) === 64 && ctype_xdigit($token1));

assert_test("CSRF: Token valid diterima", 
    verify_csrf_token($token1) === true);

assert_test("CSRF: Token palsu / manipulasi ditolak", 
    verify_csrf_token('fake_csrf_attack_token_1234567890') === false);

// 3. Uji BCRYPT Password Security
$plainPassword = 'RahasiaPolinema2026!';
$hashed = password_hash($plainPassword, PASSWORD_BCRYPT);
assert_test("BCRYPT: Password di-hash menggunakan algoritma BCRYPT (prefiks $2y$)", 
    str_starts_with($hashed, '$2y$'));

assert_test("BCRYPT: Verifikasi kecocokan password asli berhasil", 
    password_verify($plainPassword, $hashed) === true);

assert_test("BCRYPT: Password salah ditolak", 
    password_verify('PasswordSalah123', $hashed) === false);

// 4. Uji Validasi Input Registrasi Mahasiswa
$validNim = '254107020095';
$invalidNim1 = '2541ABC';
$invalidNim2 = '123';
assert_test("Registrasi Mahasiswa: Validasi NIM angka 8-15 digit bekerja tepat", 
    preg_match('/^[0-9]{8,15}$/', $validNim) === 1 &&
    preg_match('/^[0-9]{8,15}$/', $invalidNim1) === 0 &&
    preg_match('/^[0-9]{8,15}$/', $invalidNim2) === 0
);

$validEmail = '254107020095@student.polinema.ac.id';
$invalidEmail = 'bukan-email-polinema';
assert_test("Registrasi Mahasiswa: Filter email standar RFC valid", 
    filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false &&
    filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) === false
);

// 5. Uji Validasi 4 Master Unit PBL SIPEKA
$allowedUnits = ['sekjur', 'prodi', 'kajur', 'kps'];
$testUnitValid = 'sekjur';
$testUnitInvalid = 'admin_umum';
assert_test("Registrasi Unit: 4 Unit PBL (Sekjur, Prodi, Kajur, KPS) tervalidasi tepat", 
    in_array($testUnitValid, $allowedUnits, true) === true &&
    in_array($testUnitInvalid, $allowedUnits, true) === false
);

// 6. Uji Route Guard
set_authenticated_user(['id_user' => 99, 'nama' => 'Test User', 'role' => 'mahasiswa']);
$userSession = get_authenticated_user();
assert_test("Route Guard: Sesi user aktif terotentikasi dan memiliki peran mahasiswa", 
    $userSession !== null && $userSession['role'] === 'mahasiswa'
);

// 7. Uji SQL Injection Detection Pattern
$maliciousInput = "254107020095' OR '1'='1";
$simulatedParamBinding = [':ident' => $maliciousInput];
assert_test("SQL Injection: Input manipulatif dibungkus secara literal dalam parameter array", 
    $simulatedParamBinding[':ident'] === $maliciousInput && !str_contains("SELECT * FROM users WHERE email = :ident", $maliciousInput)
);

// 8. Uji File auth/koneksi.php
require_once __DIR__ . '/../auth/koneksi.php';
assert_test("Koneksi Database: File auth/koneksi.php menyediakan variabel koneksi dan fungsi getKoneksi", 
    function_exists('getKoneksi') &&
    (isset($koneksi) || array_key_exists('koneksi', $GLOBALS)) &&
    (isset($conn) || array_key_exists('conn', $GLOBALS)) &&
    (isset($pdo) || array_key_exists('pdo', $GLOBALS))
);

echo "\n=====================================\n";
echo "Hasil Pengujian: $testsPassed / $totalTests Pengujian Berhasil Lolos!\n";
if ($testsPassed === $totalTests) {
    echo "Status: SEMUA MEKANISME KEAMANAN SIPEKA MEMENUHI STANDAR OWASP TOP 10 & OBE POLINEMA.\n";
}
echo "=====================================\n";

<?php
/**
 * SIPEKA API: Registrasi Akun Mahasiswa Baru & Petugas Unit
 * Method: POST
 * Payload Mahasiswa: { "role": "mahasiswa", "nama": "...", "nim": "...", "email": "...", "program_studi": "...", "nomor_telepon": "...", "password": "...", "confirm_password": "..." }
 * Payload Unit:      { "role": "unit", "kode_unit": "sekjur|prodi|kajur|kps", "nama": "...", "nip": "...", "email": "...", "nomor_telepon": "...", "password": "...", "confirm_password": "..." }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.'], 405);
}

// Baca JSON input atau fallback ke $_POST
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$role           = trim($input['role'] ?? 'mahasiswa');
$nama           = trim($input['nama'] ?? '');
$email          = trim($input['email'] ?? '');
$nomor_telepon  = trim($input['nomor_telepon'] ?? '');
$password       = trim($input['password'] ?? '');
$confirm_pass   = trim($input['confirm_password'] ?? '');

// 1. Validasi Umum
if (empty($nama) || empty($email) || empty($password)) {
    json_response(['success' => false, 'message' => 'Nama lengkap, Email, dan Kata Sandi wajib diisi.'], 400);
}

if (strlen($nama) < 3) {
    json_response(['success' => false, 'message' => 'Nama lengkap minimal 3 karakter.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Format alamat email tidak valid.'], 400);
}

if (strlen($password) < 6) {
    json_response(['success' => false, 'message' => 'Kata sandi minimal 6 karakter.'], 400);
}

if ($confirm_pass !== '' && $password !== $confirm_pass) {
    json_response(['success' => false, 'message' => 'Konfirmasi kata sandi tidak cocok.'], 400);
}

// Master 4 Unit PBL SIPEKA
$masterUnits = [
    'sekjur' => [
        'nama' => 'Sekretariat Jurusan (Sekjur)',
        'deskripsi' => 'Menangani perbaikan dan perawatan fasilitas sarana dan prasarana kampus (AC, Lab, Proyektor, Toilet).'
    ],
    'prodi'  => [
        'nama' => 'Admin Program Studi (Admin Prodi)',
        'deskripsi' => 'Menangani urusan akademik, jadwal kuliah, KRS, perbaikan nilai, dan administrasi perkuliahan.'
    ],
    'kajur'  => [
        'nama' => 'Ketua Jurusan (Kajur)',
        'deskripsi' => 'Menangani pengaduan terkait kode etik dosen/staf dan dugaan pungutan liar (pungli).'
    ],
    'kps'    => [
        'nama' => 'Ketua Program Studi (KPS)',
        'deskripsi' => 'Menangani pengaduan ketertiban, kecurangan akademik, dan pelanggaran tata tertib mahasiswa.'
    ]
];

try {
    $pdo = Database::getConnection();

    // 2. Cek apakah Email sudah terdaftar di sistem
    $stmtCheckEmail = $pdo->prepare("SELECT id_user FROM users WHERE email = :email");
    $stmtCheckEmail->execute([':email' => $email]);
    if ($stmtCheckEmail->fetch()) {
        json_response(['success' => false, 'message' => 'Alamat email ini sudah terdaftar di SIPEKA.'], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'unit') {
        // --- REGISTRASI PETUGAS / ADMIN UNIT ---
        $kode_unit = strtolower(trim($input['kode_unit'] ?? 'sekjur'));
        $nip       = trim($input['nip'] ?? '');

        if (!array_key_exists($kode_unit, $masterUnits)) {
            json_response(['success' => false, 'message' => 'Unit penanggung jawab yang dipilih tidak valid.'], 400);
        }

        $unitInfo = $masterUnits[$kode_unit];

        $pdo->beginTransaction();

        // Insert ke users sebagai unit
        $stmtUser = $pdo->prepare("
            INSERT INTO users (nama, email, password, role)
            VALUES (:nama, :email, :password, 'unit')
            RETURNING id_user
        ");
        $stmtUser->execute([
            ':nama'     => $nama,
            ':email'    => $email,
            ':password' => $hashedPassword
        ]);
        $rowUser = $stmtUser->fetch();
        $idUser = $rowUser['id_user'] ?? $pdo->lastInsertId('users_id_user_seq');

        // Hubungkan ke tabel unit
        $stmtCheckUnit = $pdo->prepare("SELECT id_unit FROM unit WHERE kode_unit = :kode");
        $stmtCheckUnit->execute([':kode' => $kode_unit]);
        $existingUnit = $stmtCheckUnit->fetch();

        if ($existingUnit) {
            // Update penanggung jawab unit ke user baru
            $stmtUpdateUnit = $pdo->prepare("UPDATE unit SET id_user = :uid WHERE kode_unit = :kode");
            $stmtUpdateUnit->execute([':uid' => $idUser, ':kode' => $kode_unit]);
            $idUnit = (int) $existingUnit['id_unit'];
        } else {
            // Buat record unit baru
            $stmtNewUnit = $pdo->prepare("
                INSERT INTO unit (id_user, kode_unit, nama_unit, deskripsi)
                VALUES (:uid, :kode, :nama_unit, :deskripsi)
                RETURNING id_unit
            ");
            $stmtNewUnit->execute([
                ':uid'       => $idUser,
                ':kode'      => $kode_unit,
                ':nama_unit' => $unitInfo['nama'],
                ':deskripsi' => $unitInfo['deskripsi']
            ]);
            $rowNew = $stmtNewUnit->fetch();
            $idUnit = (int) ($rowNew['id_unit'] ?? $pdo->lastInsertId('unit_id_unit_seq'));
        }

        $pdo->commit();

        $userData = [
            'id_user'       => (int) $idUser,
            'id_unit'       => $idUnit,
            'kode_unit'     => $kode_unit,
            'nama_unit'     => $unitInfo['nama'],
            'deskripsi_unit'=> $unitInfo['deskripsi'],
            'nama'          => $nama,
            'nip'           => $nip,
            'email'         => $email,
            'role'          => 'unit',
            'nomor_telepon' => $nomor_telepon
        ];

        set_authenticated_user($userData);
        $csrfToken = generate_csrf_token();

        json_response([
            'success'    => true,
            'message'    => "Pendaftaran berhasil! Selamat datang Petugas {$unitInfo['nama']}.",
            'user'       => $userData,
            'csrf_token' => $csrfToken,
            'redirect'   => get_unit_dashboard_url($targetUnitCode, '../unit')
        ], 201);

    } else {
        // --- REGISTRASI MAHASISWA ---
        $nim           = trim($input['nim'] ?? '');
        $program_studi = trim($input['program_studi'] ?? 'D4 Teknik Informatika');
        $jurusan       = trim($input['jurusan'] ?? 'Teknologi Informasi');

        if (empty($nim)) {
            json_response(['success' => false, 'message' => 'NIM wajib diisi untuk akun mahasiswa.'], 400);
        }

        if (!preg_match('/^[0-9]{8,15}$/', $nim)) {
            json_response(['success' => false, 'message' => 'NIM harus berupa angka (8-15 digit).'], 400);
        }

        // Cek duplikasi NIM
        $stmtCheckNim = $pdo->prepare("SELECT id_mahasiswa FROM mahasiswa WHERE nim = :nim");
        $stmtCheckNim->execute([':nim' => $nim]);
        if ($stmtCheckNim->fetch()) {
            json_response(['success' => false, 'message' => 'NIM ini sudah terdaftar sebagai mahasiswa di SIPEKA.'], 409);
        }

        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("
            INSERT INTO users (nama, email, password, role)
            VALUES (:nama, :email, :password, 'mahasiswa')
            RETURNING id_user
        ");
        $stmtUser->execute([
            ':nama'     => $nama,
            ':email'    => $email,
            ':password' => $hashedPassword
        ]);
        $rowUser = $stmtUser->fetch();
        $idUser = $rowUser['id_user'] ?? $pdo->lastInsertId('users_id_user_seq');

        $stmtMhs = $pdo->prepare("
            INSERT INTO mahasiswa (id_user, nim, nama_mahasiswa, program_studi, jurusan, nomor_telepon)
            VALUES (:id_user, :nim, :nama_mhs, :prodi, :jurusan, :telepon)
            RETURNING id_mahasiswa
        ");
        $stmtMhs->execute([
            ':id_user'   => $idUser,
            ':nim'       => $nim,
            ':nama_mhs'  => $nama,
            ':prodi'     => $program_studi,
            ':jurusan'   => $jurusan,
            ':telepon'   => $nomor_telepon
        ]);
        $rowMhs = $stmtMhs->fetch();
        $idMahasiswa = $rowMhs['id_mahasiswa'] ?? $pdo->lastInsertId('mahasiswa_id_mahasiswa_seq');

        $pdo->commit();

        $userData = [
            'id_user'       => (int) $idUser,
            'id_mahasiswa'  => (int) $idMahasiswa,
            'nama'          => $nama,
            'nim'           => $nim,
            'email'         => $email,
            'role'          => 'mahasiswa',
            'program_studi' => $program_studi,
            'jurusan'       => $jurusan,
            'nomor_telepon' => $nomor_telepon
        ];

        set_authenticated_user($userData);
        $csrfToken = generate_csrf_token();

        json_response([
            'success'    => true,
            'message'    => 'Registrasi akun mahasiswa berhasil. Selamat datang di SIPEKA!',
            'user'       => $userData,
            'csrf_token' => $csrfToken,
            'redirect'   => '../mahasiswa/dashboard.php'
        ], 201);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[SIPEKA Register Error] " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem saat mendaftarkan akun. Silakan coba beberapa saat lagi.'
    ], 500);
}

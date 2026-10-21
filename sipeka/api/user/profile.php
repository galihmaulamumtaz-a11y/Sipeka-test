<?php
/**
 * SIPEKA API: User Profile Management
 * Method: GET (ambil profil), POST (update info atau ubah password)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

$user = require_auth();
$userId = (int) $user['id_user'];
$role = $user['role'];

$pdo = Database::getConnection();

// --- 1. GET: AMBIL PROFIL LENGKAP & STATISTIK ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ambil data users
    $stmtUser = $pdo->prepare("SELECT id_user, nama, email, role, created_at FROM users WHERE id_user = :uid");
    $stmtUser->execute([':uid' => $userId]);
    $userData = $stmtUser->fetch();

    if (!$userData) {
        json_response(['success' => false, 'message' => 'Data pengguna tidak ditemukan.'], 404);
    }

    $profile = [
        'id_user'    => (int) $userData['id_user'],
        'nama'       => $userData['nama'],
        'email'      => $userData['email'],
        'role'       => $userData['role'],
        'created_at' => $userData['created_at'],
        'stats'      => [
            'total'    => 0,
            'menunggu' => 0,
            'proses'   => 0,
            'selesai'  => 0,
            'ditolak'  => 0
        ]
    ];

    if ($role === 'mahasiswa') {
        $stmtMhs = $pdo->prepare("SELECT id_mahasiswa, nim, nama_mahasiswa, program_studi, jurusan, nomor_telepon FROM mahasiswa WHERE id_user = :uid");
        $stmtMhs->execute([':uid' => $userId]);
        $mhs = $stmtMhs->fetch();

        if ($mhs) {
            $profile['id_mahasiswa']  = (int) $mhs['id_mahasiswa'];
            $profile['nim']           = $mhs['nim'];
            $profile['program_studi'] = $mhs['program_studi'];
            $profile['jurusan']       = $mhs['jurusan'];
            $profile['nomor_telepon'] = $mhs['nomor_telepon'];

            // Statistik pengaduan khusus mahasiswa ini
            $stmtStats = $pdo->prepare("
                SELECT status, COUNT(*) as jml 
                FROM laporan 
                WHERE id_mahasiswa = :mid 
                GROUP BY status
            ");
            $stmtStats->execute([':mid' => $mhs['id_mahasiswa']]);
            $rows = $stmtStats->fetchAll();

            $total = 0;
            foreach ($rows as $r) {
                $jml = (int) $r['jml'];
                $total += $jml;
                if ($r['status'] === 'Menunggu Validasi') $profile['stats']['menunggu'] += $jml;
                elseif ($r['status'] === 'Dalam Penanganan') $profile['stats']['proses'] += $jml;
                elseif ($r['status'] === 'Selesai') $profile['stats']['selesai'] += $jml;
                elseif ($r['status'] === 'Tidak Valid') $profile['stats']['ditolak'] += $jml;
            }
            $profile['stats']['total'] = $total;
        }

    } elseif ($role === 'unit') {
        $stmtUnit = $pdo->prepare("SELECT id_unit, kode_unit, nama_unit, deskripsi FROM unit WHERE id_user = :uid");
        $stmtUnit->execute([':uid' => $userId]);
        $unit = $stmtUnit->fetch();

        if (!$unit) {
            // Coba ambil berdasarkan session id_unit jika ada
            if (!empty($user['id_unit'])) {
                $stmtUnitById = $pdo->prepare("SELECT id_unit, kode_unit, nama_unit, deskripsi FROM unit WHERE id_unit = :uid");
                $stmtUnitById->execute([':uid' => $user['id_unit']]);
                $unit = $stmtUnitById->fetch();
            }
        }

        if ($unit) {
            $profile['id_unit']        = (int) $unit['id_unit'];
            $profile['kode_unit']      = $unit['kode_unit'];
            $profile['nama_unit']      = $unit['nama_unit'];
            $profile['deskripsi_unit'] = $unit['deskripsi'];

            // Statistik laporan masuk ke unit ini
            $stmtStats = $pdo->prepare("
                SELECT status, COUNT(*) as jml 
                FROM laporan 
                WHERE id_unit = :uid 
                GROUP BY status
            ");
            $stmtStats->execute([':uid' => $unit['id_unit']]);
            $rows = $stmtStats->fetchAll();

            $total = 0;
            foreach ($rows as $r) {
                $jml = (int) $r['jml'];
                $total += $jml;
                if ($r['status'] === 'Menunggu Validasi') $profile['stats']['menunggu'] += $jml;
                elseif ($r['status'] === 'Dalam Penanganan') $profile['stats']['proses'] += $jml;
                elseif ($r['status'] === 'Selesai') $profile['stats']['selesai'] += $jml;
                elseif ($r['status'] === 'Tidak Valid') $profile['stats']['ditolak'] += $jml;
            }
            $profile['stats']['total'] = $total;
        }
    }

    json_response(['success' => true, 'profile' => $profile]);
}

// --- 2. POST: UPDATE INFORMASI ATAU GANTI PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = trim($input['action'] ?? 'update_info');

    if ($action === 'change_password') {
        $oldPass     = trim($input['old_password'] ?? '');
        $newPass     = trim($input['new_password'] ?? '');
        $confirmPass = trim($input['confirm_password'] ?? '');

        if (empty($oldPass) || empty($newPass)) {
            json_response(['success' => false, 'message' => 'Kata sandi lama dan baru wajib diisi.'], 400);
        }

        if (strlen($newPass) < 6) {
            json_response(['success' => false, 'message' => 'Kata sandi baru minimal 6 karakter.'], 400);
        }

        if ($newPass !== $confirmPass) {
            json_response(['success' => false, 'message' => 'Konfirmasi kata sandi baru tidak cocok.'], 400);
        }

        // Cek kecocokan password lama
        $stmtCurrent = $pdo->prepare("SELECT password FROM users WHERE id_user = :uid");
        $stmtCurrent->execute([':uid' => $userId]);
        $curr = $stmtCurrent->fetch();

        if (!$curr || !password_verify($oldPass, $curr['password'])) {
            json_response(['success' => false, 'message' => 'Kata sandi lama tidak tepat.'], 400);
        }

        $newHashed = password_hash($newPass, PASSWORD_BCRYPT);
        $stmtUpdatePass = $pdo->prepare("UPDATE users SET password = :p WHERE id_user = :uid");
        $stmtUpdatePass->execute([':p' => $newHashed, ':uid' => $userId]);

        json_response(['success' => true, 'message' => 'Kata sandi berhasil diperbarui.']);

    } else {
        // Update data profil
        $nama    = trim($input['nama'] ?? '');
        $telepon = trim($input['nomor_telepon'] ?? '');

        if (empty($nama) || strlen($nama) < 3) {
            json_response(['success' => false, 'message' => 'Nama lengkap minimal 3 karakter.'], 400);
        }

        $pdo->beginTransaction();

        $stmtUser = $pdo->prepare("UPDATE users SET nama = :nama WHERE id_user = :uid");
        $stmtUser->execute([':nama' => $nama, ':uid' => $userId]);

        if ($role === 'mahasiswa') {
            $stmtMhs = $pdo->prepare("UPDATE mahasiswa SET nama_mahasiswa = :nama, nomor_telepon = :telp WHERE id_user = :uid");
            $stmtMhs->execute([':nama' => $nama, ':telp' => $telepon, ':uid' => $userId]);
        }

        $pdo->commit();

        // Update session
        $updatedUser = get_authenticated_user();
        $updatedUser['nama'] = $nama;
        $updatedUser['nomor_telepon'] = $telepon;
        set_authenticated_user($updatedUser);

        json_response([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'user'    => $updatedUser
        ]);
    }
}

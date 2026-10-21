<?php
/**
 * SIPEKA Database Connection Manager
 * Mengelola koneksi PDO ke PostgreSQL dengan konfigurasi .env
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::loadEnv(__DIR__ . '/../.env');

            $driver   = getenv('DB_CONNECTION') ?: 'pgsql';
            $host     = getenv('DB_HOST') ?: '127.0.0.1';
            $port     = getenv('DB_PORT') ?: '5432';
            $database = getenv('DB_DATABASE') ?: 'sipeka_db';
            $username = getenv('DB_USERNAME') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: 'secret';

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Return JSON error response if accessed via API
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal terhubung ke basis data PostgreSQL: ' . $e->getMessage(),
                    'error_code' => 'DB_CONNECTION_ERROR'
                ]);
                exit;
            }
        }

        return self::$instance;
    }

    /**
     * Sederhana .env parser tanpa dependensi eksternal
     */
    private static function loadEnv(string $path): void {
        if (!file_exists($path)) {
            // Gunakan .env.example jika .env belum dibuat
            $path = __DIR__ . '/../.env.example';
            if (!file_exists($path)) return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Hilangkan quote jika ada
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1);
                } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1);
                }

                if (!getenv($key)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}

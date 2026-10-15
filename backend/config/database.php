<?php
// backend/config/database.php

function loadEnv(?string $path = null): void {
    $path = $path ?? dirname(__DIR__, 2) . '/.env';
    
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);

        // Abaikan baris kosong atau komentar
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);

        // Hapus tanda kutip jika nilai dibungkus " atau '
        if (preg_match('/^(["\'])(.*)\1$/', $val, $matches)) {
            $val = $matches[2];
        }

        // Simpan ke environment jika belum ada
        if (getenv($key) === false) {
            putenv("{$key}={$val}");
            $_ENV[$key]    = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Jalankan loader env sekali saat file dimuat
loadEnv();

/**
 * Mengembalikan instance PDO singleton.
 *
 * @throws PDOException jika koneksi database gagal
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Ambil konfigurasi dari $_ENV / getenv dengan fallback default
        $host    = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $port    = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $dbName  = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'db-kelola-gym';
        $user    = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass    = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        $charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Hindari membocorkan kredensial DSN ke output mentah
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Gagal terhubung ke database.", (int) $e->getCode());
        }
    }

    return $pdo;
}
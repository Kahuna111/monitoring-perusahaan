<?php
// ============================================
// KONFIGURASI DATABASE
// ============================================
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'monitoring_perusahaan');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Base URL — Railway otomatis set RAILWAY_PUBLIC_DOMAIN
$_railwayDomain = getenv('RAILWAY_PUBLIC_DOMAIN');
define('BASE_URL', $_railwayDomain
    ? 'https://' . $_railwayDomain
    : 'http://localhost:8000');

// Nama Aplikasi
define('APP_NAME',    'MonitorPro');
define('APP_COMPANY', 'PT. Nama Perusahaan Anda');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Jika database tidak ditemukan (SQLSTATE[HY000] [1049] Unknown database)
    if ($e->getCode() == 1049 || strpos($e->getMessage(), '1049') !== false || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            // Coba konek tanpa menentukan database terlebih dahulu
            $dsnWithoutDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsnWithoutDb, DB_USER, DB_PASS, $options);
            
            // Buat database baru
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Pilih database yang baru dibuat
            $pdo->exec("USE `" . DB_NAME . "`");
        } catch (PDOException $e2) {
            die(json_encode([
                'error'   => true,
                'message' => 'Gagal membuat database otomatis: ' . $e2->getMessage()
            ]));
        }
    } else {
        die(json_encode([
            'error'   => true,
            'message' => 'Koneksi database gagal: ' . $e->getMessage()
        ]));
    }
}

// Tambah kolom session_id secara otomatis ke tabel users (HANYA SEKALI, tidak setiap request)
$_migrationFlag = __DIR__ . '/.session_id_migrated';
if (!file_exists($_migrationFlag)) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) DEFAULT NULL");
        @file_put_contents($_migrationFlag, date('Y-m-d H:i:s'));
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN session_id VARCHAR(255) DEFAULT NULL");
            @file_put_contents($_migrationFlag, date('Y-m-d H:i:s'));
        } catch (Exception $e2) {
            // Kolom sudah ada — tulis flag agar tidak dicoba lagi
            @file_put_contents($_migrationFlag, 'already_exists');
        }
    }
}

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
    die(json_encode([
        'error'   => true,
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]));
}

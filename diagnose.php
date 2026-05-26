<?php
require_once 'config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $users = $pdo->query("SELECT id, nama, email, role FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $karyawan = $pdo->query("SELECT id, user_id, nik, nama, status FROM karyawan ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'database_host' => DB_HOST,
        'database_name' => DB_NAME,
        'users' => $users,
        'karyawan' => $karyawan
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

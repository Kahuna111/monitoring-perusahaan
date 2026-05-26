<?php
require_once 'config/database.php';
$id = 1; // Budi Santoso

$karyawan = $pdo->prepare("SELECT * FROM karyawan WHERE id = ?");
$karyawan->execute([$id]);
$karyawan = $karyawan->fetch();

echo "KARYAWAN DETAIL:\n";
print_r($karyawan);

$stmtUsers = $pdo->prepare("
    SELECT id, nama, email, role 
    FROM users 
    WHERE (id NOT IN (SELECT user_id FROM karyawan WHERE user_id IS NOT NULL AND id != ?) OR id = ?)
    ORDER BY nama ASC
");
$stmtUsers->execute([$id, $karyawan['user_id']]);
$userPegawaiList = $stmtUsers->fetchAll();

echo "\nQUERY RESULT:\n";
print_r($userPegawaiList);

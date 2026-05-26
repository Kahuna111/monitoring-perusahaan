<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/karyawan/index.php'); exit; }

$karyawan = $pdo->prepare("SELECT * FROM karyawan WHERE id = ?");
$karyawan->execute([$id]);
$karyawan = $karyawan->fetch();

if (!$karyawan) {
    setFlash('danger', 'Karyawan tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/karyawan/index.php');
    exit;
}

// Hapus transaksi gaji terkait, lalu karyawan
$pdo->prepare("DELETE FROM transaksi_gaji WHERE karyawan_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM karyawan WHERE id = ?")->execute([$id]);

setFlash('success', "Karyawan <strong>{$karyawan['nama']}</strong> berhasil dihapus.");
header('Location: ' . BASE_URL . '/admin/karyawan/index.php');
exit;

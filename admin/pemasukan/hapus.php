<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/pemasukan/index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM pemasukan WHERE id = ?");
$stmt->execute([$id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    setFlash('danger', 'Transaksi pemasukan tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/pemasukan/index.php');
    exit;
}

// Hapus file bukti fisik jika ada
if ($transaksi['bukti']) {
    $filePath = '../../uploads/' . $transaksi['bukti'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Hapus record dari database
$delete = $pdo->prepare("DELETE FROM pemasukan WHERE id = ?");
$delete->execute([$id]);

setFlash('success', 'Transaksi pemasukan berhasil dihapus.');
header('Location: ' . BASE_URL . '/admin/pemasukan/index.php');
exit;

<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/gaji/index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT tg.*, k.nik, k.nama, k.jabatan, k.departemen, k.alamat, k.no_telp
    FROM transaksi_gaji tg
    JOIN karyawan k ON k.id = tg.karyawan_id
    WHERE tg.id = ?
");
$stmt->execute([$id]);
$gaji = $stmt->fetch();

if (!$gaji) { setFlash('danger','Data gaji tidak ditemukan.'); header('Location: ' . BASE_URL . '/admin/gaji/index.php'); exit; }

$bulanLabel = date('F Y', strtotime($gaji['bulan'] . '-01'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji – <?= htmlspecialchars($gaji['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f8; display: flex; justify-content: center; padding: 40px 20px; }
        .slip { background: #fff; width: 680px; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; }

        .slip-header { background: linear-gradient(135deg, #0f1117 0%, #1a1d2e 100%); padding: 32px 36px; color: #fff; }
        .slip-header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .slip-brand { display: flex; align-items: center; gap: 12px; }
        .slip-logo { width: 42px; height: 42px; background: linear-gradient(135deg,#6366f1,#8b5cf6); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .slip-logo svg { width: 22px; height: 22px; color: #fff; }
        .slip-company-name { font-size: 18px; font-weight: 800; }
        .slip-company-sub  { font-size: 11px; color: rgba(255,255,255,0.5); margin-top: 2px; }
        .slip-title { text-align: right; }
        .slip-title h2 { font-size: 22px; font-weight: 800; color: #fff; }
        .slip-title p  { font-size: 13px; color: rgba(255,255,255,0.6); margin-top: 3px; }

        .slip-info { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .slip-info-item label { font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px; }
        .slip-info-item span  { display: block; font-size: 13.5px; font-weight: 600; color: #fff; margin-top: 3px; }

        .slip-body { padding: 32px 36px; }

        .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; margin-bottom: 14px; }

        .slip-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .slip-row:last-child { border-bottom: none; }
        .slip-row .label { font-size: 13.5px; color: #475569; }
        .slip-row .amount { font-size: 13.5px; font-weight: 600; color: #0f172a; }
        .slip-row .amount.green { color: #10b981; }
        .slip-row .amount.red   { color: #ef4444; }

        .slip-total { background: linear-gradient(135deg, #6366f1, #4f46e5); border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; margin: 24px 0; }
        .slip-total .label { font-size: 14px; font-weight: 700; color: rgba(255,255,255,0.85); }
        .slip-total .amount { font-size: 26px; font-weight: 800; color: #fff; }

        .slip-status { text-align: center; margin-bottom: 24px; }
        .slip-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .slip-badge.lunas { background: #d1fae5; color: #065f46; }
        .slip-badge.belum { background: #fef3c7; color: #92400e; }

        .slip-footer { background: #f8fafc; padding: 20px 36px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; }
        .slip-footer .note { font-size: 11.5px; color: #94a3b8; }
        .slip-footer .sign { text-align: center; }
        .slip-footer .sign .line { width: 120px; height: 1px; background: #0f172a; margin: 32px auto 6px; }
        .slip-footer .sign .name { font-size: 12px; font-weight: 700; color: #0f172a; }
        .slip-footer .sign .role { font-size: 11px; color: #64748b; }

        .print-btn-wrap { display: flex; justify-content: center; gap: 12px; margin-top: 24px; }
        .print-btn { padding: 10px 24px; border-radius: 9px; font-family: 'Inter', sans-serif; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; display: flex; align-items: center; gap: 8px; }
        .print-btn svg { width: 16px; height: 16px; }
        .btn-print { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
        .btn-back  { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; }
        .btn-back:hover  { background: #f8fafc; }
        .btn-print:hover { box-shadow: 0 6px 20px rgba(99,102,241,0.4); }

        @media print {
            body { background: #fff; padding: 0; }
            .slip { box-shadow: none; border-radius: 0; width: 100%; }
            .print-btn-wrap { display: none; }
        }
    </style>
</head>
<body>

<div>
    <div class="slip">
        <!-- Header -->
        <div class="slip-header">
            <div class="slip-header-top">
                <div class="slip-brand">
                    <div class="slip-logo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="slip-company-name"><?= APP_NAME ?></div>
                        <div class="slip-company-sub"><?= APP_COMPANY ?></div>
                    </div>
                </div>
                <div class="slip-title">
                    <h2>SLIP GAJI</h2>
                    <p><?= $bulanLabel ?></p>
                </div>
            </div>
            <div class="slip-info">
                <div class="slip-info-item">
                    <label>Nama Karyawan</label>
                    <span><?= htmlspecialchars($gaji['nama']) ?></span>
                </div>
                <div class="slip-info-item">
                    <label>NIK</label>
                    <span><?= htmlspecialchars($gaji['nik']) ?></span>
                </div>
                <div class="slip-info-item">
                    <label>Jabatan</label>
                    <span><?= htmlspecialchars($gaji['jabatan'] ?? '-') ?></span>
                </div>
                <div class="slip-info-item">
                    <label>Departemen</label>
                    <span><?= htmlspecialchars($gaji['departemen'] ?? '-') ?></span>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="slip-body">
            <div class="section-title">Rincian Gaji</div>

            <div class="slip-row">
                <span class="label">Gaji Pokok</span>
                <span class="amount"><?= formatRupiah($gaji['gaji_pokok']) ?></span>
            </div>
            <div class="slip-row">
                <span class="label">Tunjangan</span>
                <span class="amount green">+ <?= formatRupiah($gaji['tunjangan']) ?></span>
            </div>
            <div class="slip-row">
                <span class="label">Potongan</span>
                <span class="amount red">– <?= formatRupiah($gaji['potongan']) ?></span>
            </div>

            <div class="slip-total">
                <span class="label">Gaji Bersih Diterima</span>
                <span class="amount"><?= formatRupiah($gaji['gaji_bersih']) ?></span>
            </div>

            <div class="slip-status">
                <?php if ($gaji['status_bayar'] === 'sudah'): ?>
                <span class="slip-badge lunas">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Sudah Dibayar – <?= $gaji['tanggal_bayar'] ? formatTanggal($gaji['tanggal_bayar']) : '' ?>
                </span>
                <?php else: ?>
                <span class="slip-badge belum">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    Belum Dibayar
                </span>
                <?php endif; ?>
            </div>

            <?php if ($gaji['keterangan']): ?>
            <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;font-size:13px;color:#475569;">
                <strong>Catatan:</strong> <?= htmlspecialchars($gaji['keterangan']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="slip-footer">
            <div class="note">
                Dicetak: <?= date('d/m/Y H:i') ?><br>
                Dokumen ini sah tanpa tanda tangan basah.
            </div>
            <div class="sign">
                <div class="line"></div>
                <div class="name">HRD / Admin</div>
                <div class="role"><?= APP_COMPANY ?></div>
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="print-btn-wrap">
        <button class="print-btn btn-back" onclick="window.history.back()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Kembali
        </button>
        <button class="print-btn btn-print" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Slip Gaji
        </button>
    </div>
</div>

</body>
</html>

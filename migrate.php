<?php
// ==============================================================================
// migrate.php - Helper tool to automatically import database.sql to Railway/Local
// ==============================================================================
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Database Migrator</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #0f1117; color: #e2e8f0; padding: 40px; }
        .card { background-color: #1a1d24; border: 1px solid #2d3139; border-radius: 8px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h2 { margin-top: 0; color: #3b82f6; }
        .success { color: #10b981; font-weight: bold; background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.3); }
        .error { color: #ef4444; font-weight: bold; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.3); }
        .warning { color: #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 12px; border-radius: 6px; border: 1px solid rgba(245, 158, 11, 0.3); margin-top: 20px; }
        code { background-color: #2e3440; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="card">
        <h2>MonitorPro Database Migrator</h2>
        <?php
        $sqlFile = 'database.sql';

        if (!file_exists($sqlFile)) {
            echo "<div class='error'>Error: File <code>$sqlFile</code> tidak ditemukan di folder utama (root) aplikasi.</div>";
            exit;
        }

        try {
            $sql = file_get_contents($sqlFile);
            
            // Execute the entire SQL script
            $pdo->exec($sql);
            
            echo "<div class='success'>✓ Sukses! Semua tabel dan data awal dari <code>database.sql</code> telah berhasil diimpor ke database Anda.</div>";
            echo "<div class='warning'><strong>PENTING:</strong> Demi keamanan sistem, harap segera HAPUS file <code>migrate.php</code> dari project Anda agar tidak disalahgunakan oleh pihak lain.</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>Gagal mengimpor database: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>

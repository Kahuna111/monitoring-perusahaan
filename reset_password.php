<?php
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password Admin</title>
    <style>
        body { font-family: sans-serif; background-color: #0f172a; color: #e2e8f0; padding: 40px; display: flex; justify-content: center; align-items: center; min-height: 80vh; }
        .card { background-color: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 30px; max-width: 500px; width: 100%; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); }
        h2 { margin-top: 0; color: #38bdf8; }
        .success { color: #4ade80; font-weight: bold; background: rgba(74, 222, 128, 0.1); padding: 16px; border-radius: 8px; border: 1px solid rgba(74, 222, 128, 0.2); margin: 20px 0; }
        .error { color: #f87171; font-weight: bold; background: rgba(248, 113, 113, 0.1); padding: 16px; border-radius: 8px; border: 1px solid rgba(248, 113, 113, 0.2); margin: 20px 0; }
        .info { background: #1e293b; padding: 12px; border-radius: 6px; border-left: 4px solid #38bdf8; margin: 10px 0; font-family: monospace; }
        .btn { display: inline-block; background-color: #38bdf8; color: #0f172a; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 15px; }
        .btn:hover { background-color: #7dd3fc; }
    </style>
</head>
<body>
    <div class="card">
        <h2>MonitorPro Password Resetter</h2>
        <?php
        try {
            $email = 'admin@monitoring.com';
            $plainPassword = 'Admin123!';
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            // Cek apakah tabel users ada
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
            if (!$tableCheck) {
                // Jika tabel belum ada, jalankan database.sql terlebih dahulu
                echo "<div class='error'>Tabel <code>users</code> belum ada. Silakan jalankan <code>migrate.php</code> terlebih dahulu di browser Anda untuk mengimpor seluruh database.</div>";
                echo "<a href='migrate.php' class='btn'>Jalankan Migrasi Database</a>";
            } else {
                // Cari apakah user admin@monitoring.com ada
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Update password
                    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update->execute([$hashedPassword, $user['id']]);
                    echo "<div class='success'>✓ Berhasil! Password untuk akun Admin telah di-reset ke default.</div>";
                } else {
                    // Insert baru
                    $insert = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                    $insert->execute(['Super Admin', $email, $hashedPassword, 'admin']);
                    echo "<div class='success'>✓ Berhasil! Akun Super Admin baru telah didaftarkan karena belum ada di database.</div>";
                }

                echo "<p>Gunakan kredensial berikut untuk login:</p>";
                echo "<div class='info'>";
                echo "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
                echo "<strong>Password:</strong> " . htmlspecialchars($plainPassword);
                echo "</div>";
                echo "<a href='login.php' class='btn'>Pergi ke Halaman Login</a>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Terjadi kesalahan: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
</body>
</html>

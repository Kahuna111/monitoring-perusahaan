<?php
// ============================================
// FUNGSI AUTENTIKASI & SESSION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login dan memvalidasi single device session
 * Hasil di-cache per request agar tidak query DB berkali-kali
 */
function isLoggedIn(): bool {
    static $cachedResult = null;
    if ($cachedResult !== null) return $cachedResult;

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return $cachedResult = false;
    }

    // Fitur cek agar 1 akun hanya dapat diakses oleh 1 perangkat/browser
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT session_id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $dbSession = $stmt->fetchColumn();

            if ($dbSession && $dbSession !== session_id()) {
                // Sesi telah diambil alih/login dari perangkat lain
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                
                // Mulai sesi baru secara temporer untuk mengirimkan pesan warning flash
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                setFlash('danger', 'Akun Anda telah diakses dari perangkat atau browser lain. Sesi ini telah berakhir.');
                return $cachedResult = false;
            }
        } catch (Exception $e) {
            // Abaikan jika query gagal agar tidak memutus akses aplikasi secara mendadak
        }
    }

    return $cachedResult = true;
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Wajibkan login - redirect ke login jika belum
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Wajibkan role admin - redirect ke dashboard jika bukan admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . '/pegawai/dashboard.php');
        exit;
    }
}

/**
 * Redirect sesuai role setelah login
 */
function redirectByRole(): void {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/pegawai/dashboard.php');
    }
    exit;
}

/**
 * Generate CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF Token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitasi input
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke Rupiah
 */
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format tanggal ke bahasa Indonesia
 */
function formatTanggal(string $date): string {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
        '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
        '10' => 'Oktober', '11' => 'November',  '12' => 'Desember'
    ];
    if (empty($date) || $date === '0000-00-00') return '-';
    $parts = explode('-', $date);
    return ($parts[2] ?? '') . ' ' . ($bulan[$parts[1]] ?? '') . ' ' . ($parts[0] ?? '');
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get dan hapus flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Ambil inisial nama untuk avatar
 */
function getInitials(string $name): string {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(mb_substr($word, 0, 1));
    }
    return $initials;
}

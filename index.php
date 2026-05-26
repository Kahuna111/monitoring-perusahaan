<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirectByRole();
}

header('Location: ' . BASE_URL . '/login.php');
exit;

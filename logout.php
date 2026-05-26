<?php
require_once 'config/database.php';
require_once 'config/auth.php';

// Hapus semua session
$_SESSION = [];
session_destroy();

// Redirect ke login
header('Location: ' . BASE_URL . '/login.php');
exit;

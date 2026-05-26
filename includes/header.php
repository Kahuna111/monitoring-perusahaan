<?php
// includes/header.php
// Requires: $pageTitle, $activePage (opsional)
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/config/auth.php';
}
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> - Sistem Monitoring Perusahaan | Kelola karyawan, keuangan, dan laporan secara terpusat.">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Preconnect to CDN domains for faster resource loading -->
    <link rel="dns-prefetch" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="https://cdn.datatables.net">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.custom.css">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- DataTables custom overrides -->
    <style>
        div.dataTables_wrapper div.dataTables_filter input {
            padding: 8px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
            margin-left: 6px;
        }
        div.dataTables_wrapper div.dataTables_length select {
            padding: 6px 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
        }
        div.dataTables_wrapper div.dataTables_info { font-size: 13px; color: #64748b; }
        div.dataTables_wrapper div.dataTables_paginate { font-size: 13px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 7px !important;
            font-family: 'Inter', sans-serif !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
            border-color: transparent !important;
            color: white !important;
        }
        table.dataTable thead th { cursor: pointer; }
    </style>
</head>
<body>
<div id="mobileOverlay" class="mobile-overlay"></div>

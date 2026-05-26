-- ============================================
-- DATABASE: monitoring_perusahaan
-- ============================================
CREATE DATABASE IF NOT EXISTS monitoring_perusahaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE monitoring_perusahaan;

-- ============================================
-- TABEL: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','pegawai') DEFAULT 'pegawai',
    foto        VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABEL: karyawan
-- ============================================
CREATE TABLE IF NOT EXISTS karyawan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT DEFAULT NULL,
    nik             VARCHAR(20) UNIQUE NOT NULL,
    nama            VARCHAR(100) NOT NULL,
    jabatan         VARCHAR(100) DEFAULT NULL,
    departemen      VARCHAR(100) DEFAULT NULL,
    tanggal_masuk   DATE DEFAULT NULL,
    gaji_pokok      DECIMAL(15,2) DEFAULT 0.00,
    no_telp         VARCHAR(20) DEFAULT NULL,
    alamat          TEXT DEFAULT NULL,
    status          ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABEL: transaksi_gaji
-- ============================================
CREATE TABLE IF NOT EXISTS transaksi_gaji (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id     INT NOT NULL,
    bulan           VARCHAR(7) NOT NULL,
    gaji_pokok      DECIMAL(15,2) DEFAULT 0.00,
    tunjangan       DECIMAL(15,2) DEFAULT 0.00,
    potongan        DECIMAL(15,2) DEFAULT 0.00,
    gaji_bersih     DECIMAL(15,2) DEFAULT 0.00,
    status_bayar    ENUM('belum','sudah') DEFAULT 'belum',
    tanggal_bayar   DATE DEFAULT NULL,
    keterangan      TEXT DEFAULT NULL,
    dibuat_oleh     INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABEL: kategori_transaksi
-- ============================================
CREATE TABLE IF NOT EXISTS kategori_transaksi (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nama    VARCHAR(100) NOT NULL,
    tipe    ENUM('pemasukan','pengeluaran') NOT NULL
) ENGINE=InnoDB;

-- ============================================
-- TABEL: pemasukan
-- ============================================
CREATE TABLE IF NOT EXISTS pemasukan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE NOT NULL,
    kategori_id INT DEFAULT NULL,
    deskripsi   TEXT DEFAULT NULL,
    jumlah      DECIMAL(15,2) NOT NULL,
    bukti       VARCHAR(255) DEFAULT NULL,
    dibuat_oleh INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_transaksi(id) ON DELETE SET NULL,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- TABEL: pengeluaran
-- ============================================
CREATE TABLE IF NOT EXISTS pengeluaran (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE NOT NULL,
    kategori_id INT DEFAULT NULL,
    deskripsi   TEXT DEFAULT NULL,
    jumlah      DECIMAL(15,2) NOT NULL,
    bukti       VARCHAR(255) DEFAULT NULL,
    dibuat_oleh INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_transaksi(id) ON DELETE SET NULL,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- DATA AWAL
-- ============================================

-- Admin default: email=admin@monitoring.com | password=Admin123!
INSERT INTO users (nama, email, password, role) VALUES
('Super Admin', 'admin@monitoring.com', '$2y$10$IuARjnThWqGS.ChnZmaZRu8f0GbXT2U9ZQOxyUxvMV9.7zsDytaAa', 'admin');

-- Kategori default
INSERT INTO kategori_transaksi (nama, tipe) VALUES
('Penjualan Produk', 'pemasukan'),
('Jasa Layanan', 'pemasukan'),
('Investasi', 'pemasukan'),
('Lain-lain Masuk', 'pemasukan'),
('Gaji Karyawan', 'pengeluaran'),
('Operasional Kantor', 'pengeluaran'),
('Pembelian Aset', 'pengeluaran'),
('Utilitas (Listrik/Air)', 'pengeluaran'),
('Pemasaran', 'pengeluaran'),
('Lain-lain Keluar', 'pengeluaran');

-- Karyawan contoh
INSERT INTO karyawan (nik, nama, jabatan, departemen, tanggal_masuk, gaji_pokok, no_telp, status) VALUES
('EMP001', 'Budi Santoso', 'Manager', 'Operasional', '2022-01-15', 8500000, '08123456789', 'aktif'),
('EMP002', 'Siti Rahayu', 'Staff Akuntansi', 'Keuangan', '2022-03-01', 5500000, '08234567890', 'aktif'),
('EMP003', 'Ahmad Fauzi', 'Staff IT', 'Teknologi', '2023-06-01', 6000000, '08345678901', 'aktif');

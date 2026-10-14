-- Membuat Database
CREATE DATABASE IF NOT EXISTS `db-kelola-gym`;
USE `db-kelola-gym`;

-- 1. Tabel Users (Akun, Profil & Status Membership)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(30) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    phone VARCHAR(20),
    gender ENUM('pria', 'wanita'),
    address TEXT,
    emergency_contact VARCHAR(30),
    membership_status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'inactive',
    membership_expires_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Paket
CREATE TABLE IF NOT EXISTS paket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_paket VARCHAR(50) NOT NULL,
    durasi_hari INT NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    tersedia BOOLEAN DEFAULT TRUE
);

-- 3. Tabel Langganan (Riwayat Paket Member)
CREATE TABLE IF NOT EXISTS langganan (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_paket INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status ENUM('aktif', 'kedaluwarsa', 'menunggu') DEFAULT 'menunggu',
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_paket) REFERENCES paket(id) ON DELETE RESTRICT
);

-- 4. Tabel Transaksi (Tunai, QRIS, Transfer)
CREATE TABLE IF NOT EXISTS transaksi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_langganan BIGINT NOT NULL,
    jumlah_bayar DECIMAL(10,2) NOT NULL,
    metode_pembayaran ENUM('tunai', 'transfer', 'qris') NOT NULL,
    dibayar_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_langganan) REFERENCES langganan(id) ON DELETE CASCADE
);

-- 5. Tabel Presensi Check-In
CREATE TABLE IF NOT EXISTS presensi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    waktu_presensi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- Seeders Akun Awal (Admin & Members)
INSERT INTO users (member_code, name, email, password, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at) VALUES
('ADM001', 'Administrator', 'admin@gym.com', '$2y$12$4VYuGBsW5Rj9l5R9voytp.yYSqT5Vt0xW6tZscAaq3rkup3nVtCm6', 'admin', '081234567890', 'pria', 'Jl. Sudirman No. 1, Jakarta', '081234567899', 'active', NULL),
('GYM001', 'Gym Member', 'user@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '081234567801', 'pria', 'Jl. Gatot Subroto No. 10, Jakarta', '081234567800', 'active', DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)),
('GYM002', 'Siti Lestari', 'siti@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '081356789021', 'wanita', 'Jl. Melati No. 5, Bandung', '081356789000', 'active', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)),
('GYM003', 'Budi Pratama', 'budi@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '082111223344', 'pria', 'Jl. Mawar No. 12, Surabaya', '082111223300', 'expired', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY)),
('GYM004', 'Nadia Anjani', 'nadia@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '085277001100', 'wanita', 'Jl. Anggrek No. 8, Yogyakarta', '085277001199', 'inactive', NULL)
ON DUPLICATE KEY UPDATE 
    member_code = VALUES(member_code),
    name = VALUES(name),
    phone = VALUES(phone),
    gender = VALUES(gender),
    address = VALUES(address),
    emergency_contact = VALUES(emergency_contact),
    membership_status = VALUES(membership_status),
    membership_expires_at = VALUES(membership_expires_at);

-- Seeders Paket Langganan
INSERT INTO paket (id, nama_paket, durasi_hari, harga, tersedia) VALUES
(1, 'Harian (1 Hari)', 1, 25000.00, TRUE),
(2, 'Paket 1 Bulan', 30, 150000.00, TRUE),
(3, 'Paket 3 Bulan', 90, 400000.00, TRUE),
(4, 'Paket 6 Bulan', 180, 750000.00, TRUE),
(5, 'Paket 1 Tahun', 365, 1300000.00, TRUE)
ON DUPLICATE KEY UPDATE
    nama_paket = VALUES(nama_paket),
    durasi_hari = VALUES(durasi_hari),
    harga = VALUES(harga),
    tersedia = VALUES(tersedia);

-- Seeders Langganan & Transaksi Awal
INSERT INTO langganan (id, id_user, id_paket, tanggal_mulai, tanggal_selesai, status) VALUES
(1, 2, 3, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), 'aktif'),
(2, 3, 2, DATE_SUB(CURRENT_DATE, INTERVAL 27 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'aktif'),
(3, 4, 2, DATE_SUB(CURRENT_DATE, INTERVAL 35 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'kedaluwarsa')
ON DUPLICATE KEY UPDATE
    id_user = VALUES(id_user),
    id_paket = VALUES(id_paket),
    tanggal_mulai = VALUES(tanggal_mulai),
    tanggal_selesai = VALUES(tanggal_selesai),
    status = VALUES(status);

INSERT INTO transaksi (id, id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) VALUES
(1, 1, 400000.00, 'transfer', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 2, 150000.00, 'qris', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(3, 3, 150000.00, 'tunai', DATE_SUB(NOW(), INTERVAL 35 DAY))
ON DUPLICATE KEY UPDATE
    id_langganan = VALUES(id_langganan),
    jumlah_bayar = VALUES(jumlah_bayar),
    metode_pembayaran = VALUES(metode_pembayaran),
    dibayar_pada = VALUES(dibayar_pada);
-- Membuat Database
CREATE DATABASE IF NOT EXISTS `db-kelola-gym`;
USE `db-kelola-gym`;

-- 1. Tabel Users (Akun, Profil & Status Membership)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(30) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    phone VARCHAR(20),
    gender ENUM('pria', 'wanita'),
    address TEXT,
    emergency_contact VARCHAR(30),
    membership_status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'inactive',
    membership_expires_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Paket
CREATE TABLE IF NOT EXISTS paket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_paket VARCHAR(50) NOT NULL,
    durasi_hari INT NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    tersedia BOOLEAN DEFAULT TRUE
);

-- 3. Tabel Langganan (Riwayat Paket Member)
CREATE TABLE IF NOT EXISTS langganan (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_paket INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status ENUM('aktif', 'kedaluwarsa', 'menunggu') DEFAULT 'menunggu',
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_paket) REFERENCES paket(id) ON DELETE RESTRICT
);

-- 4. Tabel Transaksi (Tunai, QRIS, Transfer)
CREATE TABLE IF NOT EXISTS transaksi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_langganan BIGINT NOT NULL,
    jumlah_bayar DECIMAL(10,2) NOT NULL,
    metode_pembayaran ENUM('tunai', 'transfer', 'qris') NOT NULL,
    dibayar_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_langganan) REFERENCES langganan(id) ON DELETE CASCADE
);

-- 5. Tabel Presensi Check-In
CREATE TABLE IF NOT EXISTS presensi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    waktu_presensi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

-- Seeders Akun Awal (Admin & Members)
INSERT INTO users (member_code, name, email, password, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at) VALUES
('ADM001', 'Administrator', 'admin@gym.com', '$2y$12$4VYuGBsW5Rj9l5R9voytp.yYSqT5Vt0xW6tZscAaq3rkup3nVtCm6', 'admin', '081234567890', 'pria', 'Jl. Sudirman No. 1, Jakarta', '081234567899', 'active', NULL),
('GYM001', 'Gym Member', 'user@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '081234567801', 'pria', 'Jl. Gatot Subroto No. 10, Jakarta', '081234567800', 'active', DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)),
('GYM002', 'Siti Lestari', 'siti@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '081356789021', 'wanita', 'Jl. Melati No. 5, Bandung', '081356789000', 'active', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)),
('GYM003', 'Budi Pratama', 'budi@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '082111223344', 'pria', 'Jl. Mawar No. 12, Surabaya', '082111223300', 'expired', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY)),
('GYM004', 'Nadia Anjani', 'nadia@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user', '085277001100', 'wanita', 'Jl. Anggrek No. 8, Yogyakarta', '085277001199', 'inactive', NULL)
ON DUPLICATE KEY UPDATE 
    member_code = VALUES(member_code),
    name = VALUES(name),
    phone = VALUES(phone),
    gender = VALUES(gender),
    address = VALUES(address),
    emergency_contact = VALUES(emergency_contact),
    membership_status = VALUES(membership_status),
    membership_expires_at = VALUES(membership_expires_at);

-- Seeders Paket
INSERT INTO paket (id, nama_paket, durasi_hari, harga, tersedia) VALUES
(1, '1 Bulan', 30, 150000.00, 1),
(2, '3 Bulan', 90, 400000.00, 1),
(3, '6 Bulan', 180, 750000.00, 1),
(4, '12 Bulan', 365, 1300000.00, 1)
ON DUPLICATE KEY UPDATE 
    nama_paket = VALUES(nama_paket),
    durasi_hari = VALUES(durasi_hari),
    harga = VALUES(harga),
    tersedia = VALUES(tersedia);

-- Seeders Langganan
INSERT INTO langganan (id, id_user, id_paket, tanggal_mulai, tanggal_selesai, status) VALUES
(1, 2, 2, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), 'aktif'),
(2, 3, 1, DATE_SUB(CURRENT_DATE, INTERVAL 27 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY), 'aktif'),
(3, 4, 3, DATE_SUB(CURRENT_DATE, INTERVAL 185 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 'kedaluwarsa')
ON DUPLICATE KEY UPDATE 
    id_user = VALUES(id_user),
    id_paket = VALUES(id_paket),
    tanggal_mulai = VALUES(tanggal_mulai),
    tanggal_selesai = VALUES(tanggal_selesai),
    status = VALUES(status);

-- Seeders Transaksi
INSERT INTO transaksi (id, id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) VALUES
(1, 1, 400000.00, 'qris', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 2, 150000.00, 'transfer', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(3, 3, 750000.00, 'tunai', DATE_SUB(NOW(), INTERVAL 185 DAY))
ON DUPLICATE KEY UPDATE 
    id_langganan = VALUES(id_langganan),
    jumlah_bayar = VALUES(jumlah_bayar),
    metode_pembayaran = VALUES(metode_pembayaran);

-- Seeders Presensi Hari Ini
INSERT INTO presensi (id, id_user, waktu_presensi) VALUES
(1, 4, CONCAT(CURRENT_DATE, ' 08:21:00')),
(2, 3, CONCAT(CURRENT_DATE, ' 08:35:00')),
(3, 2, CONCAT(CURRENT_DATE, ' 08:42:00'))
ON DUPLICATE KEY UPDATE 
    id_user = VALUES(id_user),
    waktu_presensi = VALUES(waktu_presensi);

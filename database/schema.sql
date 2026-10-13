-- Membuat Database
CREATE DATABASE IF NOT EXISTS db_gym_member;
USE db_gym_member;

-- 1. Tabel Pengguna (Admin, Staf, Member)
CREATE TABLE pengguna (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kode_member VARCHAR(30) UNIQUE NOT NULL,
    kata_sandi VARCHAR(255) NOT NULL,
    peran ENUM('admin', 'staf', 'member') NOT NULL,
    apakah_aktif BOOLEAN DEFAULT TRUE,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kode_member (kode_member)
);

-- 2. Tabel Profil Member
CREATE TABLE profil_member (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    nomor_telepon VARCHAR(20),
    jenis_kelamin ENUM('pria', 'wanita'),
    kontak_darurat VARCHAR(20),
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- 3. Tabel Paket
CREATE TABLE paket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_paket VARCHAR(50) NOT NULL,
    durasi_hari INT NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    tersedia BOOLEAN DEFAULT TRUE
);

-- 4. Tabel Langganan
CREATE TABLE langganan (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT NOT NULL,
    id_paket INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    status ENUM('aktif', 'kedaluwarsa', 'menunggu') DEFAULT 'menunggu',
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (id_paket) REFERENCES paket(id) ON DELETE RESTRICT
);

-- 5. Tabel Transaksi (Tunai, QRIS, Transfer)
CREATE TABLE transaksi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_langganan BIGINT NOT NULL,
    jumlah_bayar DECIMAL(10,2) NOT NULL,
    metode_pembayaran ENUM('tunai', 'transfer', 'qris') NOT NULL,
    dibayar_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_langganan) REFERENCES langganan(id) ON DELETE CASCADE
);

-- 6. Tabel Kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    kapasitas INT NOT NULL DEFAULT 15
);

-- 7. Tabel Jadwal Kelas
CREATE TABLE jadwal_kelas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_kelas INT NOT NULL,
    id_instruktur BIGINT NOT NULL,
    hari VARCHAR(15) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_instruktur) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- 8. Tabel Pemesanan Kelas (Booking Slot oleh Member)
CREATE TABLE pemesanan_kelas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_jadwal BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    tanggal_booking DATE NOT NULL,
    status_booking ENUM('dipesan', 'hadir', 'dibatalkan') DEFAULT 'dipesan',
    FOREIGN KEY (id_jadwal) REFERENCES jadwal_kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE
);

-- 9. Tabel Presensi Check-In
CREATE TABLE presensi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_pengguna BIGINT NOT NULL,
    waktu_presensi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    jenis_presensi ENUM('gym_umum', 'sesi_kelas') DEFAULT 'gym_umum',
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE
);
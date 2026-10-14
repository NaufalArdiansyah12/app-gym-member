<?php
// backend/controllers/admin/PackageController.php
require_once __DIR__ . '/AdminController.php';

class PackageController {
    /**
     * Mengambil seluruh daftar paket langganan
     * Endpoint: GET /api/admin/packages
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();

        $stmt = $db->query("
            SELECT 
                p.*,
                (
                    SELECT COUNT(*) 
                    FROM langganan l 
                    WHERE l.id_paket = p.id AND l.status = 'aktif'
                ) as total_active_members
            FROM paket p
            ORDER BY p.durasi_hari ASC
        ");
        $packages = $stmt->fetchAll();

        foreach ($packages as &$pkg) {
            $pkg['id'] = (int)$pkg['id'];
            $pkg['durasi_hari'] = (int)$pkg['durasi_hari'];
            $pkg['harga'] = (float)$pkg['harga'];
            $pkg['harga_formatted'] = 'Rp ' . number_format($pkg['harga'], 0, ',', '.');
            $pkg['tersedia'] = (bool)$pkg['tersedia'];
            $pkg['total_active_members'] = (int)$pkg['total_active_members'];
        }

        AdminController::json([
            'status' => 'success',
            'data'   => $packages,
        ]);
    }

    /**
     * Detail 1 paket
     * Endpoint: GET /api/admin/packages/{id}
     */
    public static function show(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM paket WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $pkg = $stmt->fetch();

        if (!$pkg) {
            AdminController::error('Paket tidak ditemukan', 404);
        }

        $pkg['id'] = (int)$pkg['id'];
        $pkg['durasi_hari'] = (int)$pkg['durasi_hari'];
        $pkg['harga'] = (float)$pkg['harga'];
        $pkg['harga_formatted'] = 'Rp ' . number_format($pkg['harga'], 0, ',', '.');
        $pkg['tersedia'] = (bool)$pkg['tersedia'];

        AdminController::json([
            'status' => 'success',
            'data'   => $pkg,
        ]);
    }

    /**
     * Tambah paket baru
     * Endpoint: POST /api/admin/packages
     */
    public static function store(): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $namaPaket = trim($input['nama_paket'] ?? '');
        $durasiHari = (int)($input['durasi_hari'] ?? 0);
        $harga = (float)($input['harga'] ?? 0);
        $tersedia = isset($input['tersedia']) ? (int)(bool)$input['tersedia'] : 1;

        if ($namaPaket === '') {
            AdminController::error('Nama paket wajib diisi', 422);
        }
        if ($durasiHari <= 0) {
            AdminController::error('Durasi hari wajib lebih dari 0', 422);
        }
        if ($harga <= 0) {
            AdminController::error('Harga wajib lebih dari 0', 422);
        }

        $stmt = $db->prepare("
            INSERT INTO paket (nama_paket, durasi_hari, harga, tersedia) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$namaPaket, $durasiHari, $harga, $tersedia]);
        $newId = (int)$db->lastInsertId();

        AdminController::json([
            'status'  => 'success',
            'message' => 'Paket berhasil ditambahkan',
            'data'    => [
                'id'          => $newId,
                'nama_paket'  => $namaPaket,
                'durasi_hari' => $durasiHari,
                'harga'       => $harga,
                'tersedia'    => (bool)$tersedia,
            ],
        ], 201);
    }

    /**
     * Ubah data paket
     * Endpoint: PUT /api/admin/packages/{id}
     */
    public static function update(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $stmt = $db->prepare("SELECT * FROM paket WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $pkg = $stmt->fetch();

        if (!$pkg) {
            AdminController::error('Paket tidak ditemukan', 404);
        }

        $namaPaket = trim($input['nama_paket'] ?? $pkg['nama_paket']);
        $durasiHari = isset($input['durasi_hari']) ? (int)$input['durasi_hari'] : (int)$pkg['durasi_hari'];
        $harga = isset($input['harga']) ? (float)$input['harga'] : (float)$pkg['harga'];
        $tersedia = isset($input['tersedia']) ? (int)(bool)$input['tersedia'] : (int)$pkg['tersedia'];

        $upStmt = $db->prepare("
            UPDATE paket 
            SET nama_paket = ?, durasi_hari = ?, harga = ?, tersedia = ? 
            WHERE id = ?
        ");
        $upStmt->execute([$namaPaket, $durasiHari, $harga, $tersedia, $id]);

        AdminController::json([
            'status'  => 'success',
            'message' => 'Paket berhasil diperbarui',
        ]);
    }

    /**
     * Hapus paket (atau nonaktifkan jika ada relasi langganan)
     * Endpoint: DELETE /api/admin/packages/{id}
     */
    public static function destroy(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();

        $checkStmt = $db->prepare("SELECT COUNT(*) FROM langganan WHERE id_paket = ?");
        $checkStmt->execute([$id]);
        $count = (int)$checkStmt->fetchColumn();

        if ($count > 0) {
            // Karena ON DELETE RESTRICT di schema, nonaktifkan ketersediaan jika sudah pernah dipakai
            $db->prepare("UPDATE paket SET tersedia = 0 WHERE id = ?")->execute([$id]);
            AdminController::json([
                'status'  => 'success',
                'message' => 'Paket telah dinonaktifkan (tersedia = false) karena memiliki riwayat langganan.',
            ]);
        }

        $delStmt = $db->prepare("DELETE FROM paket WHERE id = ?");
        $delStmt->execute([$id]);

        AdminController::json([
            'status'  => 'success',
            'message' => 'Paket berhasil dihapus',
        ]);
    }
}


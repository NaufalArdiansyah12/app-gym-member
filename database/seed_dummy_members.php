<?php
// database/seed_dummy_members.php
require_once __DIR__ . '/../backend/config/database.php';

$db = getDB();

$members = [
    ['name' => 'Rizky Ramadhan',     'gender' => 'pria',   'month' => 4,  'day' => 12],
    ['name' => 'Dewi Anggraeni',      'gender' => 'wanita', 'month' => 4,  'day' => 25],
    ['name' => 'Fajar Nugraha',       'gender' => 'pria',   'month' => 5,  'day' => 8],
    ['name' => 'Maya Safitri',        'gender' => 'wanita', 'month' => 5,  'day' => 17],
    ['name' => 'Hendra Kurniawan',    'gender' => 'pria',   'month' => 5,  'day' => 29],
    ['name' => 'Putri Ayu Lestari',   'gender' => 'wanita', 'month' => 6,  'day' => 10],
    ['name' => 'Bayu Setiawan',       'gender' => 'pria',   'month' => 6,  'day' => 22],
    ['name' => 'Rina Wulandari',      'gender' => 'wanita', 'month' => 7,  'day' => 5],
    ['name' => 'Dimas Prasetyo',      'gender' => 'pria',   'month' => 7,  'day' => 14],
    ['name' => 'Tiara Maharani',      'gender' => 'wanita', 'month' => 7,  'day' => 20],
    ['name' => 'Aditya Pratama',      'gender' => 'pria',   'month' => 7,  'day' => 28],
    ['name' => 'Salsabila Khairunisa', 'gender' => 'wanita', 'month' => 8,  'day' => 3],
    ['name' => 'Irfan Maulana',       'gender' => 'pria',   'month' => 8,  'day' => 16],
    ['name' => 'Citra Kirana',        'gender' => 'wanita', 'month' => 8,  'day' => 27],
    ['name' => 'Bagas Saputra',       'gender' => 'pria',   'month' => 9,  'day' => 7],
    ['name' => 'Annisa Rahmawati',    'gender' => 'wanita', 'month' => 9,  'day' => 18],
    ['name' => 'Dedi Gunawan',        'gender' => 'pria',   'month' => 9,  'day' => 24],
    ['name' => 'Fitri Handayani',     'gender' => 'wanita', 'month' => 10, 'day' => 2],
    ['name' => 'Galih Permana',       'gender' => 'pria',   'month' => 10, 'day' => 8],
    ['name' => 'Nabila Syahrani',     'gender' => 'wanita', 'month' => 10, 'day' => 11],
];

$passHash = '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky'; // 'user123'
$statuses = ['active', 'active', 'active', 'inactive', 'expired'];

$stmt = $db->prepare("
    INSERT INTO users (
        member_code, name, email, password, role, phone, gender, 
        address, emergency_contact, membership_status, membership_expires_at, created_at
    ) VALUES (
        :code, :name, :email, :password, 'user', :phone, :gender,
        :address, :emergency, :status, :expires_at, :created_at
    )
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        phone = VALUES(phone),
        created_at = VALUES(created_at)
");

$db->beginTransaction();

try {
    foreach ($members as $i => $m) {
        $idx = $i + 10;
        $code = sprintf('GYM%03d', $idx);
        $email = 'dummy' . ($i + 1) . '@gym.com';
        $phone = '0812' . sprintf('%08d', rand(10000000, 99999999));
        $status = $statuses[$i % count($statuses)];
        $hour = rand(8, 20);
        $min = rand(10, 59);
        $sec = rand(10, 59);
        $createdAt = sprintf('2026-%02d-%02d %02d:%02d:%02d', $m['month'], $m['day'], $hour, $min, $sec);
        
        $expiresAt = null;
        if ($status === 'active') {
            $expiresAt = date('Y-m-d', strtotime($createdAt . ' + 30 days'));
        } elseif ($status === 'expired') {
            $expiresAt = date('Y-m-d', strtotime($createdAt . ' + 15 days'));
        }

        $stmt->execute([
            ':code'       => $code,
            ':name'       => $m['name'],
            ':email'      => $email,
            ':password'   => $passHash,
            ':phone'      => $phone,
            ':gender'     => $m['gender'],
            ':address'    => 'Jl. Dummy No. ' . ($i + 1) . ', Kota Gym',
            ':emergency'  => '0819' . sprintf('%08d', rand(10000000, 99999999)),
            ':status'     => $status,
            ':expires_at' => $expiresAt,
            ':created_at' => $createdAt,
        ]);
    }
    $db->commit();
    echo "20 dummy members inserted successfully.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Error inserting dummy members: " . $e->getMessage() . "\n";
    exit(1);
}

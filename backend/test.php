<?php
// backend/test.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/controllers/admin/AdminController.php';
require_once __DIR__ . '/controllers/admin/DashboardController.php';
require_once __DIR__ . '/controllers/admin/MemberController.php';
require_once __DIR__ . '/controllers/admin/PackageController.php';
require_once __DIR__ . '/controllers/admin/AttendanceController.php';
require_once __DIR__ . '/controllers/admin/TransactionController.php';
require_once __DIR__ . '/controllers/admin/CashierController.php';
require_once __DIR__ . '/controllers/admin/ReportsController.php';
require_once __DIR__ . '/controllers/user/UserController.php';
require_once __DIR__ . '/controllers/user/UserDashboardController.php';
require_once __DIR__ . '/controllers/user/UserAttendanceController.php';
require_once __DIR__ . '/controllers/user/UserMembershipController.php';

// 0. Environment & Database Configuration Tests
assert(function_exists('loadEnv'), 'loadEnv function must exist');
assert(getenv('DB_NAME') === 'db-kelola-gym', 'DB_NAME must be loaded from .env');

// 1. AuthController Tests
assert(class_exists('AuthController'), 'AuthController class not found');
assert(method_exists('AuthController', 'login'), 'AuthController::login method not found');

ob_start();
AuthController::login();
$output = ob_get_clean();
$data = json_decode($output, true);
assert(isset($data['error']), 'Empty login payload must return error message');

// Test admin login via email
ob_start();
AuthController::login(['email' => 'admin@gym.com', 'password' => 'admin123']);
$adminOut = ob_get_clean();
$adminData = json_decode($adminOut, true);
assert(($adminData['status'] ?? '') === 'ok' && ($adminData['data']['role'] ?? '') === 'admin', 'Admin login via email must succeed');

// Test member login via member_code
ob_start();
AuthController::login(['identifier' => 'GYM001', 'password' => 'user123']);
$memberOut = ob_get_clean();
$memberData = json_decode($memberOut, true);
assert(($memberData['status'] ?? '') === 'ok' && ($memberData['data']['role'] ?? '') === 'user' && $memberData['data']['member_code'] === 'GYM001', 'Member login via member_code must succeed');

// Test invalid login
ob_start();
AuthController::login(['identifier' => 'WRONG_CODE', 'password' => 'wrongpass']);
$invalidOut = ob_get_clean();
$invalidData = json_decode($invalidOut, true);
assert(isset($invalidData['error']), 'Invalid login credentials must return error message');

// Check CashierController
assert(class_exists('CashierController'), 'CashierController class not found');
assert(method_exists('CashierController', 'initData'), 'CashierController::initData not found');
assert(method_exists('CashierController', 'createTransaction'), 'CashierController::createTransaction not found');
assert(method_exists('CashierController', 'getTransactions'), 'CashierController::getTransactions not found');

// Check CashierController validation
ob_start();
CashierController::createTransaction();
$cashierOut = ob_get_clean();
$cashierData = json_decode($cashierOut, true);
assert(isset($cashierData['error']), 'Empty cashier payload must return error message');

// Check CashierController initData returns structure
ob_start();
CashierController::initData();
$initOut = ob_get_clean();
$initData = json_decode($initOut, true);
assert($initData['status'] === 'ok', 'Cashier initData must return ok status');
assert(isset($initData['data']['packages']), 'Cashier initData must return packages');
assert(isset($initData['data']['members']), 'Cashier initData must return members');
assert(isset($initData['data']['summary']), 'Cashier initData must return summary');

// Check CashierController searchMembers
ob_start();
$_GET['q'] = 'Siti';
CashierController::searchMembers();
$searchOut = ob_get_clean();
$searchData = json_decode($searchOut, true);
assert($searchData['status'] === 'ok', 'Cashier searchMembers must return ok status');
unset($_GET['q']);

// 2. Admin Classes and Methods Existence Tests
assert(class_exists('AdminController'), 'AdminController class not found');
assert(class_exists('DashboardController'), 'DashboardController class not found');
assert(class_exists('MemberController'), 'MemberController class not found');
assert(class_exists('PackageController'), 'PackageController class not found');
assert(class_exists('AttendanceController'), 'AttendanceController class not found');
assert(class_exists('TransactionController'), 'TransactionController class not found');
assert(class_exists('ReportsController'), 'ReportsController class not found');
assert(class_exists('LaporanController'), 'LaporanController class not found');
assert(method_exists('LaporanController', 'index'), 'LaporanController::index method not found');

// Test LaporanController::index response structure
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
ob_start();
LaporanController::index();
$repOut = ob_get_clean();
$repData = json_decode($repOut, true);
assert(($repData['status'] ?? '') === 'success', 'LaporanController::index must return success status');
assert(isset($repData['data']['summary']), 'LaporanController::index must return summary');
assert(isset($repData['data']['summary']['revenue']), 'Summary must contain revenue');
assert(isset($repData['data']['transactions']), 'LaporanController::index must return transactions list');
assert(isset($repData['data']['packages']), 'LaporanController::index must return packages');
assert(isset($repData['data']['attendances']), 'LaporanController::index must return attendances');
assert(isset($repData['data']['method_stats']), 'LaporanController::index must return method_stats');

// 3. Database Connection & Table Schema Tests
$db = getDB();
assert($db instanceof PDO, 'Database connection must return PDO instance');

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['users', 'paket', 'langganan', 'transaksi', 'presensi'];
foreach ($expectedTables as $expected) {
    assert(in_array($expected, $tables, true), "Table {$expected} must exist in database");
}

$cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$expectedCols = ['member_code', 'phone', 'gender', 'membership_status', 'membership_expires_at'];
foreach ($expectedCols as $col) {
    assert(in_array($col, $cols, true), "Column $col missing in users table");
}

// 4. DashboardController Tests
$stats = DashboardController::getStats($db);
assert(isset($stats['total_members']), 'Dashboard stats must contain total_members');
assert(isset($stats['active_members']), 'Dashboard stats must contain active_members');
assert(isset($stats['expiring_members']), 'Dashboard stats must contain expiring_members');
assert(isset($stats['today_checkins']), 'Dashboard stats must contain today_checkins');
assert(isset($stats['month_revenue']), 'Dashboard stats must contain month_revenue');

$chart = DashboardController::getRevenueChart($db);
assert(isset($chart['labels']) && is_array($chart['labels']), 'Chart must return labels array');
assert(count($chart['labels']) === 7, 'Chart must return 7 months of labels');
assert(isset($chart['series']) && is_array($chart['series']), 'Chart must return series array');

$memberChart = DashboardController::getMemberChart($db);
assert(isset($memberChart['labels']) && is_array($memberChart['labels']), 'Member chart must return labels array');
assert(count($memberChart['labels']) === 7, 'Member chart must return 7 months of labels');
assert(isset($memberChart['series']) && is_array($memberChart['series']), 'Member chart must return series array');
assert(isset($memberChart['total_7_months']), 'Member chart must return total_7_months');

$recent = DashboardController::getRecentCheckins($db);
assert(is_array($recent), 'Recent checkins must return array');

$expiring = DashboardController::getExpiringMembers($db);
assert(is_array($expiring), 'Expiring members must return array');

// 5. PackageController Tests
$packages = $db->query("SELECT * FROM paket ORDER BY durasi_hari ASC")->fetchAll();
assert(count($packages) === 3, 'Packages count must be exactly 3 (1 bulan, 3 bulan, 6 bulan)');
$expectedDurations = [30, 90, 180];
foreach ($packages as $i => $pkg) {
    assert((int)$pkg['durasi_hari'] === $expectedDurations[$i], "Package duration mismatch at index $i");
    assert(isset($pkg['nama_paket']), 'Package record must have nama_paket');
    assert(isset($pkg['harga']), 'Package record must have harga');
    assert(array_key_exists('benefit', $pkg), 'Package record must have benefit field');
}

ob_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
PackageController::index();
$pkgIndexOut = ob_get_clean();
$pkgIndexData = json_decode($pkgIndexOut, true);
assert($pkgIndexData['status'] === 'success', 'PackageController::index must return status success');
assert(count($pkgIndexData['data']) >= 3, 'PackageController::index must return at least 3 packages');
assert(isset($pkgIndexData['data'][0]['benefit_list']) && is_array($pkgIndexData['data'][0]['benefit_list']), 'Package item must include benefit_list array');

// Test PackageController::store() for 2-month package (60 days)
$_POST = [
    'nama_paket' => 'Paket 2 Bulan Test',
    'durasi_hari' => 60,
    'harga' => 280000,
    'benefit' => "Akses gym harian\nFree locker & shower",
    'tersedia' => 1,
];
ob_start();
PackageController::store();
$storePkgOut = ob_get_clean();
$_POST = [];
$storePkgData = json_decode($storePkgOut, true);
assert(($storePkgData['status'] ?? '') === 'success', 'PackageController::store must succeed');
assert((int)$storePkgData['data']['durasi_hari'] === 60, 'PackageController::store duration must be 60 days');
assert($storePkgData['data']['nama_paket'] === 'Paket 2 Bulan Test', 'Package name must be Paket 2 Bulan Test');
$testPkgId = (int)$storePkgData['data']['id'];

// Test PackageController::show()
ob_start();
PackageController::show($testPkgId);
$showPkgOut = ob_get_clean();
$showPkgData = json_decode($showPkgOut, true);
assert(($showPkgData['status'] ?? '') === 'success', 'PackageController::show must succeed');
assert($showPkgData['data']['nama_paket'] === 'Paket 2 Bulan Test', 'Package name must match');
assert((int)$showPkgData['data']['durasi_hari'] === 60, 'Package duration must be 60');

// Test PackageController::update()
$_POST = [
    'nama_paket' => 'Paket 2 Bulan Pro',
    'durasi_hari' => 60,
    'harga' => 290000,
    'benefit' => "Akses gym harian VIP\nFree locker & shower",
    'tersedia' => 1,
];
ob_start();
PackageController::update($testPkgId);
$upPkgOut = ob_get_clean();
$_POST = [];
$upPkgData = json_decode($upPkgOut, true);
assert(($upPkgData['status'] ?? '') === 'success', 'PackageController::update must succeed');
assert($upPkgData['data']['nama_paket'] === 'Paket 2 Bulan Pro', 'Updated package name must match');

// Clean up test package
ob_start();
PackageController::destroy($testPkgId);
$delPkgOut = ob_get_clean();
$delPkgData = json_decode($delPkgOut, true);
assert(($delPkgData['status'] ?? '') === 'success', 'PackageController::destroy must succeed');

// 6. Member Logic & Code Generation Tests
$newCode = AdminController::generateMemberCode($db);
assert(str_starts_with($newCode, 'GYM'), 'Generated member code must start with GYM');
assert(strlen($newCode) >= 18 && strlen($newCode) <= 30, 'Generated member code must be long (18-30 chars)');

ob_start();
MemberController::show(2);
$memberDetailOutput = ob_get_clean();
$memberDetail = json_decode($memberDetailOutput, true);
assert(isset($memberDetail['data']['member']['id']), 'Member detail must contain member ID');
assert(isset($memberDetail['data']['subscriptions']), 'Member detail must contain subscriptions array');
assert(isset($memberDetail['data']['transactions']), 'Member detail must contain transactions array');
assert(isset($memberDetail['data']['attendances']), 'Member detail must contain attendances array');

// Test MemberController::index (Person data, gender stats & filters)
$_GET['gender'] = 'all';
ob_start();
MemberController::index();
$memberIndexOutput = ob_get_clean();
$memberIndex = json_decode($memberIndexOutput, true);
assert(isset($memberIndex['data']['stats']['total']), 'Member stats must contain total');
assert(isset($memberIndex['data']['stats']['pria']), 'Member stats must contain pria');
assert(isset($memberIndex['data']['stats']['wanita']), 'Member stats must contain wanita');
assert(isset($memberIndex['data']['stats']['with_contact']), 'Member stats must contain with_contact');
assert(is_array($memberIndex['data']['members']), 'Members list must be an array');
if (!empty($memberIndex['data']['members'])) {
    $first = $memberIndex['data']['members'][0];
    assert(isset($first['gender_label']), 'Member item must contain gender_label');
    assert(isset($first['created_at_formatted']), 'Member item must contain created_at_formatted');
    assert(isset($first['emergency_contact']), 'Member item must contain emergency_contact');
}

// Test MemberController::index with gender filter = pria
$_GET['gender'] = 'pria';
ob_start();
MemberController::index();
$priaOutput = ob_get_clean();
$priaData = json_decode($priaOutput, true);
foreach ($priaData['data']['members'] as $pm) {
    assert($pm['gender'] === 'pria', 'Filtered member by pria must only return pria');
}
unset($_GET['gender']);

// Test Unique Member Code Generation for Attendance QR
$genMemberCode = AdminController::generateMemberCode($db);
assert(preg_match('/^GYM[-A-Z0-9]+$/', $genMemberCode), 'Generated member code must match attendance QR pattern');

// Test Member Registration with direct Package Activation & KTP Identity & Photo
$dummyPhoto = 'data:image/jpeg;base64,' . base64_encode('fake-jpeg-photo-content');
$_POST = [
    'nik' => '3201012345678901',
    'nama_lengkap' => 'Member Auto Paket Test',
    'nomor_telepon' => '089988776655',
    'jenis_kelamin' => 'pria',
    'tempat_lahir' => 'Jakarta',
    'tanggal_lahir' => '1995-08-17',
    'alamat' => 'Jl. Merdeka No. 45, Jakarta',
    'id_paket' => 1,
    'tanggal_mulai' => date('Y-m-d'),
    'metode_pembayaran' => 'qris',
    'foto' => $dummyPhoto,
];
ob_start();
MemberController::store();
$storeOut = ob_get_clean();
$_POST = [];
$storeData = json_decode($storeOut, true);
assert($storeData['status'] === 'success', 'Member registration with package must succeed');
assert($storeData['data']['nik'] === '3201012345678901', 'Member registered must have NIK');
assert($storeData['data']['tempat_lahir'] === 'Jakarta', 'Member registered must have tempat_lahir');
assert($storeData['data']['tanggal_lahir'] === '1995-08-17', 'Member registered must have tanggal_lahir');
assert(!empty($storeData['data']['foto']), 'Member registered must have foto path');
assert(file_exists(__DIR__ . '/../frontend/' . $storeData['data']['foto']), 'Member photo file must be saved to frontend/uploads/members/');
assert($storeData['data']['membership_status'] === 'active', 'Member registered with package must have active status');
assert(!empty($storeData['data']['membership_expires_at']), 'Member registered with package must have expires_at');
$newPassword = $storeData['data']['password'] ?? '';
assert(strlen($newPassword) === 8, 'Member registration must return an 8-character random password');
assert($newPassword !== '12345678', 'Member password must not be the old fixed default 12345678');
assert($db->query("SELECT password_plain FROM users WHERE id = " . (int)$storeData['data']['id'])->fetchColumn() === $newPassword, 'Stored password_plain must match returned password');
assert($storeData['data']['email'] === null, 'Member registered without email must have null email');
$newMemberId = (int)$storeData['data']['id'];
$newMemberCode = $storeData['data']['member_code'];

$dbEmail = $db->query("SELECT email FROM users WHERE id = $newMemberId")->fetchColumn();
assert($dbEmail === null, 'Database email column for newly created member must be null');

// Test MemberController::update photo semantics (ubah / pertahankan / hapus)
$originalFoto = $storeData['data']['foto'];

// Update tanpa key 'foto' -> foto lama dipertahankan
$_POST = ['name' => 'Member Auto Paket Test'];
ob_start(); MemberController::update($newMemberId); ob_end_clean(); $_POST = [];
assert($db->query("SELECT foto FROM users WHERE id = $newMemberId")->fetchColumn() === $originalFoto, 'Update without foto key must keep existing photo');

// Update dengan foto baru -> foto berubah & file tersimpan
$newPhoto = 'data:image/png;base64,' . base64_encode('foto-baru-edit');
$_POST = ['name' => 'Member Auto Paket Test', 'foto' => $newPhoto];
ob_start(); MemberController::update($newMemberId); ob_end_clean(); $_POST = [];
$changedFoto = $db->query("SELECT foto FROM users WHERE id = $newMemberId")->fetchColumn();
assert($changedFoto !== $originalFoto && !empty($changedFoto), 'Update with new foto must replace photo path');
assert(file_exists(__DIR__ . '/../frontend/' . $changedFoto), 'Updated member photo file must exist');

// Update dengan foto kosong -> foto dihapus
$_POST = ['name' => 'Member Auto Paket Test', 'foto' => ''];
ob_start(); MemberController::update($newMemberId); ob_end_clean(); $_POST = [];
assert($db->query("SELECT foto FROM users WHERE id = $newMemberId")->fetchColumn() === null, 'Update with empty foto must delete photo');

// Bersihkan file foto uji
if (!empty($changedFoto) && file_exists(__DIR__ . '/../frontend/' . $changedFoto)) {
    @unlink(__DIR__ . '/../frontend/' . $changedFoto);
}

// Verify member can login using member_code and the generated random password
ob_start();
AuthController::login(['identifier' => $newMemberCode, 'password' => $newPassword]);
$newLoginOut = ob_get_clean();
$newLoginData = json_decode($newLoginOut, true);
assert(($newLoginData['status'] ?? '') === 'ok' && $newLoginData['data']['member_code'] === $newMemberCode, 'Newly created member must be able to login with member_code and generated password');

// Test MemberController::resetPassword generates a new usable password
ob_start();
MemberController::resetPassword($newMemberId);
$resetData = json_decode(ob_get_clean(), true);
$resetPassword = $resetData['data']['password'] ?? '';
assert(strlen($resetPassword) === 8, 'resetPassword must return an 8-character password');
assert($resetPassword !== $newPassword, 'resetPassword must produce a different password');
ob_start();
AuthController::login(['identifier' => $newMemberCode, 'password' => $resetPassword]);
$resetLoginData = json_decode(ob_get_clean(), true);
assert(($resetLoginData['status'] ?? '') === 'ok', 'Member must be able to login with the reset password');

// Check database records for langganan and transaksi
$testSub = $db->query("SELECT * FROM langganan WHERE id_user = $newMemberId ORDER BY id DESC LIMIT 1")->fetch();
assert($testSub !== false, 'Langganan record must be created on member registration with package');
$testTrx = $db->query("SELECT * FROM transaksi WHERE id_langganan = {$testSub['id']}")->fetch();
assert($testTrx !== false, 'Transaksi record must be created on member registration with package');
assert($testTrx['metode_pembayaran'] === 'qris', 'Metode pembayaran in transaksi must match input');

// Test Cashier Perpanjang Paket on this member
$prevExpiry = $storeData['data']['membership_expires_at'];
$_POST = [
    'user_id' => $newMemberId,
    'paket_id' => 1, // 30 days
    'jumlah_bayar' => 150000,
    'metode_pembayaran' => 'tunai',
];
ob_start();
CashierController::createTransaction();
$renewOut = ob_get_clean();
$_POST = [];
$renewData = json_decode($renewOut, true);
assert($renewData['status'] === 'ok', 'Cashier perpanjang must return ok status');
assert($renewData['data']['tipe_transaksi'] === 'Perpanjang Paket', 'Cashier must return Perpanjang Paket as tipe_transaksi');
$expectedRenewExpiry = date('Y-m-d', strtotime("$prevExpiry + 31 days")); // +1 day start + 30 days
assert($renewData['data']['member']['membership_expires_at'] === $expectedRenewExpiry, 'Perpanjangan must extend from previous expiry date');

// Clean up test member (cascades to langganan & transaksi)
$db->exec("DELETE FROM users WHERE id = $newMemberId");

// 7. Attendance Logic Tests
$todayCount = (int)$db->query("SELECT COUNT(*) FROM presensi WHERE DATE(waktu_presensi) = CURDATE()")->fetchColumn();
assert($todayCount >= 0, 'Today checkins query must execute successfully');

// Check attendance today endpoint
ob_start();
AttendanceController::today();
$todayOut = ob_get_clean();
$todayData = json_decode($todayOut, true);
assert($todayData['status'] === 'ok', 'AttendanceController::today must return ok status');

// Test Duplicate Attendance Check-in on same day
$activeMemberCode = (string)$db->query("SELECT member_code FROM users WHERE role = 'user' AND membership_status = 'active' LIMIT 1")->fetchColumn();
if ($activeMemberCode !== '') {
    // Pastikan member sudah memiliki presensi hari ini
    $hasPresensi = (int)$db->query("SELECT COUNT(*) FROM presensi p JOIN users u ON u.id = p.id_user WHERE u.member_code = '$activeMemberCode' AND DATE(p.waktu_presensi) = CURDATE()")->fetchColumn();
    if ($hasPresensi === 0) {
        $uid = (int)$db->query("SELECT id FROM users WHERE member_code = '$activeMemberCode'")->fetchColumn();
        $db->exec("INSERT INTO presensi (id_user, waktu_presensi) VALUES ($uid, NOW())");
    }
    // Verifikasi query deteksi presensi ganda hari ini
    $dupCheck = $db->prepare('SELECT id, waktu_presensi FROM presensi WHERE id_user = (SELECT id FROM users WHERE member_code = ?) AND DATE(waktu_presensi) = CURRENT_DATE LIMIT 1');
    $dupCheck->execute([$activeMemberCode]);
    $dupRow = $dupCheck->fetch();
    assert($dupRow !== false && isset($dupRow['id']), 'Duplicate checkin detection query must find today record');
}

// 8. Transaction & Reports Logic Tests
$totalRev = (float)$db->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM transaksi")->fetchColumn();
assert($totalRev >= 0, 'Total revenue calculation must return non-negative number');

// 9. User Area Controllers & Endpoints Tests
assert(class_exists('UserController'), 'UserController class not found');
assert(class_exists('UserDashboardController'), 'UserDashboardController class not found');
assert(class_exists('UserAttendanceController'), 'UserAttendanceController class not found');
assert(class_exists('UserMembershipController'), 'UserMembershipController class not found');

// Test User Dashboard
ob_start();
UserDashboardController::index();
$userDashOut = ob_get_clean();
$userDashData = json_decode($userDashOut, true);
assert($userDashData['status'] === 'success', 'User dashboard must return success status');
assert(isset($userDashData['data']['user']['name']), 'User dashboard must contain user name');
assert(!empty($userDashData['data']['user']['member_code']), 'User dashboard must contain member_code for digital QR card');
assert(isset($userDashData['data']['stats']['days_remaining']), 'User dashboard must contain days_remaining');
assert(isset($userDashData['data']['stats']['today_checkin']), 'User dashboard must contain today_checkin');
assert(is_array($userDashData['data']['recent_attendances']), 'User dashboard must contain recent_attendances array');
assert(is_array($userDashData['data']['recent_packages']), 'User dashboard must contain recent_packages array');

// Test User Attendance
ob_start();
UserAttendanceController::index();
$userAttOut = ob_get_clean();
$userAttData = json_decode($userAttOut, true);
assert($userAttData['status'] === 'success', 'User attendance must return success status');
assert(isset($userAttData['data']['total']), 'User attendance must contain total');
assert(is_array($userAttData['data']['attendances']), 'User attendance must contain attendances array');

// Test User Membership
ob_start();
UserMembershipController::index();
$userMemOut = ob_get_clean();
$userMemData = json_decode($userMemOut, true);
assert($userMemData['status'] === 'success', 'User membership must return success status');
assert(isset($userMemData['data']['active']['package_name']), 'User membership must contain active package_name');
assert(isset($userMemData['data']['active']['days_remaining']), 'User membership must contain active days_remaining');
assert(is_array($userMemData['data']['history']), 'User membership must contain history array');

// 10. User Settings / Profile & Password Change Tests
$_SESSION['user'] = ['id' => 2, 'role' => 'user'];
ob_start();
UserController::getProfile();
$profileOut = ob_get_clean();
$profileData = json_decode($profileOut, true);
assert(($profileData['status'] ?? '') === 'success', 'UserController::getProfile must succeed');
assert(isset($profileData['data']['user']['member_code']), 'User profile must contain member_code');
assert(isset($profileData['data']['user']['email']), 'User profile must contain email');

// Test update profile: member (role user) tidak boleh mengubah data diri
$_POST = [
    'name' => 'Gym Member Updated',
    'phone' => '081234567899',
    'gender' => 'pria',
    'address' => 'Jl. Baru No. 123',
    'emergency_contact' => '0811998877',
];
ob_start();
UserController::updateProfile();
$updateOut = ob_get_clean();
$_POST = [];
$updateData = json_decode($updateOut, true);
assert(isset($updateData['error']), 'Non-admin updateProfile must be rejected');

// Test change password validation (wrong current password)
$_POST = [
    'current_password' => 'wrongcurrentpassword',
    'new_password' => 'newpassword123',
    'confirm_password' => 'newpassword123'
];
ob_start();
UserController::changePassword();
$pwErrOut = ob_get_clean();
$_POST = [];
$pwErrData = json_decode($pwErrOut, true);
assert(isset($pwErrData['error']), 'Wrong current password must return error');

// Test change password validation (mismatched confirm)
$_POST = [
    'current_password' => 'user123',
    'new_password' => 'newpassword123',
    'confirm_password' => 'differentpassword'
];
ob_start();
UserController::changePassword();
$pwMismatchOut = ob_get_clean();
$_POST = [];
$pwMismatchData = json_decode($pwMismatchOut, true);
assert(isset($pwMismatchData['error']), 'Mismatched confirm password must return error');

// Test change password success
$_POST = [
    'current_password' => 'user123',
    'new_password' => 'newpassword123',
    'confirm_password' => 'newpassword123'
];
ob_start();
UserController::changePassword();
$pwSuccessOut = ob_get_clean();
$_POST = [];
$pwSuccessData = json_decode($pwSuccessOut, true);
assert(($pwSuccessData['status'] ?? '') === 'success', 'Password change must succeed with correct credentials');

// Revert password back to user123 so seed tests remain intact
$_POST = [
    'current_password' => 'newpassword123',
    'new_password' => 'user123',
    'confirm_password' => 'user123'
];
ob_start();
UserController::changePassword();
ob_end_clean();
$_POST = [];

// 11. PackageController Dynamic Popularity Test
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
ob_start();
PackageController::index();
$pkgOut = ob_get_clean();
$pkgData = json_decode($pkgOut, true);
assert(($pkgData['status'] ?? '') === 'success', 'PackageController::index must return success');
assert(isset($pkgData['data'][0]['total_subscribers']), 'PackageController must return total_subscribers');
assert(isset($pkgData['data'][0]['is_popular']), 'PackageController must return is_popular');

// 12. Reports & Filtered Export (Excel XLSX & PDF) Tests
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
$_GET['start_date'] = date('Y-m-01');
$_GET['end_date'] = date('Y-m-d');

$repData = LaporanController::fetchReportData($_GET['start_date'], $_GET['end_date'], false);
assert(isset($repData['summary']['revenue']), 'Report data must calculate filtered revenue');
assert(isset($repData['period']['start_date']), 'Report data must contain filtered start date');
assert(isset($repData['packages']), 'Report data must contain packages performance');
assert(isset($repData['transactions']), 'Report data must contain transactions');

// Test XLSX Export
ob_start();
LaporanController::exportXlsx();
$xlsxBinary = ob_get_clean();
assert(strlen($xlsxBinary) > 500, 'XLSX export must generate non-empty binary');
assert(substr($xlsxBinary, 0, 4) === "PK\x03\x04", 'XLSX export must be valid OpenXML ZIP archive');

// Test PDF Export
ob_start();
LaporanController::exportPdf();
$pdfBinary = ob_get_clean();
assert(strlen($pdfBinary) > 500, 'PDF export must generate non-empty binary');
assert(substr($pdfBinary, 0, 5) === '%PDF-', 'PDF export must begin with %PDF- header');

unset($_GET['start_date'], $_GET['end_date']);

echo "✓ All backend unit tests passed successfully!\n";

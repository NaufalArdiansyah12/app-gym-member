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

// 1. AuthController Tests
assert(class_exists('AuthController'), 'AuthController class not found');
assert(method_exists('AuthController', 'login'), 'AuthController::login method not found');

ob_start();
AuthController::login();
$output = ob_get_clean();
$data = json_decode($output, true);
assert(isset($data['error']), 'Empty login payload must return error message');

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

$recent = DashboardController::getRecentCheckins($db);
assert(is_array($recent), 'Recent checkins must return array');

$expiring = DashboardController::getExpiringMembers($db);
assert(is_array($expiring), 'Expiring members must return array');

// 5. PackageController Tests
$packages = $db->query("SELECT * FROM paket")->fetchAll();
assert(!empty($packages), 'Packages table must have seeded packages');
assert(isset($packages[0]['nama_paket']), 'Package record must have nama_paket');
assert(isset($packages[0]['durasi_hari']), 'Package record must have durasi_hari');
assert(isset($packages[0]['harga']), 'Package record must have harga');

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

// 7. Attendance Logic Tests
$todayCount = (int)$db->query("SELECT COUNT(*) FROM presensi WHERE DATE(waktu_presensi) = CURDATE()")->fetchColumn();
assert($todayCount >= 0, 'Today checkins query must execute successfully');

// Check attendance today endpoint
ob_start();
AttendanceController::today();
$todayOut = ob_get_clean();
$todayData = json_decode($todayOut, true);
assert($todayData['status'] === 'ok', 'AttendanceController::today must return ok status');

// 8. Transaction & Reports Logic Tests
$totalRev = (float)$db->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM transaksi")->fetchColumn();
assert($totalRev >= 0, 'Total revenue calculation must return non-negative number');

echo "✓ All backend unit tests passed successfully!\n";

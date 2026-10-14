<?php
// backend/test.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/controller/admin/CashierController.php';

// 1. AuthController Tests
assert(class_exists('AuthController'), 'AuthController class not found');
assert(method_exists('AuthController', 'login'), 'AuthController::login method not found');

// Check CashierController
assert(class_exists('CashierController'), 'CashierController class not found');
assert(method_exists('CashierController', 'initData'), 'CashierController::initData not found');
assert(method_exists('CashierController', 'createTransaction'), 'CashierController::createTransaction not found');
assert(method_exists('CashierController', 'getTransactions'), 'CashierController::getTransactions not found');

// Check validation logic when payload empty
ob_start();
AuthController::login();
$output = ob_get_clean();
$data = json_decode($output, true);

assert(isset($data['error']), 'Empty login payload must return error message');
assert(http_response_code() === 422, 'Empty login payload must return 422 status code');
echo "✓ AuthController tests passed.\n";

// Check CashierController validation
ob_start();
CashierController::createTransaction();
$cashierOut = ob_get_clean();
$cashierData = json_decode($cashierOut, true);
assert(isset($cashierData['error']), 'Empty cashier payload must return error message');
assert(http_response_code() === 422, 'Empty cashier payload must return 422 status code');

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
assert(count($searchData['data']) >= 1, 'Cashier searchMembers must find member Siti');
unset($_GET['q']);

// Check users table columns
$db = getDB();
$cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$expected = ['member_code', 'phone', 'gender', 'membership_status', 'membership_expires_at'];
foreach ($expected as $col) {
    assert(in_array($col, $cols, true), "Column $col missing in users table");
}

echo "AuthController unit tests passed.\n";

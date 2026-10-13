<?php
// backend/test.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';

// Check Controller class
assert(class_exists('AuthController'), 'AuthController class not found');
assert(method_exists('AuthController', 'login'), 'AuthController::login method not found');

// Check validation logic when payload empty
ob_start();
AuthController::login();
$output = ob_get_clean();
$data = json_decode($output, true);

assert(isset($data['error']), 'Empty login payload must return error message');
assert(http_response_code() === 422, 'Empty login payload must return 422 status code');

echo "AuthController unit tests passed.\n";

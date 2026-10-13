<?php
// backend/test.php
require_once __DIR__ . '/config/database.php';

$db = getDB();
assert($db instanceof PDO, 'Database connection failed');

// Test insert & query
$testEmail = 'test_' . time() . '@example.com';
$stmt = $db->prepare('INSERT INTO members (name, email, phone) VALUES (?, ?, ?)');
$stmt->execute(['Test User', $testEmail, '08123456789']);
$id = (int) $db->lastInsertId();
assert($id > 0, 'Insert member failed');

$stmt = $db->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
assert($row['email'] === $testEmail, 'Fetch member failed');

// Cleanup
$db->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);

echo "All backend tests passed.\n";

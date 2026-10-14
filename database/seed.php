<?php
// database/seed.php
require_once __DIR__ . '/../backend/config/database.php';

$sql = file_get_contents(__DIR__ . '/schema.sql');
$db = getDB();

$queries = explode(";\n", str_replace("\r\n", "\n", $sql));

foreach ($queries as $q) {
    $trimmed = trim($q);
    if ($trimmed !== '') {
        try {
            $db->exec($trimmed);
        } catch (PDOException $e) {
            echo "Error executing query: " . $e->getMessage() . "\n";
        }
    }
}

echo "Database seeded successfully.\n";


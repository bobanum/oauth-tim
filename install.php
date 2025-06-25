<?php
require './vendor/autoload.php';
// Installation script for OAuth provider
// Get DOTENV variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db_file = $_ENV['DB_NAME'];
if (file_exists($db_file)) {
    try {
        unlink($db_file);
    } catch (PDOException $e) {
        echo "Error deleting existing database file: " . $e->getMessage() . "\n";
    }
}
$pdo = new PDO('sqlite:' . $db_file);
$sql = file_get_contents(__DIR__ . '/database/sql/db.sqlite.sql');
$pdo->exec($sql);

$sql = file_get_contents(__DIR__ . '/database/sql/provider.sql');
$pdo->exec($sql);


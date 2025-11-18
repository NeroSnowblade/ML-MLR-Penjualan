<?php
// Database configuration

// [Local]
$host = 'localhost';
$dbname = 'sales_prediction';
$username = 'root';
$password = '';
$port = '3306';
$charset = 'utf8mb4';

// [InfinityFree]
// $host = 'sql213.infinityfree.com';
// $dbname = 'if0_40096945_sales_prediction';
// $username = 'if0_40096945';
// $password = 'gjfIeQLSCdTz4';
// $port = '3306';
// $charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
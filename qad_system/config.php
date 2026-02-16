<?php
$host = 'localhost';
$db   = 'qad_system';
$user = 'root';      // Adjust if using a password
$pass = '';          // Adjust if using a password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If this is an API call, return JSON error
    if(strpos($_SERVER['REQUEST_URI'], 'api.php') !== false) {
        die(json_encode(['success' => false, 'message' => "Database Connection Failed"]));
    }
    die("Database Connection Failed: " . $e->getMessage());
}
?>
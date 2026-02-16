<?php
$host = 'localhost';
$db   = 'qad_system';
$user = 'root'; // Change if your XAMPP/WAMP user is different
$pass = '';     // Change if you have a password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}
?>
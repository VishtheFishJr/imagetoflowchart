<?php
// db.php - Database Connectio

// If PHP runs on the same Droplet, keep 'localhost'.
// If PHP runs locally on your machine, replace 'localhost' with your Droplet IP '64.23.131.157'.
$host = 'localhost';
$db = 'image_ai_db';
$user = 'root';                // Or your specific MySQL user
$pass = 'YourNewRootPassword123!';    // Your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
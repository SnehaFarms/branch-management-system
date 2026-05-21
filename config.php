<?php
// Ensure session starts properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";      // Default XAMPP user
$pass = "";          // Default XAMPP password is empty
$dbname = "branch_db";

try {
    // Creating the PDO connection and storing it in $conn
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
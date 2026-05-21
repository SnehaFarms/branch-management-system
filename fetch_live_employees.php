<?php
// Include database connection
require_once 'config.php';

// Security Check: Only allow logged-in HO Users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ho_user') {
    echo json_encode([]);
    exit;
}

$selected_branch = isset($_GET['branch_filter']) ? $_GET['branch_filter'] : '';

// Fetch data based on filter
if (!empty($selected_branch)) {
    $query = "SELECT e.*, b.branch_name, b.branch_code 
              FROM employees e 
              JOIN branches b ON e.branch_id = b.id 
              WHERE e.branch_id = :branch_id 
              ORDER BY e.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':branch_id', $selected_branch, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $query = "SELECT e.*, b.branch_name, b.branch_code 
              FROM employees e 
              JOIN branches b ON e.branch_id = b.id 
              ORDER BY e.id DESC";
    $stmt = $conn->query($query);
}

$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send data back as clean JSON format for JavaScript to read live
header('Content-Type: application/json');
echo json_encode($employees);
?>
<?php
session_start();

// Restrict access: only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Get record ID
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($record_id > 0) {
    $stmt = $conn->prepare("DELETE FROM file_records WHERE file_id = ?");
    $stmt->execute([$record_id]);
}

// Redirect back to veterinary records admin page
header("Location: veterinary_records_admin.php");
exit();
?>

<?php
try {
    $conn = new PDO('sqlite:' . __DIR__ . '/data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$admin_user = 'admin1';
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);

$staff_user = 'staff1';
$staff_pass = password_hash('staff123', PASSWORD_DEFAULT);

// Admin user
$stmt1 = $conn->prepare("
    INSERT INTO users (username, password, role) 
    VALUES (?, ?, 'admin')
    ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)
");
$stmt1->bind_param("ss", $admin_user, $admin_pass);
$stmt1->execute();

// Staff user
$stmt2 = $conn->prepare("
    INSERT INTO users (username, password, role) 
    VALUES (?, ?, 'staff')
    ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)
");
$stmt2->bind_param("ss", $staff_user, $staff_pass);
$stmt2->execute();

echo "✅ Admin and Staff users created/updated successfully!";
?>

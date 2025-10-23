<?php
session_start();
// Restrict access: only staff can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// Ensure a record ID is provided in the URL
if (!isset($_GET['id'])) {
    echo "No record ID provided.";
    exit();
}

$record_id = $_GET['id'];

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Fetch the veterinary record by record_id
$stmt = $conn->prepare("SELECT * FROM veterinary_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Validate that the record exists
if (!$record) {
    echo "Record not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Veterinary Record</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            padding: 40px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        h2 {
            margin-bottom: 25px;
            color: #0f766e;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #f9fafb;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 20px;
            background-color: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .back-btn:hover {
            background-color: #059669;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🐶 Veterinary Record Details</h2>

    <label>Authenticated Code:</label>
    <input type="text" value="<?= htmlspecialchars($record['auth_code']) ?>" readonly>

    <label>Diagnosis:</label>
    <textarea readonly><?= htmlspecialchars($record['diagnosis']) ?></textarea>

    <label>Treatment:</label>
    <textarea readonly><?= htmlspecialchars($record['treatment']) ?></textarea>

    <label>Medication:</label>
    <textarea readonly><?= htmlspecialchars($record['medication']) ?></textarea>

    <label>Remarks:</label>
    <textarea readonly><?= htmlspecialchars($record['remarks']) ?></textarea>

    <label>Recorded By:</label>
    <input type="text" value="<?= htmlspecialchars($record['recorded_by']) ?>" readonly>

    <label>Created At:</label>
    <input type="text" value="<?= date('M d, Y h:i A', strtotime($record['created_at'])) ?>" readonly>

    <a href="veterinary_records.php" class="back-btn">⬅ Back to Records</a>
</div>

</body>
</html>

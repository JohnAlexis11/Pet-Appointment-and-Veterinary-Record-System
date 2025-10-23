<?php
session_start();

// Restrict access: only staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

// DB connection
try {
    $conn = new PDO('sqlite:' . __DIR__ . '/data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get appointment ID
$appointment_id = intval($_GET['id'] ?? 0);

// SQLite doesn't support CONCAT — use string concatenation with ||
$sql = "SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            p.pet_name,
            p.species,
            p.breed,
            (o.first_name || ' ' || o.last_name) AS owner_name,
            o.contact_number,
            o.address
        FROM appointments a
        INNER JOIN pets p ON a.pet_id = p.pet_id
        INNER JOIN owners o ON p.owner_id = o.owner_id
        WHERE a.appointment_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Appointment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: 'Poppins', sans-serif;
            padding: 40px;
        }
        .card {
            border-radius: 16px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(90deg, #34d399, #10b981);
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .card-body label {
            font-weight: 600;
            color: #065f46;
        }
        .btn-success {
            background-color: #34d399;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 500;
        }
        .btn-success:hover {
            background-color: #10b981;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if ($appointment): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Appointment History (ID: <?= $appointment['appointment_id'] ?>)
            </div>
            <div class="card-body">
                <p><label>Pet Name:</label> <?= htmlspecialchars($appointment['pet_name']) ?></p>
                <p><label>Species:</label> <?= htmlspecialchars($appointment['species'] ?? 'N/A') ?></p>
                <p><label>Breed:</label> <?= htmlspecialchars($appointment['breed'] ?? 'N/A') ?></p>
                <hr>
                <p><label>Owner:</label> <?= htmlspecialchars($appointment['owner_name']) ?></p>
                <p><label>Contact:</label> <?= htmlspecialchars($appointment['contact_number']) ?></p>
                <p><label>Address:</label> <?= htmlspecialchars($appointment['address']) ?></p>
                <hr>
                <p><label>Date & Time:</label> 
                    <?= date('M d, Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) ?>
                </p>
                <p><label>Status:</label> <?= htmlspecialchars($appointment['status']) ?></p>
                <div class="mt-4">
                    <a href="staff_dashboard.php" class="btn btn-success"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">❌ Appointment not found.</div>
        <a href="staff_dashboard.php" class="btn btn-success"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <?php endif; ?>
</div>
</body>
</html>

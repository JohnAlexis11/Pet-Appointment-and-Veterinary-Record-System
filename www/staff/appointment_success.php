<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
if ($appointment_id <= 0) {
    header("Location: staff_dashboard.php");
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


$sql = "SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            o.first_name,
            o.last_name,
            p.pet_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        WHERE a.appointment_id = :appointment_id";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':appointment_id', $appointment_id, PDO::PARAM_INT);
$stmt->execute();
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    echo "Appointment not found!";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Booked - PetLandia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(to right, #d1fae5, #a7f3d0);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.card {
    max-width: 600px;
    width: 100%;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    overflow: hidden;
    animation: fadeIn 0.5s ease-in-out;
}
.card-header {
    background: linear-gradient(90deg, #34d399, #10b981);
    color: white;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    padding: 25px;
}
.card-body {
    padding: 30px 25px;
}
.success-icon {
    font-size: 3rem;
    color: #10b981;
    margin-bottom: 15px;
}
.info {
    background: #f0fdf4;
    border-left: 5px solid #10b981;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}
.info p {
    margin: 8px 0;
    font-size: 1rem;
}
.label {
    font-weight: 600;
    color: #065f46;
}
.btn-dashboard {
    background-color: #34d399;
    border: none;
    color: white;
    font-weight: 600;
    padding: 12px 25px;
    border-radius: 10px;
    transition: 0.3s;
    display: inline-block;
}
.btn-dashboard:hover {
    background-color: #10b981;
    text-decoration: none;
    color: white;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px);}
    to { opacity: 1; transform: translateY(0);}
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <i class="fas fa-check-circle success-icon"></i><br>
        Appointment Successfully Booked!
    </div>
    <div class="card-body">
        <div class="info">
            <p><span class="label">Appointment ID:</span> <?= htmlspecialchars($appointment['appointment_id']) ?></p>
            <p><span class="label">Owner Name:</span> <?= htmlspecialchars($appointment['first_name'] . " " . $appointment['last_name']) ?></p>
            <p><span class="label">Pet Name:</span> <?= htmlspecialchars($appointment['pet_name']) ?></p>
            <p><span class="label">Reason:</span> <?= htmlspecialchars($appointment['reason']) ?></p>
            <p><span class="label">Date & Time:</span> <?= date('M d, Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])) ?></p>
        </div>
        <div class="text-center">
            <a href="../staff_dashboard.php" class="btn btn-dashboard"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

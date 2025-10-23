<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

try {
    $conn = new PDO('sqlite:' . __DIR__ . '/data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$id = intval($_GET['id']);
$sql = "SELECT a.auth_code, a.appointment_date, a.reason, a.status,
               p.pet_name, p.species, p.breed, p.gender, p.age,
               o.full_name, o.contact_number, o.email, o.address
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN owners o ON p.owner_id = o.owner_id
        WHERE a.appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();

// Fetch veterinary records if auth_code exists
$vet_records = [];
if ($appointment && !empty($appointment['auth_code'])) {
    $stmt2 = $conn->prepare("SELECT * FROM veterinary_records WHERE auth_code = ? ORDER BY created_at DESC");
    $stmt2->bind_param("s", $appointment['auth_code']);
    $stmt2->execute();
    $vet_records = $stmt2->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 30px;
        }
        .card {
            width: 100%;
            max-width: 900px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        .card-header {
            background-color: #0ea5e9;
            color: white;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            padding: 20px;
        }
        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .section-title {
            color: #0284c7;
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .info p {
            margin: 4px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        tr:hover {
            background-color: #f0f9ff;
        }
        .btn-secondary {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header text-center">
            <h4><i class="fas fa-calendar-check me-2"></i>Appointment Details</h4>
        </div>
        <div class="card-body">
            <?php if ($appointment): ?>
               <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Appointment Info</h5>
<div class="info">
    <p><strong>Auth Code:</strong> <?= $appointment['auth_code'] ?></p>
    <p><strong>Date & Time:</strong> <?= date('F d, Y g:i A', strtotime($appointment['appointment_date'])) ?></p>
    <p><strong>Status:</strong> <?= $appointment['status'] ?></p>
    <p><strong>Reason:</strong> <?= $appointment['reason'] ?></p>
</div>


                <h5 class="section-title"><i class="fas fa-paw me-2"></i>Pet Information</h5>
                <div class="info">
                    <p><strong>Name:</strong> <?= $appointment['pet_name'] ?></p>
                    <p><strong>Species:</strong> <?= $appointment['species'] ?></p>
                    <p><strong>Breed:</strong> <?= $appointment['breed'] ?></p>
                    <p><strong>Gender:</strong> <?= $appointment['gender'] ?></p>
                    <p><strong>Age:</strong> <?= $appointment['age'] ?></p>
                </div>

                <h5 class="section-title"><i class="fas fa-user me-2"></i>Owner Information</h5>
                <div class="info">
                    <p><strong>Name:</strong> <?= $appointment['full_name'] ?></p>
                    <p><strong>Contact:</strong> <?= $appointment['contact_number'] ?></p>
                    <p><strong>Email:</strong> <?= $appointment['email'] ?></p>
                    <p><strong>Address:</strong> <?= $appointment['address'] ?></p>
                </div>

                <h5 class="section-title"><i class="fas fa-notes-medical me-2"></i>Veterinary Records</h5>
                <?php if ($vet_records && $vet_records->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Medication</th>
                                <th>Remarks</th>
                                <th>Recorded By</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $vet_records->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['diagnosis']) ?></td>
                                    <td><?= htmlspecialchars($row['treatment']) ?></td>
                                    <td><?= htmlspecialchars($row['medication']) ?></td>
                                    <td><?= htmlspecialchars($row['remarks']) ?></td>
                                    <td><?= htmlspecialchars($row['recorded_by']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No veterinary records found for this appointment.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-danger">❌ Appointment not found.</p>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="staff_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

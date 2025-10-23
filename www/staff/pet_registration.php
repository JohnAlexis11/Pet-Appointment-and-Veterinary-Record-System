<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

$owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : (isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0);
if ($owner_id <= 0) { die("Owner ID is missing!"); }

$appointment_date = $_GET['appointment_date'] ?? ($_POST['appointment_date'] ?? null);
$appointment_time = $_GET['appointment_time'] ?? ($_POST['appointment_time'] ?? null);
$message = "";

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- Get owner name ---
$chk = $conn->prepare("SELECT first_name || ' ' || last_name AS owner_name FROM owners WHERE owner_id = ?");
$chk->execute([$owner_id]);
$ownerRow = $chk->fetch(PDO::FETCH_ASSOC);
if (!$ownerRow) { die("Owner not found."); }
$ownerName = $ownerRow['owner_name'];

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = intval($_POST['owner_id']);
    $pet_name = trim($_POST['pet_name']);
    $species = trim($_POST['species']);
    $breed = trim($_POST['breed']);
    $sex = $_POST['sex'];
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
    $color = trim($_POST['color']);
    $microchip_no = isset($_POST['microchip_no']) && $_POST['microchip_no'] !== '' ? trim($_POST['microchip_no']) : null;
    $reason = trim($_POST['reason']);
    $appointment_date = $_POST['appointment_date'] ?? null;
    $appointment_time = $_POST['appointment_time'] ?? null;

    if (!$appointment_date || !$appointment_time) {
        $message = "Appointment date/time missing. Please go back to owner registration.";
    } elseif (empty($pet_name) || empty($species) || empty($breed) || empty($sex) || empty($reason)) {
        $message = "Please fill all required fields.";
    } else {
        try {
            // --- Insert pet record ---
            $stmt = $conn->prepare("INSERT INTO pets (owner_id, pet_name, species, breed, sex, age, color, microchip_no, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmt->execute([$owner_id, $pet_name, $species, $breed, $sex, $age, $color, $microchip_no]);
            $pet_id = $conn->lastInsertId();

            // --- Insert appointment record ---
            $status = "Pending";
            $created_by = $_SESSION['user_id'] ?? 0;
            $stmt2 = $conn->prepare("INSERT INTO appointments (owner_id, pet_id, appointment_date, appointment_time, reason, status, created_by, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
            $stmt2->execute([$owner_id, $pet_id, $appointment_date, $appointment_time, $reason, $status, $created_by]);
            $appointment_id = $conn->lastInsertId();

            header("Location: appointment_success.php?appointment_id=$appointment_id");
            exit();
        } catch (PDOException $e) {
            $message = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pet Registration - PetLandia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    background-color: #f0f4f8;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    padding: 40px 20px;
}
.card {
    width: 100%;
    max-width: 900px;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.card-header {
    background: linear-gradient(90deg, #34d399, #10b981);
    color: white;
    font-weight: 600;
    padding: 20px;
    border-radius: 16px 16px 0 0;
    text-align: center;
    font-size: 1.25rem;
}
.card-body {
    padding: 30px 40px;
}
.form-control, .form-select, textarea {
    border-radius: 10px;
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    transition: 0.3s;
}
.form-control:focus, .form-select:focus, textarea:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 0.2rem rgba(16,185,129,0.25);
}
textarea { resize: vertical; }
.btn-primary {
    background-color: #34d399;
    border: none;
    border-radius: 10px;
    font-weight: 500;
    padding: 12px;
    width: 100%;
    transition: 0.3s;
}
.btn-primary:hover { background-color: #10b981; }
.alert { border-radius: 10px; }
@media(max-width:768px) {
    .card-body { padding: 20px; }
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header"><i class="fas fa-paw me-2"></i> Pet Registration</div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="owner_id" value="<?= htmlspecialchars($owner_id) ?>">
            <input type="hidden" name="appointment_date" value="<?= htmlspecialchars($appointment_date) ?>">
            <input type="hidden" name="appointment_time" value="<?= htmlspecialchars($appointment_time) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Owner</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($ownerName) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Selected Appointment</label>
                    <input type="text" class="form-control" 
                           value="<?= $appointment_date && $appointment_time ? date('M d, Y', strtotime($appointment_date)) . ' — ' . date('g:i A', strtotime($appointment_time)) : 'Not selected' ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Pet Name</label>
                    <input type="text" class="form-control" name="pet_name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Species</label>
                    <input type="text" class="form-control" name="species" placeholder="Dog, Cat, Bird..." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Breed</label>
                    <input type="text" class="form-control" name="breed" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sex</label>
                    <select class="form-select" name="sex" required>
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Age (Optional)</label>
                    <input type="number" class="form-control" name="age" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control" name="color" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Microchip No (Optional)</label>
                    <input type="text" class="form-control" name="microchip_no">
                </div>
                <div class="col-12">
                    <label class="form-label">Reason for Appointment</label>
                    <textarea class="form-control" name="reason" rows="2" required></textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right me-2"></i> Next: Confirm Appointment</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

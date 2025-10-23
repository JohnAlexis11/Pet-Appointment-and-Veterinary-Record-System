<?php
session_start();

// Restrict access: staff/admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','admin'])) {
    header("Location: ../login.php");
    exit();
}

// Set timezone
date_default_timezone_set("Asia/Manila");

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


$owner = $_GET['owner'] ?? '';
$pet   = $_GET['pet'] ?? '';

// Add new vet record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $species     = $_POST['species'];
    $breed       = $_POST['breed'];
    $age         = $_POST['age'];
    $sex         = $_POST['sex'];
    $diagnosis   = $_POST['diagnosis'];
    $treatment   = $_POST['treatment'];
    $remarks     = $_POST['remarks'];
    $recorded_by = $_SESSION['username'];
    $created_at  = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO vet_records 
        (owner_name, pet_name, species, breed, age, sex, diagnosis, treatment, remarks, recorded_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$owner, $pet, $species, $breed, $age, $sex, $diagnosis, $treatment, $remarks, $recorded_by, $created_at]);

    header("Location: pet_history.php?owner=" . urlencode($owner) . "&pet=" . urlencode($pet) . "&success=1");
    exit();
}

// Fetch medical history
$stmt = $conn->prepare("SELECT * FROM vet_records WHERE owner_name=? AND pet_name=? ORDER BY created_at DESC");
$stmt->execute([$owner, $pet]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch summary (from file_records)
$info_stmt = $conn->prepare("SELECT address, contact_number, email, color, microchip_no 
                             FROM file_records 
                             WHERE owner_name=? AND pet_name=? LIMIT 1");
$info_stmt->execute([$owner, $pet]);
$info_result = $info_stmt->fetch(PDO::FETCH_ASSOC);

// Count total visits & last visit
$count_stmt = $conn->prepare("SELECT COUNT(*) as total_visits, MAX(created_at) as last_visit 
                              FROM vet_records 
                              WHERE owner_name=? AND pet_name=?");
$count_stmt->execute([$owner, $pet]);
$count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pet) ?>'s Medical History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; padding: 40px; }
h3 { font-weight: 600; color: #065f46; margin-bottom: 25px; }
.card { border-radius: 12px; box-shadow: 0 3px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
.card-body { padding: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.table { border-collapse: separate; border-spacing: 0 8px; width: 100%; }
.table th, .table td { padding: 12px 15px; vertical-align: middle; }
.table th { background-color: #d1fae5; color: #065f46; font-weight: 600; border-bottom: none; }
.table tr { background-color: white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s; }
.table tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.btn-success { background-color: #34d399; color: white; border: none; border-radius: 12px; font-weight: 500; transition: background 0.3s; }
.btn-success:hover { background-color: #10b981; }
.btn-outline-secondary { border: 1px solid #cbd5e1; color: #374151; background-color: white; border-radius: 12px; }
.alert { margin-top: 15px; }
.form-control, .form-select, textarea { border-radius: 12px; padding: 10px 15px; border: 1px solid #cbd5e1; font-size: 0.95rem; }
</style>
</head>
<body>

<div class="container">

    <h3><?= htmlspecialchars($pet) ?>'s Medical History</h3>

    <!-- Summary Card -->
    <div class="card">
        <div class="card-body">
            <div>
                <p><strong>Owner:</strong> <?= htmlspecialchars($owner) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($info_result['address'] ?? '-') ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($info_result['contact_number'] ?? '-') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($info_result['email'] ?? '-') ?></p>
            </div>
            <div>
                <p><strong>Pet:</strong> <?= htmlspecialchars($pet) ?></p>
                <p><strong>Color:</strong> <?= htmlspecialchars($info_result['color'] ?? '-') ?></p>
                <p><strong>Microchip No:</strong> <?= htmlspecialchars($info_result['microchip_no'] ?? '-') ?></p>
            </div>
            <div class="text-end">
                <p><strong>Total Visits:</strong> <?= htmlspecialchars($count_result['total_visits'] ?? 0) ?></p>
                <p><strong>Last Visit:</strong> <?= $count_result['last_visit'] ? date('M d, Y h:i A', strtotime($count_result['last_visit'])) : '-' ?></p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between mb-3">
        <a href="veterinary_records.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRecordModal"><i class="fas fa-plus"></i> Add Record</button>
    </div>

    <!-- Success Alert -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success rounded-3">Record added successfully!</div>
    <?php endif; ?>

    <!-- History Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Species</th>
                <th>Breed</th>
                <th>Age</th>
                <th>Sex</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Remarks</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($records) > 0): ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                        <td><?= htmlspecialchars($row['species']) ?></td>
                        <td><?= htmlspecialchars($row['breed']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['sex']) ?></td>
                        <td><?= htmlspecialchars($row['diagnosis']) ?></td>
                        <td><?= htmlspecialchars($row['treatment']) ?></td>
                        <td><?= htmlspecialchars($row['remarks']) ?></td>
                        <td><?= htmlspecialchars($row['recorded_by']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted">No records found for this pet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Veterinary Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="add_record" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Owner Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($owner) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pet Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pet) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Species</label>
                            <input type="text" name="species" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Breed</label>
                            <input type="text" name="breed" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sex</label>
                            <select name="sex" class="form-select">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment</label>
                            <textarea name="treatment" class="form-control" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Save Record</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

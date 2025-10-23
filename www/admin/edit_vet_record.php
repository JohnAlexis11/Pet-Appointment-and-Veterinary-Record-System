<?php
session_start();

// Restrict access: only admin can edit vet records
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


$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$alertClass = '';

if ($record_id === 0) {
    header("Location: veterinary_records_admin.php");
    exit();
}

// Update record if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = $_POST['owner_name'];
    $pet_name   = $_POST['pet_name'];
    $species    = $_POST['species'];
    $breed      = $_POST['breed'];
    $diagnosis  = $_POST['diagnosis'];
    $treatment  = $_POST['treatment'];
    $remarks    = $_POST['remarks'];

    try {
        $stmt = $conn->prepare("UPDATE vet_records 
            SET owner_name = ?, pet_name = ?, species = ?, breed = ?, diagnosis = ?, treatment = ?, remarks = ?
            WHERE record_id = ?");
        $success = $stmt->execute([$owner_name, $pet_name, $species, $breed, $diagnosis, $treatment, $remarks, $record_id]);

        if ($success) {
            $message = "✅ Record updated successfully.";
            $alertClass = "alert-success";
        } else {
            $message = "❌ Error updating record.";
            $alertClass = "alert-danger";
        }
    } catch (PDOException $e) {
        $message = "❌ Database error: " . $e->getMessage();
        $alertClass = "alert-danger";
    }
}

// Fetch the vet record details
$stmt = $conn->prepare("SELECT * FROM vet_records WHERE record_id = ?");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    header("Location: veterinary_records_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Vet Record</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; padding: 40px; }
.card { border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
.card-header { background: linear-gradient(180deg,#0d9488,#007d8f); color: white; border-top-left-radius: 16px; border-top-right-radius: 16px; }
.card-header h4 { font-weight: 600; font-size: 1.4rem; }
.card-body { padding: 25px; }
.form-label { font-weight: 500; color: #065f46; }
.form-control { border-radius: 10px; }
textarea.form-control { resize: none; }
.btn-success { background: #0d9488; border: none; }
.btn-success:hover { background: #007d8f; }
.btn-secondary { border-radius: 10px; }
.alert { border-radius: 10px; }
</style>
</head>
<body>
<div class="container">
    <div class="card shadow-lg">
        <div class="card-header">
            <h4><i class="fas fa-edit"></i> Edit Veterinary Record</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert <?= $alertClass ?>"> <?= $message ?> </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Owner Name</label>
                    <input type="text" name="owner_name" value="<?= htmlspecialchars($record['owner_name']) ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pet Name</label>
                    <input type="text" name="pet_name" value="<?= htmlspecialchars($record['pet_name']) ?>" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Species</label>
                        <input type="text" name="species" value="<?= htmlspecialchars($record['species']) ?>" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Breed</label>
                        <input type="text" name="breed" value="<?= htmlspecialchars($record['breed']) ?>" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="3" required><?= htmlspecialchars($record['diagnosis']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Treatment</label>
                    <textarea name="treatment" class="form-control" rows="3" required><?= htmlspecialchars($record['treatment']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($record['remarks']) ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="veterinary_records_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();

// Restrict access: only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
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


$owner = $_GET['owner'] ?? '';
$pet   = $_GET['pet'] ?? '';
$updated = '';

// ✅ Handle edit form submission (updates file_records)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['owner_name'], $_POST['pet_name'])) {
    $owner_old = $_POST['owner_name'];
    $pet_old   = $_POST['pet_name'];

    $owner_new = $_POST['owner_name_new'];
    $pet_new   = $_POST['pet_name_new'];
    $address   = $_POST['address'];
    $contact   = $_POST['contact_number'];
    $email     = $_POST['email'];
    $color     = $_POST['color'];
    $microchip = $_POST['microchip_no'];

    $stmt = $conn->prepare("UPDATE file_records 
        SET owner_name = ?, pet_name = ?, address = ?, contact_number = ?, email = ?, color = ?, microchip_no = ? 
        WHERE owner_name = ? AND pet_name = ?");
    $ok = $stmt->execute([$owner_new, $pet_new, $address, $contact, $email, $color, $microchip, $owner_old, $pet_old]);

    if ($ok) {
        $updated = 'success';
        $owner = $owner_new;
        $pet   = $pet_new;
    } else {
        $updated = 'error';
    }
}

// ✅ Handle delete request (from table button)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM vet_records WHERE record_id = ?");
    $ok = $del_stmt->execute([$delete_id]);
    $updated = $ok ? 'deleted' : 'error';
}

// ✅ Fetch summary info
$info_stmt = $conn->prepare("SELECT * FROM file_records WHERE owner_name = ? AND pet_name = ? LIMIT 1");
$info_stmt->execute([$owner, $pet]);
$info_result = $info_stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch all veterinary visits
$stmt = $conn->prepare("SELECT * FROM vet_records WHERE owner_name = ? AND pet_name = ? ORDER BY created_at DESC");
$stmt->execute([$owner, $pet]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Count total visits and last visit
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total_visits, MAX(created_at) AS last_visit 
                              FROM vet_records WHERE owner_name = ? AND pet_name = ?");
$count_stmt->execute([$owner, $pet]);
$count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pet) ?> - Admin History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f0f4f8; padding: 40px; }
h3 { font-weight: 600; color: #065f46; margin-bottom: 25px; }
.card { border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 25px; }
.card-body { padding: 20px 25px; display: flex; justify-content: space-between; flex-wrap: wrap; gap:10px; }
.table { border-collapse: separate; border-spacing: 0 8px; width: 100%; }
.table th, .table td { padding: 12px 15px; vertical-align: middle; }
.table th { background: linear-gradient(180deg,#0d9488,#007d8f); color:white; font-weight:600; border-bottom:none; }
.table tr { background:white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s; }
.table tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.btn-sm { border-radius: 8px; padding: 5px 12px; font-size: 0.85rem; font-weight:500; }
.btn-warning { background-color: #fbbf24; color:white; border:none; }
.btn-warning:hover { background-color: #f59e0b; }
.btn-danger { background-color: #f87171; color:white; border:none; }
.btn-danger:hover { background-color: #ef4444; }
.btn-outline-secondary { border: 1px solid #cbd5e1; color: #374151; background:white; border-radius: 12px; }
.summary p { margin-bottom:5px; font-weight:500; }
.text-end p { margin-bottom:5px; font-weight:500; }
</style>
</head>
<body>

<div class="container">
    <h3><?= htmlspecialchars($pet) ?>'s Veterinary History</h3>

    <?php if ($updated == 'success'): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Info updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif ($updated == 'deleted'): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Record deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php elseif ($updated == 'error'): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> Operation failed.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Summary Card -->
    <div class="card">
        <div class="card-body summary">
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
                <p><strong>Total Visits:</strong> <?= $count_result['total_visits'] ?? 0 ?></p>
                <p><strong>Last Visit:</strong> <?= $count_result['last_visit'] ? date('M d, Y', strtotime($count_result['last_visit'])) : '-' ?></p>
                <button class="btn btn-sm btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editSummaryModal">
                  <i class="fas fa-edit"></i> Edit Info
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-3">
        <a href="veterinary_records_admin.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <!-- History Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Remarks</th>
                    <th>Recorded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['record_id']) ?></td>
                            <td><?= htmlspecialchars($row['species']) ?></td>
                            <td><?= htmlspecialchars($row['breed']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['sex']) ?></td>
                            <td><?= htmlspecialchars($row['diagnosis']) ?></td>
                            <td><?= htmlspecialchars($row['treatment']) ?></td>
                            <td><?= htmlspecialchars($row['remarks']) ?></td>
                            <td><?= htmlspecialchars($row['recorded_by']) ?></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))) ?></td>
                            <td>
                                <a href="edit_vet_record.php?id=<?= $row['record_id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?owner=<?= urlencode($owner) ?>&pet=<?= urlencode($pet) ?>&delete_id=<?= $row['record_id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this record?');">
                                   <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center text-muted">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Summary Modal -->
<div class="modal fade" id="editSummaryModal" tabindex="-1" aria-labelledby="editSummaryLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="editSummaryLabel">Edit Pet & Owner Info</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <input type="hidden" name="owner_name" value="<?= htmlspecialchars($owner) ?>">
          <input type="hidden" name="pet_name" value="<?= htmlspecialchars($pet) ?>">

          <div class="col-md-6">
            <label class="form-label">Owner</label>
            <input type="text" class="form-control" name="owner_name_new" value="<?= htmlspecialchars($owner) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($info_result['email'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($info_result['address'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact</label>
            <input type="text" class="form-control" name="contact_number" value="<?= htmlspecialchars($info_result['contact_number'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Pet</label>
            <input type="text" class="form-control" name="pet_name_new" value="<?= htmlspecialchars($pet) ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Color</label>
            <input type="text" class="form-control" name="color" value="<?= htmlspecialchars($info_result['color'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Microchip No</label>
            <input type="text" class="form-control" name="microchip_no" value="<?= htmlspecialchars($info_result['microchip_no'] ?? '') ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

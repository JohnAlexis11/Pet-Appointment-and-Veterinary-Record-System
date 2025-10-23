<?php
session_start();
// Restrict access: only admin can view this page
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


// Handle delete
if (isset($_GET['delete_id'])) {
    $appointment_id = intval($_GET['delete_id']);
    if ($appointment_id > 0) {
        $stmtDel = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmtDel->execute([$appointment_id]);
        header("Location: admin_appointments_view.php?message=" . urlencode("Appointment deleted successfully"));
        exit();
    }
}

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchTerm = "%$search%";
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$sql = "SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.reason,
            p.pet_name,
            p.species,
            p.breed,
            (o.first_name || ' ' || o.last_name) AS owner_name
        FROM appointments a
        INNER JOIN pets p ON a.pet_id = p.pet_id
        INNER JOIN owners o ON a.owner_id = o.owner_id
        WHERE ((o.first_name || ' ' || o.last_name) LIKE :search OR p.pet_name LIKE :search)";

if ($status_filter === 'completed' || $status_filter === 'pending' || $status_filter === 'paid') {
    $sql .= " AND a.status = :status";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
if ($status_filter === 'completed' || $status_filter === 'pending' || $status_filter === 'paid') {
    $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
}
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Appointments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { display: flex; min-height: 100vh; font-family: 'Poppins', sans-serif; background-color: #f0f4f8; margin: 0;}
.sidebar { width: 260px; background: linear-gradient(180deg, #0d9488, #007d8f); color: white; padding: 25px 20px; display: flex; flex-direction: column;}
.sidebar .profile { text-align: center; margin-bottom: 30px;}
.sidebar .profile img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid white;}
.sidebar .profile h5 { margin-top: 10px; font-weight: 600; font-size: 1.1rem;}
.sidebar .nav-link { color: white; padding: 12px 15px; margin: 8px 0; display: flex; align-items: center; gap: 12px; text-decoration: none; border-radius: 10px; font-weight: 500; transition: background 0.3s;}
.sidebar .nav-link:hover { background-color: rgba(255,255,255,0.2);}
.main-content { flex: 1; display: flex; flex-direction: column;}
.topbar { background: linear-gradient(180deg, #0d9488, #007d8f); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 3px 6px rgba(0,0,0,0.1); border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;}
.topbar h4 { margin: 0; font-weight: 600; font-size: 1.4rem;}
.topbar .btn-light { background-color: white !important; color: #007d8f !important; font-weight: 500; border-radius: 8px;}
.container { padding: 40px 50px;}
.container h3 { font-weight: 600; margin-bottom: 25px; color: #064e3b;}
.form-control, .form-select { border-radius: 12px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 0.95rem;}
.btn-primary { background-color: #0d9488; border: none; border-radius: 12px; padding: 10px 15px; font-weight: 500;}
.btn-primary:hover { background-color: #007d8f;}
.table thead { background-color: #0d9488; color: white; font-weight: 600;}
.table tr { background-color: white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s;}
.table tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08);}
.table td { vertical-align: middle;}
.btn-sm { border-radius: 8px; padding: 5px 12px; font-size: 0.85rem; font-weight: 500;}
.btn-danger { background-color: #f87171; color: white; border: none;}
.btn-danger:hover { background-color: #ef4444;}
.badge { font-size: 0.85rem;}
.badge.bg-success { background-color: #10b981; }
.badge.bg-warning { background-color: #facc15; color: #000; }
.badge.bg-secondary { background-color: #6b7280; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="profile">
        <img src="../petlandia.jpg" alt="Profile">
        <h5>Hello, Admin</h5>
    </div>
    <a href="../admin_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="veterinary_records_admin.php" class="nav-link"><i class="fas fa-notes-medical"></i> Veterinary Records</a>
    <a href="admin_appointments_view.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a>
    <a href="create_account.php" class="nav-link"><i class="fas fa-user-plus"></i> Account Management</a>
</div>

<div class="main-content">
    <div class="topbar">
        <h4>Appointments</h4>
        <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <h3>📋 Booked Appointments</h3>

        <!-- Search + Status Filter Form -->
        <form method="GET" class="d-flex mb-3 gap-2" id="filterForm">
            <input type="text" name="search" class="form-control" placeholder="Search by Owner or Pet Name..." value="<?= htmlspecialchars($search) ?>">
            <select name="status_filter" class="form-select" style="max-width:150px;" onchange="document.getElementById('filterForm').submit();">
                <option value="">All Statuses</option>
                <option value="completed" <?= ($status_filter=='completed')?'selected':'' ?>>Completed</option>
                <option value="pending" <?= ($status_filter=='pending')?'selected':'' ?>>Pending</option>
                <option value="paid" <?= ($status_filter=='paid')?'selected':'' ?>>Paid</option>
            </select>
        </form>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['message']) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Owner Name</th>
                        <th>Pet Name</th>
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Appointment Date & Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['owner_name']) ?></td>
                                <td><?= htmlspecialchars($row['pet_name']) ?></td>
                                <td><?= htmlspecialchars($row['species']) ?></td>
                                <td><?= htmlspecialchars($row['breed']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?><?= $row['appointment_time'] ? ' - ' . date('h:i A', strtotime($row['appointment_time'])) : '' ?></td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($row['status'] == 'completed'): ?>
                                        <span class="badge bg-info text-dark">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?delete_id=<?= $row['appointment_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

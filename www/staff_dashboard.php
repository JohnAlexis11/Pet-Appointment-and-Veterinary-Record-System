<?php
session_start();

// Restrict access: only staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

date_default_timezone_set("Asia/Manila");

// DB connection (SQLite)
try {
    $conn = new PDO('sqlite:' . __DIR__ . '/data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle Actions (Pay / Cancel)
if (isset($_GET['action'], $_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === "pay") {
        $status = "Completed";
    } elseif ($action === "cancel") {
        $status = "Cancelled";
    }

    if (isset($status)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->execute([$status, $appointment_id]);
        header("Location: staff_dashboard.php");
        exit();
    }
}

// Fetch appointments (sorted by recent → old)
$sql = "SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.reason,
            p.pet_name,
            (o.first_name || ' ' || o.last_name) AS owner_name
        FROM appointments a
        INNER JOIN pets p ON a.pet_id = p.pet_id
        INNER JOIN owners o ON p.owner_id = o.owner_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($sql);
$rows = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { display: flex; min-height: 100vh; font-family: 'Poppins', sans-serif; background-color: #f0f4f8; margin: 0; }
        .sidebar { width: 260px; background: linear-gradient(180deg, #34d399, #10b981); color: white; padding: 25px 20px; display: flex; flex-direction: column; }
        .sidebar .profile { text-align: center; margin-bottom: 30px; }
        .sidebar .profile img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid white; }
        .sidebar .profile h5 { margin-top: 10px; font-weight: 600; font-size: 1.1rem; }
        .sidebar .nav-link { color: white; padding: 12px 15px; margin: 8px 0; display: flex; align-items: center; gap: 12px; text-decoration: none; border-radius: 10px; font-weight: 500; transition: background 0.3s; }
        .sidebar .nav-link:hover { background-color: rgba(255,255,255,0.2); }
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .topbar { background-color: #10b981; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 3px 6px rgba(0,0,0,0.1); border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; }
        .topbar h4 { margin: 0; font-weight: 600; font-size: 1.4rem; }
        .topbar .btn-light { background-color: white !important; color: #10b981 !important; font-weight: 500; border-radius: 8px; }
        .container { padding: 40px 50px; }
        .container h3 { font-weight: 600; margin-bottom: 25px; color: #065f46; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 0.95rem; }
        .appointment-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .appointment-table th, .appointment-table td { padding: 14px 18px; text-align: left; font-size: 0.95rem; }
        .appointment-table th { background-color: #d1fae5; color: #065f46; font-weight: 600; border-bottom: none; }
        .appointment-table tr { background-color: white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s; }
        .appointment-table tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .appointment-table td { border: none; color: #374151; vertical-align: middle; }
        .btn-sm { border-radius: 8px; padding: 5px 12px; font-size: 0.85rem; font-weight: 500; }
        .btn-success { background-color: #34d399; color: white; border: none; }
        .btn-success:hover { background-color: #10b981; }
        .btn-danger { background-color: #f87171; color: white; border: none; }
        .btn-danger:hover { background-color: #ef4444; }
        .btn-info { background-color: #3b82f6; color: white; border: none; }
        .btn-info:hover { background-color: #0d9488; }
        .badge.bg-danger { font-weight: 500; font-size: 0.85rem; }
        .truncate { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="petlandia.jpg" alt="Profile">
            <h5>Hello, <?= htmlspecialchars($_SESSION['username']) ?></h5>
        </div>
        <a href="staff_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="staff/veterinary_records.php" class="nav-link"><i class="fas fa-notes-medical"></i> Veterinary Records</a>
        <a href="staff/owner_registration.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
        <a href="staff/calendar_view.php" class="nav-link"><i class="fas fa-calendar"></i> Calendar</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4>Staff Dashboard</h4>
            <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="container">
            <h3>📋 List of Booked Appointments</h3>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Owner or Pet Name" style="max-width: 350px;">
                <select id="sortOrder" class="form-select" style="width: 180px;">
                    <option value="desc">Recent → Oldest</option>
                    <option value="asc">Oldest → Recent</option>
                </select>
            </div>

            <table class="appointment-table" id="appointmentsTable">
                <tr>
                    <th>ID</th>
                    <th>Pet Name</th>
                    <th>Owner Name</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $row['appointment_id'] ?></td>
                            <td><?= htmlspecialchars($row['pet_name']) ?></td>
                            <td><?= htmlspecialchars($row['owner_name']) ?></td>
                            <td><?= date('M d, Y g:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'])) ?></td>
                            <td><span class="truncate" title="<?= htmlspecialchars($row['reason']) ?>"><?= htmlspecialchars($row['reason']) ?></span></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <a href="staff_dashboard.php?action=pay&id=<?= $row['appointment_id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Pay</a>
                                    <a href="staff_dashboard.php?action=cancel&id=<?= $row['appointment_id'] ?>" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</a>
                                <?php elseif ($row['status'] === 'Completed'): ?>
                                    <a href="view_history_staff.php?id=<?= $row['appointment_id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-history"></i> View History</a>
                                <?php elseif ($row['status'] === 'Cancelled'): ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Cancelled</span>
                                <?php else: ?>
                                    <span class="text-muted">No Action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No appointments found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

<script>
    // Search filter
    document.getElementById("searchInput").addEventListener("keyup", function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll("#appointmentsTable tr:not(:first-child)");
        rows.forEach(row => {
            const petName = row.cells[1].textContent.toLowerCase();
            const ownerName = row.cells[2].textContent.toLowerCase();
            row.style.display = petName.includes(filter) || ownerName.includes(filter) ? "" : "none";
        });
    });

    // Sort table by Date & Time
    const table = document.getElementById("appointmentsTable");
    const sortOrder = document.getElementById("sortOrder");

    sortOrder.addEventListener("change", function() {
        const rowsArray = Array.from(table.querySelectorAll("tr:not(:first-child)"));
        const order = this.value;

        rowsArray.sort((a, b) => {
            const dateA = new Date(a.cells[3].textContent);
            const dateB = new Date(b.cells[3].textContent);
            return order === "asc" ? dateA - dateB : dateB - dateA;
        });

        rowsArray.forEach(row => table.appendChild(row));
    });
</script>
</body>
</html>

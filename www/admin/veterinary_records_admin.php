<?php
session_start();

// Restrict access: admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

date_default_timezone_set("Asia/Manila");

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Fetch file records
$sql = "SELECT * FROM file_records ORDER BY created_at DESC";
$stmt = $conn->query($sql);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Veterinary Records (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0d9488, #007d8f);
            color: white;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
        }
        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar .profile img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }
        .sidebar .profile h5 {
            margin-top: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .sidebar .nav-link {
            color: white;
            padding: 12px 15px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.2);
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: linear-gradient(180deg, #0d9488, #007d8f);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }
        .topbar h4 { margin: 0; font-weight: 600; font-size: 1.4rem; }
        .container { padding: 40px 50px; }
        .container h3 {
            font-weight: 600;
            margin-bottom: 25px;
            color: #065f46;
        }
        .appointment-table th {
            background-color: #d1fae5;
            color: #065f46;
        }
        .btn-history {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 14px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-history:hover {
            background-color: #2563eb;
        }
        .btn-danger {
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 14px;
            font-weight: 500;
        }
        .btn-danger:hover {
            background-color: #dc2626;
        }
        .search-box {
            margin-bottom: 20px;
            max-width: 350px;
        }
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
            <h4>Veterinary File Records</h4>
            <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="container">
            <h3>📁 File Records</h3>

            <!-- Auto-search input -->
            <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search by owner name...">

            <table class="table appointment-table" id="recordsTable">
                <tr>
                    <th>Date Created</th>
                    <th>Owner</th>
                    <th>Pet</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))); ?></td>
                        <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['pet_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                        <td>
                            <a href="view_history_admin.php?owner=<?php echo urlencode($row['owner_name']); ?>&pet=<?php echo urlencode($row['pet_name']); ?>" 
                               class="btn btn-history btn-sm">
                                <i class="fas fa-history"></i> View History
                            </a>
                            &nbsp;
                            <a href="delete_file_record.php?id=<?php echo $row['file_id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this file record?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No file records found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto search function
    document.getElementById("searchInput").addEventListener("keyup", function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#recordsTable tr:not(:first-child)");

        rows.forEach(row => {
            let owner = row.cells[1].textContent.toLowerCase();
            row.style.display = owner.includes(filter) ? "" : "none";
        });
    });
</script>
</body>
</html>

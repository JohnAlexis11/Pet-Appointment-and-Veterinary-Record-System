<?php
session_start();

// Restrict access: staff only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

date_default_timezone_set("Asia/Manila");

// ✅ Database connection (SQLite)
// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// ✅ Create file record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_file'])) {
    $owner_name    = $_POST['owner_name'];
    $pet_name      = $_POST['pet_name'];
    $address       = $_POST['address'];
    $contact       = $_POST['contact_number'];
    $email         = $_POST['email'];
    $color         = $_POST['color'];
    $microchip_no  = $_POST['microchip_no'];
    $created_by    = $_SESSION['username'];
    $created_at    = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("
        INSERT INTO file_records 
        (owner_name, pet_name, address, contact_number, email, color, microchip_no, created_by, created_at) 
        VALUES (:owner_name, :pet_name, :address, :contact, :email, :color, :microchip_no, :created_by, :created_at)
    ");

    $stmt->execute([
        ':owner_name'   => $owner_name,
        ':pet_name'     => $pet_name,
        ':address'      => $address,
        ':contact'      => $contact,
        ':email'        => $email,
        ':color'        => $color,
        ':microchip_no' => $microchip_no,
        ':created_by'   => $created_by,
        ':created_at'   => $created_at
    ]);

    header("Location: veterinary_records.php?success=1");
    exit();
}

// ✅ Fetch file records
$result = $conn->query("SELECT * FROM file_records ORDER BY created_at DESC");
$records = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Veterinary Records</title>
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
            background: linear-gradient(180deg, #34d399, #10b981);
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
            background: linear-gradient(180deg, #34d399, #10b981);
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
        .btn-success { background-color: #34d399; border: none; }
        .btn-success:hover { background-color: #10b981; }
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
            background-color: #0d9488;
            color: white;
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
            <h5>Hello, <?= htmlspecialchars($_SESSION['username']) ?></h5>
        </div>
        <a href="../staff_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="veterinary_records.php" class="nav-link"><i class="fas fa-notes-medical"></i> Veterinary Records</a>
        <a href="owner_registration.php" class="nav-link"><i class="fas fa-calendar-plus"></i> Book Appointment</a>
        <a href="calendar_view.php" class="nav-link"><i class="fas fa-calendar"></i> Calendar</a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4>Veterinary File Records</h4>
            <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="container">
            <div class="d-flex justify-content-between mb-3">
                <h3>📁 File Records</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createFileModal">
                    <i class="fas fa-plus"></i> Create File Record
                </button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">File record created successfully!</div>
            <?php endif; ?>

            <!-- Search input -->
            <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search by owner name...">

            <table class="table appointment-table" id="recordsTable">
                <tr>
                    <th>Date Created</th>
                    <th>Owner</th>
                    <th>Pet</th>
                    <th>Created By</th>
                    <th>History</th>
                </tr>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                            <td><?= htmlspecialchars($row['owner_name']) ?></td>
                            <td><?= htmlspecialchars($row['pet_name']) ?></td>
                            <td><?= htmlspecialchars($row['created_by']) ?></td>
                            <td>
                                <a href="pet_history.php?owner=<?= urlencode($row['owner_name']) ?>&pet=<?= urlencode($row['pet_name']) ?>" 
                                   class="btn btn-history btn-sm">
                                    <i class="fas fa-history"></i> View History
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

<!-- Create File Modal -->
<div class="modal fade" id="createFileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Create File Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="create_file" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Name</label>
                            <input type="text" name="owner_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pet Name</label>
                            <input type="text" name="pet_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pet Color</label>
                            <input type="text" name="color" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Microchip No.</label>
                            <input type="text" name="microchip_no" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save File</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
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

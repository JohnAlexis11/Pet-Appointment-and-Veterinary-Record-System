<?php
session_start();
// Restrict access: only admin can view this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Connect to SQLite database
try {
    $conn = new PDO('sqlite:' . __DIR__ . '/data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper to fetch a single integer value
function fetch_count($conn, $sql) {
    $res = $conn->query($sql);
    if ($res && ($row = $res->fetch(PDO::FETCH_NUM))) return (int)$row[0];
    return 0;
}

// Totals
$totalAppointments = fetch_count($conn, "SELECT COUNT(*) FROM appointments");
$totalPets         = fetch_count($conn, "SELECT COUNT(*) FROM pets");
$totalVetRecords   = fetch_count($conn, "SELECT COUNT(*) FROM vet_records");
$totalOwners       = fetch_count($conn, "SELECT COUNT(*) FROM owners");

// Pet gender distribution (SQLite uses COALESCE instead of IFNULL)
$genderCounts = ['Male'=>0, 'Female'=>0, 'Unknown'=>0];
$res = $conn->query("SELECT COALESCE(sex,'Unknown') AS sex, COUNT(*) AS cnt FROM pets GROUP BY COALESCE(sex,'Unknown')");
if ($res) {
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $key = $r['sex'] ?: 'Unknown';
        $genderCounts[$key] = (int)$r['cnt'];
    }
}

// Appointments by status
$statusCounts = [];
$res = $conn->query("SELECT COALESCE(status,'Unknown') AS status, COUNT(*) AS cnt FROM appointments GROUP BY COALESCE(status,'Unknown')");
if ($res) {
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[$r['status']] = (int)$r['cnt'];
    }
}

// Last 6 months appointments (SQLite date syntax)
$months = [];
$monthLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = $m;
    $monthLabels[] = date('M Y', strtotime($m . '-01'));
}
$monthlyCounts = array_fill(0, 6, 0);

$sql = "SELECT strftime('%Y-%m', appointment_date) AS ym, COUNT(*) AS cnt 
        FROM appointments 
        WHERE appointment_date >= date('now','-5 months')
        GROUP BY ym ORDER BY ym";
$res = $conn->query($sql);
if ($res) {
    $map = [];
    while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
        $map[$r['ym']] = (int)$r['cnt'];
    }
    foreach ($months as $i => $m) {
        $monthlyCounts[$i] = $map[$m] ?? 0;
    }
}

// JSON for JS
$genderLabelsJson = json_encode(array_keys($genderCounts));
$genderDataJson   = json_encode(array_values($genderCounts));
$statusLabelsJson = json_encode(array_keys($statusCounts));
$statusDataJson   = json_encode(array_values($statusCounts));
$monthLabelsJson  = json_encode($monthLabels);
$monthlyCountsJson = json_encode($monthlyCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard - PetLandia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { display:flex; min-height:100vh; font-family: 'Poppins', sans-serif; margin:0; background:#f0f4f8; }
.sidebar { width:260px; background: linear-gradient(180deg, #0d9488, #007d8f); color:white; padding:25px 20px; display:flex; flex-direction:column; }
.sidebar .profile { text-align:center; margin-bottom:30px; }
.sidebar .profile img { width:90px; height:90px; border-radius:50%; border:3px solid white; }
.sidebar .profile h5 { margin-top:10px; font-weight:600; font-size:1.1rem; }
.sidebar .nav-link { color:white; padding:12px 15px; margin:8px 0; display:flex; align-items:center; gap:12px; text-decoration:none; border-radius:10px; font-weight:500; transition: background 0.3s; }
.sidebar .nav-link:hover { background-color: rgba(255,255,255,0.2); }
.main-content { flex:1; display:flex; flex-direction:column; }
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
.topbar .btn-light { background:white !important; color:#007d8f !important; font-weight:500; border-radius:8px; }
.container { padding:40px 50px; }
.container h3 { font-weight:600; margin-bottom:25px; color:#065f46; }
.row.g-3.mb-4 .card { border-radius:16px; padding:20px; background:white; box-shadow:0 3px 6px rgba(0,0,0,0.05); }
.card .stat-icon { font-size:1.4rem; opacity:0.85; margin-right:12px; }
.chart-card { background:white; border-radius:16px; padding:15px; height:320px; }
.chart-card canvas { height:100% !important; }
</style>
</head>
<body>
<div class="sidebar">
    <div class="profile">
        <img src="petlandia.jpg" alt="Admin">
        <h5>Hello, Admin</h5>
    </div>
    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a class="nav-link" href="admin/veterinary_records_admin.php"><i class="fas fa-notes-medical"></i> Veterinary Records</a>
    <a class="nav-link" href="admin/admin_appointments_view.php"><i class="fas fa-calendar-alt"></i> Appointments</a>
    <a class="nav-link" href="admin/create_account.php"><i class="fas fa-user-plus"></i> Account Management</a>
</div>

<div class="main-content">
    <div class="topbar">
        <h4>Admin Dashboard</h4>
        <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <h3>📊 Dashboard Overview</h3>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-check stat-icon text-primary"></i>
                        <div>
                            <small class="text-muted">Total Appointments</small>
                            <div class="h5 mb-0"><?= htmlspecialchars($totalAppointments) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-paw stat-icon text-warning"></i>
                        <div>
                            <small class="text-muted">Total Pets</small>
                            <div class="h5 mb-0"><?= htmlspecialchars($totalPets) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-users stat-icon text-success"></i>
                        <div>
                            <small class="text-muted">Total Owners</small>
                            <div class="h5 mb-0"><?= htmlspecialchars($totalOwners) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-notes-medical stat-icon text-danger"></i>
                        <div>
                            <small class="text-muted">Vet Records</small>
                            <div class="h5 mb-0"><?= htmlspecialchars($totalVetRecords) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="chart-card">
                    <strong>Pet Gender Distribution</strong>
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <strong>Appointments by Status</strong>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <strong>Appointments (Last 6 Months)</strong>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const genderLabels = <?= $genderLabelsJson ?>;
const genderData = <?= $genderDataJson ?>;
const statusLabels = <?= $statusLabelsJson ?>;
const statusData = <?= $statusDataJson ?>;
const monthLabels = <?= $monthLabelsJson ?>;
const monthlyCounts = <?= $monthlyCountsJson ?>;

new Chart(document.getElementById('genderChart'), {
    type: 'doughnut',
    data: { labels: genderLabels, datasets: [{ data: genderData, backgroundColor: ['#0ea5e9','#f59e0b','#6b7280'], borderWidth: 1 }] },
    options: { plugins: { legend: { position: 'bottom' } }, maintainAspectRatio: false }
});

new Chart(document.getElementById('statusChart'), {
    type: 'bar',
    data: { labels: statusLabels, datasets: [{ label: 'Appointments', data: statusData, backgroundColor: '#10b981' }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } }, maintainAspectRatio: false }
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: { labels: monthLabels, datasets: [{ label: 'Appointments', data: monthlyCounts, fill: true, tension: 0.35, backgroundColor: 'rgba(14,165,233,0.12)', borderColor: '#0ea5e9', pointRadius: 4 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } }, maintainAspectRatio: false }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

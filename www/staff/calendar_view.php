<?php
session_start();
// Restrict access: only staff can view this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
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


// Handle selected month & year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Query appointments per date + slot
$appointments = [];
$sql = "SELECT 
            appointment_date AS appt_date,
            CASE 
                WHEN appointment_time BETWEEN '08:00:00' AND '12:00:00' THEN 'morning'
                WHEN appointment_time BETWEEN '13:00:00' AND '17:00:00' THEN 'afternoon'
                ELSE 'other'
            END AS slot,
            COUNT(*) AS total
        FROM appointments
        GROUP BY appointment_date, slot";

$result = $conn->query($sql);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $appointments[$row['appt_date']][$row['slot']] = $row['total'];
}

// Calendar setup
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$startDay = date('w', $firstDayOfMonth); // 0 = Sunday
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointment Calendar</title>
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
    background-color: #10b981;
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
.topbar .btn-light {
    background-color: white !important;
    color: #10b981 !important;
    font-weight: 500;
    border-radius: 8px;
}
.container {
    padding: 40px 50px;
}
.card-calendar {
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    padding: 25px;
    max-width: 900px;
    margin: auto;
}
.calendar-controls {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    margin-bottom: 20px;
}
.calendar-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.calendar-table th {
    background-color: #10b981;
    color: white;
    padding: 10px;
    font-weight: 500;
    border-radius: 8px;
}
.calendar-table td {
    width: 14.28%;
    height: 100px;
    vertical-align: top;
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
}
.calendar-table td.today {
    background-color: #d1fae5;
}
.slot-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.85rem;
    margin-top: 4px;
}
.slot-badge.full {
    background: #ef4444;
    color: white;
}
.slot-badge.partial {
    background: #34d399;
    color: white;
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
        <h4>📅 Appointment Calendar</h4>
        <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <div class="card-calendar">
            <form method="GET" class="calendar-controls">
                <select name="month" class="form-select" style="width:auto;">
                    <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= date("F", mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-select" style="width:auto;">
                    <?php for ($y=date('Y')-3;$y<=date('Y')+3;$y++): ?>
                        <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-success">Go</button>
            </form>

            <table class="calendar-table">
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th>
                    <th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
                <tr>
                <?php
                for ($i = 0; $i < $startDay; $i++) echo "<td></td>";
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $currentDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    if (($day + $startDay - 1) % 7 == 0 && $day != 1) echo "</tr><tr>";
                    $todayClass = $currentDate == date('Y-m-d') ? 'today' : '';
                    echo "<td class='$todayClass'><strong>$day</strong><br>";

                    if (isset($appointments[$currentDate])) {
                        $morning = $appointments[$currentDate]['morning'] ?? 0;
                        $afternoon = $appointments[$currentDate]['afternoon'] ?? 0;

                        echo "<span class='slot-badge " . ($morning >= 10 ? 'full' : 'partial') . "'>AM: $morning / 10</span><br>";
                        echo "<span class='slot-badge " . ($afternoon >= 10 ? 'full' : 'partial') . "'>PM: $afternoon / 10</span>";
                    }
                    echo "</td>";
                }
                ?>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>

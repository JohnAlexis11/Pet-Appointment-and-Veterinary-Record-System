<?php
session_start();

// DB connection (SQLite)
try {
    // Go up one folder from "admin/" to reach "data/"
    $conn = new PDO('sqlite:' . __DIR__ . '/../data/data.sqlite');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// --- Fetch fully booked slots (>= 10 per slot) ---
$fullSlots = [];
$sql = "
    SELECT 
        DATE(appointment_date) AS appt_date,
        CASE 
            WHEN strftime('%H:%M:%S', appointment_date) BETWEEN '08:00:00' AND '12:00:00' THEN 'morning'
            WHEN strftime('%H:%M:%S', appointment_date) BETWEEN '13:00:00' AND '17:00:00' THEN 'afternoon'
        END AS slot,
        COUNT(*) AS total
    FROM appointments
    GROUP BY DATE(appointment_date), slot
    HAVING total >= 10
";
$res = $conn->query($sql);
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $fullSlots[$row['appt_date']][] = $row['slot'];
}

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = $_POST['first_name'] ?? '';
    $last_name  = $_POST['last_name'] ?? '';
    $contact    = $_POST['contact_number'] ?? '';
    $email      = $_POST['email'] ?? '';
    $address    = $_POST['address'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';

    $full_datetime = $appointment_date . " " . $appointment_time;

   try {
    $stmt = $conn->prepare("
        INSERT INTO owners (first_name, last_name, contact_number, email, address)
        VALUES (:first_name, :last_name, :contact_number, :email, :address)
    ");
    $stmt->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':contact_number' => $contact,
        ':email' => $email,
        ':address' => $address
    ]);

    $owner_id = $conn->lastInsertId();

    // ✅ Debug check — remove after testing
    if (!$owner_id) {
        die("Insert failed or no owner_id returned. Check if 'owner_id' is INTEGER PRIMARY KEY AUTOINCREMENT.");
    }

    header("Location: pet_registration.php?owner_id=$owner_id&appointment_date=$appointment_date&appointment_time=$appointment_time");
    exit();
} catch (PDOException $e) {
    die("Error saving owner: " . htmlspecialchars($e->getMessage()));
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Registration - PetLandia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.card {
    width: 100%;
    max-width: 900px;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}
.card-header {
    background: linear-gradient(90deg, #34d399, #10b981);
    color: white;
    font-weight: 600;
    font-size: 1.3rem;
    text-align: center;
    padding: 20px 0;
}
.card-body {
    padding: 30px 40px;
}
.form-label {
    font-weight: 500;
}
.form-control, .form-select, textarea {
    border-radius: 10px;
    box-shadow: none;
    border: 1px solid #d1d5db;
    padding: 12px 15px;
}
textarea.form-control {
    resize: vertical;
}
.btn-primary, .btn-secondary {
    border-radius: 10px;
    font-weight: 500;
    padding: 10px 20px;
    transition: 0.3s;
}
.btn-primary {
    background-color: #34d399;
    color: white;
    border: none;
}
.btn-primary:hover {
    background-color: #10b981;
}
.btn-secondary {
    background-color: #d1d5db;
    color: #374151;
    border: none;
    margin-top: 10px;
}
.btn-secondary:hover {
    background-color: #9ca3af;
    color: white;
}
@media (max-width: 768px) {
    .card-body { padding: 20px; }
}
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <i class="fas fa-user-plus me-2"></i> Owner Registration
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" name="contact_number" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Date</label>
                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Time</label>
                    <select name="appointment_time" id="appointment_time" class="form-select" required>
                        <option value="08:00:00">Morning (8AM - 12NN)</option>
                        <option value="13:00:00">Afternoon (1PM - 5PM)</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-arrow-right me-2"></i> Next: Register Pet
                </button>
                <a href="../staff_dashboard.php" class="btn btn-secondary flex-grow-1">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const dateInput = document.getElementById("appointment_date");
const today = new Date().toISOString().split("T")[0];
dateInput.setAttribute("min", today);

const fullSlots = <?= json_encode($fullSlots) ?>;
const timeSelect = document.getElementById("appointment_time");

dateInput.addEventListener("change", function() {
    const selected = this.value;
    Array.from(timeSelect.options).forEach(opt => {
        const slot = opt.value === "08:00:00" ? "morning" : "afternoon";
        if (fullSlots[selected] && fullSlots[selected].includes(slot)) {
            opt.disabled = true;
            opt.textContent = (slot === "morning" ? "Morning (Fully Booked)" : "Afternoon (Fully Booked)");
        } else {
            opt.disabled = false;
            opt.textContent = (slot === "morning" ? "Morning (8AM - 12NN)" : "Afternoon (1PM - 5PM)");
        }
    });
});
</script>

</body>
</html>

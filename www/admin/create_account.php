<?php
session_start();

// Restrict access: only admin
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


$message = '';
$alertClass = '';

// Delete account
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmtDel = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmtDel->execute([$id]);
    $message = "✅ Account deleted successfully!";
    $alertClass = "alert-success";
}

// Create account
if (isset($_POST['create_account'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $check->execute([$username]);
    $exists = $check->fetchColumn();

    if ($exists > 0) {
        $message = "❌ Username already exists!";
        $alertClass = "alert-danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password, $role])) {
            $message = "✅ Account created successfully!";
            $alertClass = "alert-success";
        } else {
            $message = "❌ Error creating account!";
            $alertClass = "alert-danger";
        }
    }
}

// Edit account
if (isset($_POST['edit_account_id'])) {
    $id = intval($_POST['edit_account_id']);
    $new_username = trim($_POST['edit_username']);
    $new_password = $_POST['edit_password'];

    $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
    $check->execute([$new_username, $id]);
    $exists = $check->fetchColumn();

    if ($exists > 0) {
        $message = "❌ Username already taken!";
        $alertClass = "alert-danger";
    } else {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE user_id = ?");
            $ok = $stmt->execute([$new_username, $hashed, $id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
            $ok = $stmt->execute([$new_username, $id]);
        }

        if ($ok) {
            $message = "✅ Account updated successfully!";
            $alertClass = "alert-success";
        } else {
            $message = "❌ Error updating account!";
            $alertClass = "alert-danger";
        }
    }
}

// Fetch users
$result = $conn->query("SELECT user_id, username, role FROM users ORDER BY role, username");
$users = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { display: flex; min-height: 100vh; font-family: 'Poppins', sans-serif; background-color: #f0f4f8; margin: 0;}
.sidebar { width: 260px; background: linear-gradient(180deg,#0d9488,#007d8f); color: white; padding: 25px 20px; display: flex; flex-direction: column;}
.sidebar .profile { text-align: center; margin-bottom: 30px;}
.sidebar .profile img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid white;}
.sidebar .profile h5 { margin-top: 10px; font-weight: 600; font-size: 1.1rem;}
.sidebar .nav-link { color: white; padding: 12px 15px; margin: 8px 0; display: flex; align-items: center; gap: 12px; text-decoration: none; border-radius: 10px; font-weight: 500; transition: background 0.3s;}
.sidebar .nav-link:hover { background-color: rgba(255,255,255,0.2);}
.main-content { flex: 1; display: flex; flex-direction: column;}
.topbar { background: linear-gradient(180deg,#0d9488,#007d8f); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 3px 6px rgba(0,0,0,0.1); border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;}
.topbar h4 { margin: 0; font-weight: 600; font-size: 1.4rem;}
.topbar .btn-light { background: white !important; color: #007d8f !important; font-weight: 500; border-radius: 8px;}
.container-box { padding: 40px 50px;}
.table thead { background: #0d9488; color: white; font-weight: 600;}
.table tr { background: white; border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s;}
.table tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08);}
.table td { vertical-align: middle;}
.btn-sm { border-radius: 8px; padding: 5px 12px; font-size: 0.85rem; font-weight: 500;}
.btn-danger { background: #f87171; color: white; border: none;}
.btn-danger:hover { background: #ef4444;}
.btn-warning { background: #fbbf24; color: #000; border: none;}
.btn-warning:hover { background: #f59e0b;}
.btn-primary { background: #0d9488; border: none; border-radius: 10px; font-weight: 500;}
.btn-primary:hover { background: #007d8f;}
.badge { font-size: 0.85rem;}
.badge.bg-admin { background: #0d9488;}
.badge.bg-staff { background: #f59e0b;}
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
    <a href="create_account.php" class="nav-link"><i class="fas fa-user-cog"></i> Account Management</a>
</div>

<div class="main-content">
    <div class="topbar">
        <h4>Account Management</h4>
        <a href="../logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container-box">
        <?php if($message): ?>
            <div class="alert <?= $alertClass ?>"><?= $message ?></div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createAccountModal">
            <i class="fas fa-user-plus"></i> Create New Account
        </button>

        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $row): ?>
                        <tr>
                            <td><?= $row['user_id'] ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <span class="badge <?= $row['role']=='admin'?'bg-admin':'bg-staff' ?>">
                                    <?= ucfirst($row['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['username'] != $_SESSION['username']): ?>
                                    <a href="?delete_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this account?')"><i class="fas fa-trash"></i></a>
                                    <a href="javascript:void(0)" onclick="openEditModal(<?= $row['user_id'] ?>,'<?= htmlspecialchars($row['username']) ?>')" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <?php else: ?>
                                    <span class="text-muted">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Account Modal -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="create_account" value="1">
        <div class="modal-header">
          <h5 class="modal-title">Create New Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-control" required>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Edit Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_account_id" id="edit_account_id">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="edit_username" id="edit_username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" name="edit_password" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditModal(id, username){
    document.getElementById('edit_account_id').value = id;
    document.getElementById('edit_username').value = username;
    var myModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    myModal.show();
}
</script>
</body>
</html>

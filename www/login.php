<?php
session_start();

// Prevent caching of login page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// If already logged in, redirect to the correct dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: staff_dashboard.php");
        exit();
    }
}

// Error message handler
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid_password') {
        $error = "❌ Invalid password!";
    } elseif ($_GET['error'] === 'user_not_found') {
        $error = "❌ User not found!";
    } elseif ($_GET['error'] === 'invalid_role') {
        $error = "❌ Unauthorized role!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - PetLandia</title>
    <style>
       body {
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('bg7.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* dark overlay */
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.3); /* semi-transparent white */
            padding: 40px 50px;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.3); /* subtle shadow */
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #000;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            color: #000;
            font-weight: 500;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #a39a9aff;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            transition: 0.3s;
        }

          /* Login button */
        .login-container input[type="submit"] {
            width: 108%;
            padding: 13px;
            background-color: #1471afff;
            color: white;
            border: none;
            border-radius: 4px; /* reduced more */
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
            
        }

        .login-container input[type="submit"]:hover {
            background-color: #1e40af;
            transform: scale(1.01);
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="petlandia.jpg" alt="PetLandia Veterinary Clinic" style="display:block; margin:0 auto 25px auto; max-width:150px;">

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <label>Username</label>
            <input type="text" name="username" placeholder="" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="" required>

            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>

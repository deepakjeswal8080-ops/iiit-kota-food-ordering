<?php
include 'db.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $secret_code = $_POST['secret_code'];

    // Admin banne ke liye secret code check karega
    if ($role == 'admin' && $secret_code !== 'ADMIN123') {
        $msg = "Invalid Admin Secret Code! Use ADMIN123 to register as Admin.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "This email is already registered!";
        } else {
            $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
            if (mysqli_query($conn, $sql)) {
                header("Location: index.php");
                exit();
            } else {
                $msg = "Registration failed. Please try again!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Food Ordering System</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleSecretBox() {
            var role = document.getElementById("roleSelect").value;
            var secretDiv = document.getElementById("secretDiv");
            secretDiv.style.display = (role === "admin") ? "block" : "none";
        }
    </script>
</head>
<body>
    <div style="max-width:420px; margin:60px auto; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
        <h2>Create Account</h2>
        <?php if($msg) echo "<p style='color:red; font-weight:bold;'>$msg</p>"; ?>
        <form method="POST">
            <label>Register As:</label><br>
            <select name="role" id="roleSelect" onchange="toggleSecretBox()">
                <option value="customer">Customer (Order Food)</option>
                <option value="admin">Admin (Manage Restaurant)</option>
            </select><br>

            <div id="secretDiv" style="display:none;">
                <label>Admin Secret Code:</label><br>
                <input type="text" name="secret_code" placeholder="Enter Admin Secret Code"><br>
            </div>

            <label>Full Name:</label><br>
            <input type="text" name="name" placeholder="Enter your full name" required><br>

            <label>Email Address:</label><br>
            <input type="email" name="email" placeholder="Enter your email" required><br>

            <label>Password:</label><br>
            <input type="password" name="password" placeholder="Create a password" required><br>

            <button type="submit" style="width:100%; padding:10px; background:#007bff; color:#fff; border:none; cursor:pointer;">Register</button>
        </form>
        <p style="margin-top:15px;">Already have an account? <a href="index.php">Login here</a></p>
    </div>
</body>
</html>
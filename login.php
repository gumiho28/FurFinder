<?php
include 'db.php';

// HANDLE REGISTER
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // Default role

    // Check if creating the specific admin account
    if($email === 'admin@furfinder.com') { $role = 'admin'; }

    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$pass', '$role')";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration Successful! Please Login.');</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// HANDLE LOGIN
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
        } else {
            echo "<script>alert('Invalid Password');</script>";
        }
    } else {
        echo "<script>alert('User not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | FurFinder</title>
    <style>
        body { font-family: 'Open Sans', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #003366; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #d4af37; color: #003366; }
        .toggle { margin-top: 15px; font-size: 0.9rem; color: #003366; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container" id="login-box">
        <h2>Login</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p class="toggle" onclick="toggleForm()">No account? Register here.</p>
    </div>

    <div class="container" id="register-box" style="display:none;">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Sign Up</button>
        </form>
        <p class="toggle" onclick="toggleForm()">Have an account? Login here.</p>
    </div>

    <script>
        function toggleForm() {
            const login = document.getElementById('login-box');
            const reg = document.getElementById('register-box');
            if (login.style.display === 'none') {
                login.style.display = 'block';
                reg.style.display = 'none';
            } else {
                login.style.display = 'none';
                reg.style.display = 'block';
            }
        }
    </script>
</body>
</html>
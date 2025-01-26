<?php
// Configuration vulnérable à dessein pour le CTF
$db_host = 'localhost';
$db_user = 'webapp';
$db_pass = 'insecure_pass';
$db_name = 'webapp';

// Page de connexion vulnérable à l'injection SQL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Vulnérable à l'injection SQL intentionnellement
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Login System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .login-form { max-width: 300px; margin: 0 auto; }
        input { width: 100%; padding: 8px; margin: 8px 0; }
        button { width: 100%; padding: 8px; background: #4CAF50; color: white; border: none; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <!-- Debug comment: admin:admin123 -->
    </div>
</body>
</html>

<?php
require_once 'session.php';
requireLogin(); // locks page to logged-in users
checkTimeout(); // optional, for session expiry



// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration and establish connection
require_once 'db_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
require_once 'session.php';
checkAuth(); // only logged-in users can access

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $username, $hashed);

        if ($stmt->execute()) {
            $message = "User added successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
    } else {
        $message = "Fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User - Nettrack</title>
    <style>
    body { font-family: Arial, sans-serif; background:#f5f7fa; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    .container { background:#fff; padding:40px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); width:100%; max-width:400px; }
    h2 { text-align:center; color:#4361ee; margin-bottom:30px; }
    .form-group { margin-bottom:20px; }
    label { display:block; margin-bottom:8px; color:#666; }
    input { width:100%; padding:12px; border:1px solid #ddd; border-radius:5px; font-size:16px; }
    input:focus { outline:none; border-color:#4361ee; }
    .btn { background:#4361ee; color:#fff; border:none; padding:12px; width:100%; border-radius:5px; cursor:pointer; font-size:16px; transition:background 0.3s; }
    .btn:hover { background:#3f37c9; }
    .message { text-align:center; margin-bottom:20px; color:#f72585; }
    </style>
</head>
<body>
<div class="container">
    <h2>Add User</h2>
    <?php if($message) echo "<div class='message'>$message</div>"; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="text" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Add User</button>
    </form>
</div>
</body>
</html>

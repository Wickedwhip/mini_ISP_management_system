<?php
session_start();

// Database connection (adjust credentials as needed)
$host = 'localhost';
$user = 'your_username';
$pass = 'your_password';
$db = 'nettrack_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect to login if not authenticated
function checkAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to authenticate user
function authenticate($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }
    }
    return false;
}

// Function to logout
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'mini_isp_management_system';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Force login for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Prevent logged-in users from accessing login page
function preventLoginLoop() {
    if (isLoggedIn()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Authenticate user
function authenticate($username, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

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

// Logout user
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Optional: session timeout (e.g., 30 min)
function checkTimeout() {
    $timeout_duration = 1800; // 30 minutes
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        logout();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>

<?php
require_once 'session.php';

// New password
$newPass = password_hash("9501", PASSWORD_DEFAULT);

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$username = "joe";
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Update existing user
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $newPass, $username);
    $stmt->execute();
    echo "Password for 'joe' reset successfully!";
} else {
    // Create new user
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $newPass);
    $stmt->execute();
    echo "User 'joe' created successfully!";
}
?>

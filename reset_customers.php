<?php
require_once 'session.php';
requireLogin(); // only logged-in users can run this

// Delete payments first (child table)
$delPayments = $conn->query("DELETE FROM payments");
if (!$delPayments) {
    die("❌ Failed to delete payments: " . $conn->error);
}

// Delete customers (parent table)
$delCustomers = $conn->query("DELETE FROM customers");
if (!$delCustomers) {
    die("❌ Failed to delete customers: " . $conn->error);
}

echo "✅ All customer-related data cleared successfully!";
?>

<?php
require_once 'session.php';
requireLogin(); // locks page to logged-in users

// Fetch last Internet Bill payment (customer_id = 0)
$last_payment_result = $conn->query("SELECT MAX(date_paid) AS last_paid FROM payments WHERE customer_id = 0");
$last_paid = $last_payment_result->fetch_assoc()['last_paid'];

// ... rest of code
// Calculate next due date (30 days after last paid)
$next_due = $last_paid ? date('Y-m-d', strtotime($last_paid . ' +30 days')) : null;
$today = date('Y-m-d');

// Check status
$status = '';
if ($next_due) {
    if ($today > $next_due) {
        $status = "❌ Bill overdue! Last paid on $last_paid. Next payment was due on $next_due.";
    } else {
        $status = "✅ Bill is paid. Next due on $next_due.";
    }
} else {
    $status = "⚠️ No internet bill payment recorded yet.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Internet Bill Reminder - Nettrack</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .alert { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin-top: 20px; font-size: 16px; }
        .overdue { color: red; font-weight: bold; }
        .ok { color: green; font-weight: bold; }
        .warn { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Internet Bill Reminder - Nettrack</h2>
    <div class="alert <?= strpos($status,'overdue')!==false?'overdue':(strpos($status,'⚠️')!==false?'warn':'ok') ?>">
        <?= $status ?>
    </div>
</body>
</html>

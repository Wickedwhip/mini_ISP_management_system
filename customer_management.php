<?php
require_once 'session.php';
requireLogin();
checkTimeout();

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Handle customer deletion
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = "✅ Customer deleted successfully.";
    } else {
        $msg = "❌ Error deleting customer: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all customers
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");

// Helper: fetch last payment date (read-only, kept for display)
function getLastPayment($conn, $customer_id){
    $stmt = $conn->prepare("SELECT MAX(date_paid) AS last_paid FROM payments WHERE customer_id=? AND bill_type='internet'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res['last_paid'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Management - Nettrack</title>
    <style>
        body { font-family: Arial; padding:20px; background:#f4f6f9; }
        table { width:100%; border-collapse: collapse; background:#fff; margin-top:20px; }
        th, td { padding:10px; border:1px solid #ccc; text-align:left; }
        th { background:#007BFF; color:#fff; }
        a.delete { color:red; text-decoration:none; }
        a.delete:hover { text-decoration:underline; }
        .msg { margin-bottom:15px; font-weight:bold; }
        .paid { color:green; font-weight:bold; }
        .unpaid { color:red; font-weight:bold; }
    </style>
</head>
<body>
    <div style="margin-bottom:20px;">
        <a href="dashboard.php" style="text-decoration:none; background:#007BFF; color:#fff; padding:10px 15px; border-radius:5px;">⬅ Back</a>
    </div>

    <h2 style="margin-bottom:15px;">Customer Management - Nettrack</h2>
    <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <!-- Customer Table -->
    <table style="box-shadow:0 4px 8px rgba(0,0,0,0.1);">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Location</th>
            <th>Package</th>
            <th>Install Fee</th>
            <th>Router Cost</th>
            <th>Ethernet Cost</th>
            <th>Start Date</th>
            <th>Status</th>
            <th>Last Payment</th>
            <th>Next Due</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <?php
                $last_paid = getLastPayment($conn, $row['id']);
                $next_due = $last_paid ? date('Y-m-d', strtotime($last_paid.' +30 days')) : 'N/A';
                $status_class = strtolower($row['status']) == 'active' ? 'active' : (strtotime($next_due) < time() ? 'overdue' : 'warn');
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['package']) ?></td>
                <td><?= number_format($row['installation_fee'],2) ?></td>
                <td><?= number_format($row['router_cost'],2) ?></td>
                <td><?= number_format($row['ethernet_cost'],2) ?></td>
                <td><?= $row['start_date'] ?></td>
                <td style="font-weight:bold; color:<?= $status_class=='active'?'green':($status_class=='overdue'?'red':'orange') ?>;">
                    <?= ucfirst($row['status']) ?>
                </td>
                <td><?= $last_paid ?? '-' ?></td>
                <td><?= $next_due ?></td>
                <td><a class="delete" href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this customer?')" style="color:red;">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="13" style="text-align:center;">No customers found.</td></tr>
        <?php endif; ?>
    </table>
</body>

</html>

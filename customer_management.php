<?php
require_once 'session.php';
requireLogin();
checkTimeout();

// Delete customer if ID is passed
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Customers</title>
    <style>
        body { font-family: Arial; padding:20px; background:#f4f6f9; }
        table { width:100%; border-collapse: collapse; background:#fff; }
        th, td { padding:10px; border:1px solid #ccc; text-align:left; }
        th { background:#007BFF; color:#fff; }
        a.delete { color:red; text-decoration:none; }
        a.delete:hover { text-decoration:underline; }
        .msg { margin-bottom:15px; font-weight:bold; }
    </style>
</head>
<body>
    <h2>Customer Management</h2>
    <?php if (isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Package</th>
            <th>Install Fee</th>
            <th>Router Cost</th>
            <th>Ethernet Cost</th>
            <th>Start Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= htmlspecialchars($row['package']) ?></td>
                <td><?= number_format($row['installation_fee'],2) ?></td>
                <td><?= number_format($row['router_cost'],2) ?></td>
                <td><?= number_format($row['ethernet_cost'],2) ?></td>
                <td><?= $row['start_date'] ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><a class="delete" href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this customer?')">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11" style="text-align:center;">No customers found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>

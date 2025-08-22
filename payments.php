<?php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

$msg = '';
if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Handle delete
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = "✅ Payment #$del_id deleted.";
    } else {
        $msg = "❌ Delete failed: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all customers with their latest payment (if any)
$sql = "SELECT c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone,
               COALESCE(p.bill_type,'-') AS bill_type,
               COALESCE(p.amount,'-') AS amount,
               COALESCE(p.date_paid,'-') AS date_paid,
               COALESCE(p.method,'-') AS method,
               COALESCE(p.remarks,'-') AS remarks,
               p.id AS payment_id
        FROM customers c
        LEFT JOIN (
            SELECT * FROM payments p1
            WHERE p1.id = (
                SELECT p2.id 
                FROM payments p2 
                WHERE p2.customer_id = p1.customer_id
                ORDER BY p2.date_paid DESC, p2.id DESC
                LIMIT 1
            )
        ) p ON c.id = p.customer_id
        ORDER BY c.name ASC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payments - Nettrack</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .top { margin-bottom: 15px; }
        a.btn { display: inline-block; padding: 8px 12px; background: #007BFF; color: #fff; border-radius: 6px; text-decoration: none; margin-right: 8px; }
        a.btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 4px 8px rgba(0,0,0,.06); }
        th, td { padding: 10px; border: 1px solid #e6e6e6; text-align: left; }
        th { background: #007BFF; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }
        .msg { margin: 12px 0; font-weight: 700; }
        .del { color: red; text-decoration: none; }
        td.amount, th.amount { text-align: right; }
    </style>
</head>
<body>
    <div class="top">
        <a href="dashboard.php" class="btn">⬅ Back</a>
        <a href="add_payment.php" class="btn">+ Add Payment</a>
    </div>

    <h2>Payments</h2>
    <?php if($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Customer No.</th>
            <th>Bill Type</th>
            <th class="amount">Amount</th>
            <th>Date Paid</th>
            <th>Method</th>
            <th>Remarks</th>
            <th>Action</th>
        </tr>
        <?php if ($res && $res->num_rows > 0): ?>
            <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['payment_id'] ?: '-' ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['customer_phone']) ?></td>
                <td><?= htmlspecialchars($row['bill_type']) ?></td>
                <td class="amount"><?= is_numeric($row['amount']) ? number_format($row['amount'],2) : '-' ?></td>
                <td><?= htmlspecialchars($row['date_paid']) ?></td>
                <td><?= htmlspecialchars($row['method']) ?></td>
                <td><?= htmlspecialchars($row['remarks']) ?></td>
                <td>
                    <?php if($row['payment_id']): ?>
                        <a href="edit_payment.php?id=<?= (int)$row['payment_id'] ?>">Edit</a> |
                        <a class="del" href="delete_payment.php?id=<?= (int)$row['payment_id'] ?>" onclick="return confirm('Delete this payment?');">Delete</a>
                    <?php else: ?>
                        <a href="add_payment.php?customer_id=<?= (int)$row['customer_id'] ?>">Add Payment</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center">No customers found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>

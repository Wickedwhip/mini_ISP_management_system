<?php
include 'db_connect.php';
require_once 'session.php';
checkAuth(); // Ensure user is logged in

// Fetch customers for filter dropdown
$customers = $conn->query("SELECT id, name FROM customers");

// Filter variables
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Build query
$sql = "SELECT p.*, c.name AS customer_name FROM payments p 
        JOIN customers c ON p.customer_id = c.id 
        WHERE 1=1";

if ($customer_id != '') $sql .= " AND p.customer_id = '$customer_id'";
if ($from_date != '') $sql .= " AND p.date_paid >= '$from_date'";
if ($to_date != '') $sql .= " AND p.date_paid <= '$to_date'";

$sql .= " ORDER BY p.date_paid DESC";

$result = $conn->query($sql);

// Calculate total
$total = 0;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total += $row['amount'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payments Overview - Nettrack</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        select, input[type=date], button { padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ccc; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background: #28a745; color: white; }
        tr:hover { background: #f1f1f1; }
        .total { font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
    <h2>Payments Overview - Nettrack</h2>
    <form method="GET">
        <select name="customer_id">
            <option value="">-- All Customers --</option>
            <?php while($c = $customers->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id']==$customer_id?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
        </select>

        From: <input type="date" name="from_date" value="<?= $from_date ?>">
        To: <input type="date" name="to_date" value="<?= $to_date ?>">
        <button type="submit">Filter</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Amount (KES)</th>
            <th>Date Paid</th>
            <th>Method</th>
            <th>Remarks</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php foreach($conn->query($sql) as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= number_format($row['amount'],2) ?></td>
                    <td><?= $row['date_paid'] ?></td>
                    <td><?= htmlspecialchars($row['method']) ?></td>
                    <td><?= htmlspecialchars($row['remarks']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No payments found.</td></tr>
        <?php endif; ?>
    </table>

    <div class="total">Total Payments: KES <?= number_format($total,2) ?></div>
</body>
</html>

<?php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

// Fetch customers for filter dropdown
$customers = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");

// Filter vars
$customer_id = $_GET['customer_id'] ?? '';
$from_date   = $_GET['from_date'] ?? '';
$to_date     = $_GET['to_date'] ?? '';

// Build query safely (simple approach)
$where = " WHERE 1=1 ";
$params = [];
if ($customer_id !== '') {
    $cid = (int)$customer_id;
    $where .= " AND p.customer_id = $cid ";
}
if ($from_date !== '') {
    $fd = $conn->real_escape_string($from_date);
    $where .= " AND p.date_paid >= '$fd' ";
}
if ($to_date !== '') {
    $td = $conn->real_escape_string($to_date);
    $where .= " AND p.date_paid <= '$td' ";
}

$sql = "SELECT p.*, c.name AS customer_name, c.phone AS customer_phone,
               (
                   SELECT MAX(p2.date_paid)
                   FROM payments p2
                   WHERE p2.customer_id = p.customer_id
                     AND p2.bill_type = 'internet'
               ) AS last_internet_payment
        FROM payments p
        JOIN customers c ON p.customer_id = c.id
        $where
        ORDER BY p.date_paid DESC, p.id DESC";

$result = $conn->query($sql);

// Collect rows and total
$rows = [];
$total = 0;
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $total += (float)$r['amount'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payments Overview - Nettrack</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; padding:20px; color:#111; }
        form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        select, input[type=date], button { padding:8px; border-radius:6px; border:1px solid #d1d5db; }
        button.primary { background:#16a34a; color:#fff; border:none; padding:9px 12px; border-radius:6px; cursor:pointer; }
        button.primary:hover{background:#15803d}
        .actions a { text-decoration:none; color:#fff; background:#2563eb; padding:6px 9px; border-radius:6px; margin-right:6px; }
        .actions a.print { background:#f59e0b; }
        table{width:100%;border-collapse:collapse;margin-top:16px;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 6px 20px rgba(2,6,23,0.08)}
        th{background:#16a34a;color:#fff;padding:12px;text-align:left}
        td{padding:12px;border-bottom:1px solid #eef2f7}
        tr:hover{background:#f8fafc}
        td.amount{text-align:right;font-weight:700}
        .small{font-size:13px;color:#6b7280}
        .total{margin-top:12px;font-weight:800}
        @media(max-width:700px){ table, thead, tbody, th, td, tr {display:block} th{display:none} td{border:none;padding:8px} td::before{font-weight:600;display:block} }
    </style>
</head>
<body>
    <h2>Payments Overview - Nettrack</h2>

    <form method="GET">
        <select name="customer_id">
            <option value="">-- All Customers --</option>
            <?php
            // rewind customers result set if needed
            if ($customers && method_exists($customers, 'data_seek')) $customers->data_seek(0);
            while ($c = $customers->fetch_assoc()):
            ?>
                <option value="<?= (int)$c['id'] ?>" <?= ($c['id']==$customer_id)?'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        From: <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
        To: <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
        <button type="submit" class="primary">Filter</button>
        <div style="margin-left:auto" class="small">Total shown: KES <?= number_format($total,2) ?></div>
    </form>

    <table aria-describedby="payments-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Amount (KES)</th>
                <th>Date Paid</th>
                <th>Method</th>
                <th>Remarks</th>
                <th>Last Internet Payment</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($row['customer_phone'] ?? '-') ?></td>
                    <td class="amount"><?= number_format($row['amount'],2) ?></td>
                    <td><?= htmlspecialchars($row['date_paid']) ?></td>
                    <td class="small"><?= htmlspecialchars($row['method'] ?: '-') ?></td>
                    <td class="small"><?= htmlspecialchars($row['remarks'] ?: '-') ?></td>
                    <td class="small"><?= $row['last_internet_payment'] ?: '—' ?></td>
                    <td>
                        <div class="actions">
                            <a href="receipt.php?id=<?= (int)$row['id'] ?>" target="_blank">Receipt</a>
                            <a href="edit_payment.php?id=<?= (int)$row['id'] ?>">Edit</a>
                            <a href="delete_payment.php?id=<?= (int)$row['id'] ?>" style="background:#ef4444">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:18px">No payments found for the selected filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

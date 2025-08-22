<?php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: payments.php"); exit; }

// Fetch record
$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$payment) { header("Location: payments.php"); exit; }

// Fetch customers for dropdown
$customers = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : null;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $date_paid = !empty($_POST['date_paid']) ? $_POST['date_paid'] : date('Y-m-d');
    $method = !empty($_POST['method']) ? trim($_POST['method']) : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
    $bill_type = !empty($_POST['bill_type']) ? trim($_POST['bill_type']) : 'internet';

    if ($amount <= 0) {
        $msg = "❌ Enter a valid amount.";
    } else {
        $up = $conn->prepare("UPDATE payments SET customer_id=?, amount=?, date_paid=?, method=?, remarks=?, bill_type=? WHERE id=?");
        $up->bind_param("idssssi", $customer_id, $amount, $date_paid, $method, $remarks, $bill_type, $id);
        if ($up->execute()) {
            // mark customer active if applicable
            if ($customer_id) {
                $u2 = $conn->prepare("UPDATE customers SET status='active' WHERE id = ?");
                $u2->bind_param("i", $customer_id);
                $u2->execute();
                $u2->close();
            }
            $up->close();
            header("Location: payments.php?msg=" . urlencode("✅ Payment updated."));
            exit;
        } else {
            $msg = "❌ Update failed: " . $up->error;
            $up->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Payment - Nettrack</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;padding:20px}
        form{max-width:520px;background:#fff;padding:16px;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,.06)}
        label{display:block;margin-top:10px}
        input,select,textarea{width:100%;padding:8px;margin-top:6px;border:1px solid #ddd;border-radius:6px}
        button{margin-top:12px;padding:10px 14px;background:#007BFF;color:#fff;border:none;border-radius:6px;cursor:pointer}
        .msg{margin:10px 0;font-weight:700}
        a.back{display:inline-block;margin-bottom:12px}
    </style>
</head>
<body>
    <a class="back" href="payments.php">⬅ Back to payments</a>
    <h2>Edit Payment #<?= $payment['id'] ?></h2>
    <?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>
    <form method="post" action="">
        <label>Customer (optional)</label>
        <select name="customer_id">
            <option value="">-- walk-in / no customer --</option>
            <?php while($c = $customers->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $payment['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Amount</label>
        <input type="number" name="amount" step="0.01" value="<?= htmlspecialchars($payment['amount']) ?>" required>

        <label>Date Paid</label>
        <input type="date" name="date_paid" value="<?= htmlspecialchars($payment['date_paid']) ?>">

        <label>Method</label>
        <input type="text" name="method" value="<?= htmlspecialchars($payment['method'] ?? '') ?>">

        <label>Remarks</label>
        <textarea name="remarks" rows="3"><?= htmlspecialchars($payment['remarks'] ?? '') ?></textarea>

        <label>Bill Type</label>
        <input type="text" name="bill_type" value="<?= htmlspecialchars($payment['bill_type'] ?? 'internet') ?>">

        <button type="submit">Update Payment</button>
    </form>
</body>
</html>

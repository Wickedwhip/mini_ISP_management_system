<?php
// delete_payment.php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

function redirect_with_msg($url, $msg = '') {
    if ($msg !== '') $url .= (strpos($url, '?') === false ? '?' : '&') . 'msg=' . urlencode($msg);
    header("Location: $url");
    exit;
}

// get id from GET or POST
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if (!$id) redirect_with_msg('payments.php', '❌ Invalid payment ID.');

// fetch payment
$stmt = $conn->prepare("SELECT id, customer_id, amount, date_paid, bill_type FROM payments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) redirect_with_msg('payments.php', "❌ Payment not found.");

// If POST -> perform deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: basic token
    if (!isset($_POST['token']) || !isset($_SESSION['delete_token']) || $_POST['token'] !== $_SESSION['delete_token']) {
        redirect_with_msg('payments.php', '❌ Invalid request token.');
    }
    unset($_SESSION['delete_token']);

    $del = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        $del->close();

        // If payment belonged to a customer, check if they have any payments in last 30 days.
        $cid = (int)$payment['customer_id'];
        if ($cid) {
            $chk = $conn->prepare("
                SELECT COUNT(*) AS cnt FROM payments
                WHERE customer_id = ? AND bill_type = 'internet' AND date_paid >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $chk->bind_param("i", $cid);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (isset($row['cnt']) && (int)$row['cnt'] === 0) {
                // no recent payments -> mark inactive
                $up = $conn->prepare("UPDATE customers SET status = 'inactive' WHERE id = ?");
                $up->bind_param("i", $cid);
                $up->execute();
                $up->close();
            }
        }

        redirect_with_msg('payments.php', "✅ Payment #$id deleted.");
    } else {
        $err = $del->error ?? 'Unknown error';
        $del->close();
        redirect_with_msg('payments.php', "❌ Delete failed: $err");
    }
    exit;
}

// Not POST -> show confirmation form
// create token
$token = bin2hex(random_bytes(16));
$_SESSION['delete_token'] = $token;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirm Delete Payment</title>
    <style>
        body{font-family:Arial;background:#f4f6f9;padding:20px}
        .card{max-width:520px;background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
        .meta{margin:8px 0;padding:8px;background:#f8f9fa;border-radius:6px}
        .actions{margin-top:12px}
        .btn{display:inline-block;padding:8px 12px;border-radius:6px;text-decoration:none}
        .danger{background:#d9534f;color:#fff;border:none}
        .muted{background:#6c757d;color:#fff;border:none}
    </style>
</head>
<body>
    <a href="payments.php">⬅ Back</a>
    <h2>Confirm Delete Payment #<?= htmlspecialchars($payment['id']) ?></h2>

    <div class="card">
        <p>Are you sure you want to delete this payment? This action cannot be undone.</p>

        <div class="meta"><strong>Customer ID:</strong> <?= htmlspecialchars($payment['customer_id'] ?? '-') ?></div>
        <div class="meta"><strong>Amount:</strong> KES <?= number_format($payment['amount'],2) ?></div>
        <div class="meta"><strong>Date Paid:</strong> <?= htmlspecialchars($payment['date_paid']) ?></div>
        <div class="meta"><strong>Bill Type:</strong> <?= htmlspecialchars($payment['bill_type'] ?? '-') ?></div>

        <form method="post" action="delete_payment.php" class="actions">
            <input type="hidden" name="id" value="<?= htmlspecialchars($payment['id']) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <button type="submit" class="btn danger">Delete Payment</button>
            <a href="payments.php" class="btn muted">Cancel</a>
        </form>
    </div>
</body>
</html>

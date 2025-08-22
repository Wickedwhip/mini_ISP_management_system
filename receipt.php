<?php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die('Invalid receipt request.');
}

// Fetch payment + customer
$stmt = $conn->prepare("
    SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, c.location AS customer_location
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die('Payment not found.');
}

// Company info (edit as needed)
$company = [
    'name' => 'Nettrack Limited',
    'tagline' => 'Reliable local ISP & Network Services',
    'address' => 'Unit 4, Riverside Plaza, Nairobi',
    'phone' => '+254 700 000 000',
    'email' => 'billing@nettrack.example',
    'website' => 'www.nettrack.example',
];

// Receipt identifiers
$invoice_no = 'NT-' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT);
$paid_date = date('d M Y', strtotime($payment['date_paid']));
$amount = number_format($payment['amount'], 2);
$method = $payment['method'] ?: '—';
$remarks = $payment['remarks'] ?: '—';
$customer = $payment['customer_name'] ?: 'Walk-in / Unknown';
$phone = $payment['customer_phone'] ?: '-';
$location = $payment['customer_location'] ?: '-';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt <?= htmlspecialchars($invoice_no) ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        :root{ --brand:#0f172a; --accent:#1d4ed8; --muted:#6b7280; }
        body{font-family:Arial, Helvetica, sans-serif;margin:0;padding:20px;background:#f3f4f6;color:#111}
        .receipt{max-width:760px;margin:20px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 10px 30px rgba(2,6,23,0.08)}
        .head{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
        .company{font-weight:800;color:var(--brand);letter-spacing:0.2px}
        .tag{color:var(--muted);font-size:13px}
        .meta{font-size:13px;color:var(--muted)}
        .divider{height:1px;background:#eef2f6;margin:16px 0;border-radius:2px}
        table{width:100%;border-collapse:collapse;margin-top:8px}
        td,th{padding:10px;text-align:left}
        th{background:#fafafa;font-weight:700;color:#111;border-bottom:1px solid #eef2f6}
        .amount{font-size:20px;font-weight:800;color:var(--brand);text-align:right}
        .info-row{display:flex;gap:12px;flex-wrap:wrap}
        .btns{margin-top:18px;display:flex;gap:10px}
        .btn{padding:10px 14px;border-radius:8px;text-decoration:none;color:#fff;background:var(--accent)}
        .btn.print{background:#f59e0b}
        .small{font-size:13px;color:var(--muted)}
        @media print{
            .btns{display:none}
            body{background:#fff}
            .receipt{box-shadow:none;border-radius:0;padding:0}
        }
    </style>
</head>
<body>
    <div class="receipt" role="document" aria-label="Payment Receipt">
        <div class="head">
            <div>
                <div class="company"><?= htmlspecialchars($company['name']) ?></div>
                <div class="tag"><?= htmlspecialchars($company['tagline']) ?></div>
                <div class="small"><?= htmlspecialchars($company['address']) ?> • <?= htmlspecialchars($company['phone']) ?> • <?= htmlspecialchars($company['email']) ?></div>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700">RECEIPT</div>
                <div class="meta">Invoice: <?= htmlspecialchars($invoice_no) ?></div>
                <div class="meta">Date: <?= htmlspecialchars($paid_date) ?></div>
            </div>
        </div>

        <div class="divider"></div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start">
            <div>
                <div style="font-weight:700;margin-bottom:6px">Billed To</div>
                <div><?= htmlspecialchars($customer) ?></div>
                <div class="small"><?= htmlspecialchars($phone) ?><?= $location ? ' • ' . htmlspecialchars($location) : '' ?></div>
            </div>
            <div style="text-align:right">
                <div style="font-size:13px;color:#666">Bill Type</div>
                <div style="font-weight:700"><?= htmlspecialchars($payment['bill_type'] ?? 'internet') ?></div>
            </div>
        </div>

        <table aria-describedby="receipt-table" style="margin-top:18px">
            <thead>
                <tr><th>Description</th><th>Qty</th><th style="text-align:right">Unit</th><th style="text-align:right">Total (KES)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Installation + Router + Ethernet (one-time)</td>
                    <td>1</td>
                    <td style="text-align:right">KES <?= $amount ?></td>
                    <td style="text-align:right">KES <?= $amount ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align:right">Amount Paid</th>
                    <th style="text-align:right">KES <?= $amount ?></th>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:12px">
            <div class="small"><strong>Method:</strong> <?= htmlspecialchars($method) ?></div>
            <div class="small"><strong>Remarks:</strong> <?= htmlspecialchars($remarks) ?></div>
        </div>

        <div class="btns">
            <a class="btn print" href="javascript:window.print()">Print Receipt</a>
            <a class="btn" href="payments.php">Back to payments</a>
        </div>

        <div style="margin-top:18px;font-size:12px;color:#6b7280">
            <div>Thank you for choosing <?= htmlspecialchars($company['name']) ?>. Please keep this receipt for your records.</div>
        </div>
    </div>
</body>
</html>

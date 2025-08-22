<?php
// pl_report.php
include 'db_connect.php';
require_once 'session.php';
requireLogin();
checkTimeout();

// Filter dates
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date   = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Total income
$payment_sql = "SELECT SUM(amount) AS total_income FROM payments WHERE 1=1";
if ($from_date != '') $payment_sql .= " AND date_paid >= '$from_date'";
if ($to_date   != '') $payment_sql .= " AND date_paid <= '$to_date'";
$payment_result = $conn->query($payment_sql);
$total_income = $payment_result->fetch_assoc()['total_income'] ?? 0;

// Total expenses
$expense_sql = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE 1=1";
if ($from_date != '') $expense_sql .= " AND date >= '$from_date'";
if ($to_date   != '') $expense_sql .= " AND date <= '$to_date'";
$expense_result = $conn->query($expense_sql);
$total_expenses = $expense_result->fetch_assoc()['total_expenses'] ?? 0;

// Net profit
$net_profit = $total_income - $total_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profit & Loss Report - Nettrack</title>
<style>
    :root {
        --bg: #f4f6f9;
        --card: #fff;
        --accent: #2563eb;
        --green: #10b981;
        --red: #ef4444;
        --muted: #6b7280;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; background: var(--bg); color: #111; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .container { text-align: center; width: 100%; max-width: 600px; }
    h2 { margin-bottom: 16px; }
    form { margin-bottom: 20px; }
    input[type=date], button { padding: 8px; margin: 4px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }
    button { background: var(--accent); color: #fff; border: none; cursor: pointer; }
    button:hover { background: #1d4ed8; }
    .report { background: var(--card); padding: 24px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); margin-top: 20px; text-align: left; }
    .report h3 { margin-top: 0; margin-bottom: 16px; font-size: 20px; text-align: center; }
    .item { display: flex; justify-content: space-between; margin: 10px 0; font-size: 16px; }
    .net { font-weight: bold; font-size: 18px; border-top: 1px solid #eee; padding-top: 12px; margin-top: 12px; }
    .back { display: inline-block; margin-bottom: 12px; padding: 8px 12px; background: var(--accent); color: white; border-radius: 6px; text-decoration: none; }
    .back:hover { background: #1d4ed8; }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back">⬅ Back to Dashboard</a>
    <h2>Profit & Loss Report - Nettrack</h2>

    <form method="GET">
        From: <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
        To: <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
        <button type="submit">Generate</button>
    </form>

    <div class="report">
        <h3>Summary</h3>
        <div class="item"><span>Total Income:</span> <span>KES <?= number_format($total_income,2) ?></span></div>
        <div class="item"><span>Total Expenses:</span> <span>KES <?= number_format($total_expenses,2) ?></span></div>
        <div class="item net"><span>Net Profit:</span> <span>KES <?= number_format($net_profit,2) ?></span></div>
    </div>
</div>
</body>
</html>

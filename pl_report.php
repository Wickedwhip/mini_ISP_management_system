<?php
include 'db_connect.php';
require_once 'session.php';
checkAuth(); // Ensure user is logged in

// Filter dates
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Payments total
$payment_sql = "SELECT SUM(amount) AS total_income FROM payments WHERE 1=1";
if ($from_date != '') $payment_sql .= " AND date_paid >= '$from_date'";
if ($to_date != '') $payment_sql .= " AND date_paid <= '$to_date'";
$payment_result = $conn->query($payment_sql);
$total_income = $payment_result->fetch_assoc()['total_income'] ?? 0;

// Expenses total
$expense_sql = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE 1=1";
if ($from_date != '') $expense_sql .= " AND date >= '$from_date'";
if ($to_date != '') $expense_sql .= " AND date <= '$to_date'";
$expense_result = $conn->query($expense_sql);
$total_expenses = $expense_result->fetch_assoc()['total_expenses'] ?? 0;

// Net Profit
$net_profit = $total_income - $total_expenses;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profit & Loss Report - Nettrack</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        input[type=date], button { padding: 8px; margin-right: 10px; border-radius: 5px; border: 1px solid #ccc; }
        button { background: #007BFF; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .report { background: white; padding: 20px; border-radius: 10px; max-width: 500px; margin-top: 20px; }
        .report h3 { margin-top: 0; }
        .item { display: flex; justify-content: space-between; margin: 10px 0; }
        .net { font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <h2>Profit & Loss Report - Nettrack</h2>
    <form method="GET">
        From: <input type="date" name="from_date" value="<?= $from_date ?>">
        To: <input type="date" name="to_date" value="<?= $to_date ?>">
        <button type="submit">Generate</button>
    </form>

    <div class="report">
        <h3>Summary</h3>
        <div class="item"><span>Total Income:</span> <span>KES <?= number_format($total_income,2) ?></span></div>
        <div class="item"><span>Total Expenses:</span> <span>KES <?= number_format($total_expenses,2) ?></span></div>
        <div class="item net"><span>Net Profit:</span> <span>KES <?= number_format($net_profit,2) ?></span></div>
    </div>
</body>
</html>

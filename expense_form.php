<?php
require_once 'session.php';
requireLogin();
checkTimeout();

$msg = '';

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expense_name = trim($_POST['expense_name']);
    $amount = floatval($_POST['amount']);
    $date = $_POST['date'];

    if ($expense_name === '' || $amount <= 0 || $date === '') {
        $msg = "❌ Please fill all fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (expense_name, amount, date) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $expense_name, $amount, $date);
        if ($stmt->execute()) {
            $msg = "✅ Expense recorded successfully.";
        } else {
            $msg = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Expense - Nettrack</title>
<style>
:root {
    --bg: #f4f6f9;
    --card: #fff;
    --accent: #2563eb;
    --success: #10b981;
    --error: #ef4444;
}
body {
    font-family: Arial, sans-serif;
    background: var(--bg);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.form-container {
    background: var(--card);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    width: 400px;
    text-align: center;
}
h2 {
    margin-bottom: 20px;
}
label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
    text-align: left;
}
input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}
button {
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    background: var(--accent);
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
}
button:hover {
    background: #1d4ed8;
}
.msg {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
}
.msg.success { background: rgba(16,185,129,0.12); color: var(--success); }
.msg.error { background: rgba(239,68,68,0.12); color: var(--error); }
.btn-back {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    color: #2563eb;
    font-weight: bold;
}
.btn-back:hover {
    text-decoration: underline;
}

</style>
</head>
<body>
<div class="form-container">
      <a href="dashboard.php" class="btn-back">⬅ Back</a>

    <h2>Record Expense</h2>
    <?php if($msg): ?>
        <div class="msg <?= strpos($msg,'✅')!==false ? 'success':'error' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <label>Expense Name:</label>
        <input type="text" name="expense_name" placeholder="e.g. Router Purchase" required>

        <label>Amount (KES):</label>
        <input type="number" step="0.01" name="amount" placeholder="e.g. 2500" required>

        <label>Date:</label>
        <input type="date" name="date" required>

        <button type="submit">Save Expense</button>
    </form>
</div>
</body>
</html>

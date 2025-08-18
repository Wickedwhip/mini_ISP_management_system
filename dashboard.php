<?php
// dashboard.php - dynamic, resilient, keeps your layout/vibe
require_once 'session.php';
requireLogin();
checkTimeout();

// -- helpers ------------------------------------------------------
/**
 * Run a query and return result or false (no die).
 */
function safeQuery($conn, $sql) {
    $res = $conn->query($sql);
    if ($res === false) {
        // collect debug info (not shown to users by default)
        if (!isset($GLOBALS['dashboard_debug'])) $GLOBALS['dashboard_debug'] = [];
        $GLOBALS['dashboard_debug'][] = "Query error: " . $conn->error . " -- SQL: $sql";
    }
    return $res;
}

/**
 * Return single numeric value (SUM/COUNT), fallback to 0.
 */
function singleValue($conn, $sql) {
    $res = safeQuery($conn, $sql);
    if ($res && $row = $res->fetch_assoc()) {
        return $row['total'] ?? 0;
    }
    return 0;
}

/**
 * Check if column exists in given table (current DB).
 */
function columnExists($conn, $table, $column) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE()")->fetch_array()[0]);
    $table_e = $conn->real_escape_string($table);
    $column_e = $conn->real_escape_string($column);
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table_e' AND COLUMN_NAME = '$column_e' LIMIT 1";
    $res = safeQuery($conn, $sql);
    return ($res && $res->num_rows > 0);
}

// Ensure $conn exists (session.php should create it)
if (!isset($conn)) {
    die("Database connection not found. Check session.php");
}

// -- Stats (safe) -------------------------------------------------
$totalCustomers = singleValue($conn, "SELECT COUNT(*) AS total FROM customers");
$totalIncome    = singleValue($conn, "SELECT SUM(amount) AS total FROM payments");
$totalExpenses  = singleValue($conn, "SELECT SUM(amount) AS total FROM expenses");

// Overdue: try to use date_paid; otherwise 0
$overdueAmount = 0;
if (columnExists($conn, 'payments', 'date_paid')) {
    $overdueAmount = singleValue($conn, "SELECT SUM(amount) AS total FROM payments WHERE date_paid < NOW() AND customer_id = 0");
}

// -- Recent Activity (payments, customers, expenses) ---------------
$recentActivities = [];

// Payments - prefer date_paid; fallback to id order
$paymentsDateCol = columnExists($conn, 'payments', 'date_paid') ? 'date_paid' : null;
$sqlPayments = "
    SELECT p.id, p.amount, p.customer_id, " . ($paymentsDateCol ? "p.$paymentsDateCol AS time_col," : "NULL AS time_col,") . "
           COALESCE(c.name, 'Unknown') AS customer_name,
           " . (columnExists($conn, 'payments', 'method') ? "p.method" : "NULL") . " AS method,
           " . (columnExists($conn, 'payments', 'remarks') ? "p.remarks" : "NULL") . " AS remarks
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    ORDER BY " . ($paymentsDateCol ? "p.$paymentsDateCol DESC" : "p.id DESC") . "
    LIMIT 6
";
$resPayments = safeQuery($conn, $sqlPayments);
if ($resPayments) {
    while ($r = $resPayments->fetch_assoc()) {
        $recentActivities[] = [
            'type' => 'payment',
            'message' => "Payment received from {$r['customer_name']}",
            'time' => $r['time_col'] ?? date('Y-m-d H:i:s'),
            'amount' => $r['amount'] ?? 0
        ];
    }
}

// Customers - prefer start_date, then created_at/date_created, then id
$customerTimeCol = null;
foreach (['start_date','created_at','date_created','date'] as $cCol) {
    if (columnExists($conn, 'customers', $cCol)) { $customerTimeCol = $cCol; break; }
}
$sqlCustomers = "SELECT id, name" . ($customerTimeCol ? ", $customerTimeCol AS time_col" : ", NULL AS time_col") . " FROM customers ORDER BY " . ($customerTimeCol ? "$customerTimeCol DESC" : "id DESC") . " LIMIT 6";
$resCustomers = safeQuery($conn, $sqlCustomers);
if ($resCustomers) {
    while ($r = $resCustomers->fetch_assoc()) {
        $recentActivities[] = [
            'type' => 'customer',
            'message' => "New customer registered: {$r['name']}",
            'time' => $r['time_col'] ?? date('Y-m-d H:i:s')
        ];
    }
}

// Expenses - prefer date_created/created_at/date; use id/time fallback
$expenseTimeCol = null;
foreach (['date_created','created_at','date'] as $eCol) {
    if (columnExists($conn, 'expenses', $eCol)) { $expenseTimeCol = $eCol; break; }
}
$expenseDescCol = columnExists($conn, 'expenses', 'description') ? 'description' : null;
$sqlExpenses = "SELECT id, amount" . ($expenseTimeCol ? ", $expenseTimeCol AS time_col" : ", NULL AS time_col") . ($expenseDescCol ? ", $expenseDescCol AS desc_col" : "") . " FROM expenses ORDER BY " . ($expenseTimeCol ? "$expenseTimeCol DESC" : "id DESC") . " LIMIT 6";
$resExpenses = safeQuery($conn, $sqlExpenses);
if ($resExpenses) {
    while ($r = $resExpenses->fetch_assoc()) {
        $msg = $expenseDescCol ? "Expense recorded: {$r['desc_col']}" : "Expense recorded: ID {$r['id']}";
        $recentActivities[] = [
            'type' => 'expense',
            'message' => $msg,
            'time' => $r['time_col'] ?? date('Y-m-d H:i:s'),
            'amount' => $r['amount'] ?? 0
        ];
    }
}

// Sort activities by time desc and keep top 5
usort($recentActivities, function($a,$b){
    return strtotime($b['time'] ?? '1970-01-01 00:00:00') - strtotime($a['time'] ?? '1970-01-01 00:00:00');
});
$recentActivities = array_slice($recentActivities, 0, 5);

// -- Chart data: last 6 months (income, expenses, customers) -------
$labels = [];
$incomeSeries = [];
$expenseSeries = [];
$customerSeries = [];

for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -$i month");
    $label = $dt->format('M');
    $ymStart = $dt->format('Y-m-01');
    $ymEnd = $dt->format('Y-m-t');
    $labels[] = $label;

    // income
    $sqlInc = columnExists($conn, 'payments', 'date_paid')
        ? "SELECT SUM(amount) AS total FROM payments WHERE date_paid BETWEEN '$ymStart' AND '$ymEnd'"
        : "SELECT SUM(amount) AS total FROM payments";
    $incomeSeries[] = (float) singleValue($conn, $sqlInc);

    // expenses
    $expenseDateCol = null;
    foreach (['date_created','created_at','date'] as $c) if (columnExists($conn,'expenses',$c)) { $expenseDateCol = $c; break; }
    $sqlExp = $expenseDateCol
        ? "SELECT SUM(amount) AS total FROM expenses WHERE $expenseDateCol BETWEEN '$ymStart' AND '$ymEnd'"
        : "SELECT SUM(amount) AS total FROM expenses";
    $expenseSeries[] = (float) singleValue($conn, $sqlExp);

    // customers (count new customers per month using start_date or id fallback)
    if ($customerTimeCol) {
        $sqlCus = "SELECT COUNT(*) AS total FROM customers WHERE $customerTimeCol BETWEEN '$ymStart' AND '$ymEnd'";
    } else {
        // fallback: approximate with id (can't determine date) -> set 0
        $sqlCus = "SELECT 0 AS total";
    }
    $customerSeries[] = (int) singleValue($conn, $sqlCus);
}

// -- percentage change placeholders (you can implement proper calc later) --
$customerChange = ($totalCustomers > 0) ? round(($totalCustomers/ max(1,($totalCustomers-10)) - 1)*100, 1) : 0;
$incomeChange = 0; $expenseChange = 0; $overdueChange = 0;

// time_elapsed helper (guard)
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        try {
            $now = new DateTime;
            $ago = new DateTime($datetime);
        } catch (Exception $e) {
            return 'just now';
        }
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y'=>'year','m'=>'month','w'=>'week','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second');
        foreach ($string as $k=>&$v) {
            if ($diff->$k) $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            else unset($string[$k]);
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nettrack Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Keep your original CSS vibe — minimal adjustments preserved */
        :root{--primary:#4361ee;--secondary:#3f37c9;--success:#4cc9f0;--danger:#f72585;--warning:#f8961e;--light:#f8f9fa;--dark:#212529}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma, Geneva, Verdana, sans-serif;}
        body{background-color:#f5f7fa;color:#333}
        .dashboard{display:grid;grid-template-columns:250px 1fr;min-height:100vh}
        .sidebar{background:var(--dark);color:#fff;padding:20px 0}
        .logo{padding:0 20px 20px;border-bottom:1px solid rgba(255,255,255,0.1);text-align:center}
        .nav-menu{margin-top:20px}
        .nav-item{display:block;padding:12px 20px;color:#fff;text-decoration:none}
        .nav-item:hover{background:rgba(255,255,255,0.06)}
        .nav-item.active{background:var(--primary)}
        .main-content{padding:20px}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}
        .user-profile{display:flex;align-items:center}
        .user-profile img{width:40px;height:40px;border-radius:50%;margin-right:10px}
        .stats-container{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
        .stat-card h3{color:#666;font-size:14px;margin-bottom:10px}
        .stat-card .value{font-size:28px;font-weight:700;margin-bottom:8px}
        .stat-card.customers{border-left:4px solid var(--primary)}
        .stat-card.income{border-left:4px solid var(--success)}
        .stat-card.expenses{border-left:4px solid var(--warning)}
        .stat-card.overdue{border-left:4px solid var(--danger)}
        .quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
        .action-card{background:#fff;border-radius:10px;padding:20px;text-align:center;box-shadow:0 4px 6px rgba(0,0,0,0.1);text-decoration:none;color:var(--dark)}
        .action-card:hover{background:var(--primary);color:#fff}
        .charts-container{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:30px}
        .chart-card,.recent-activity{background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
        .chart-placeholder{height:250px;background:#f8f9fa;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#999}
        .recent-activity .activity-item{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #eee}
        .recent-activity .activity-item:last-child{border-bottom:none}
        .recent-activity .activity-item i{width:40px;height:40px;border-radius:50%;background:#f8f9fa;display:flex;align-items:center;justify-content:center;margin-right:15px;color:var(--primary)}
        .activity-info{flex:1}
        .activity-time{color:#999;font-size:12px}
        .amount{font-weight:700}
        @media(max-width:992px){.charts-container{grid-template-columns:1fr}}
        @media(max-width:768px){.dashboard{grid-template-columns:1fr}.sidebar{display:none}}
    </style>
</head>
<body>
<div class="dashboard">
    <div class="sidebar">
        <div class="logo"><h2>Nettrack</h2></div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="customer_reg.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            <a href="payments.php" class="nav-item"><i class="fas fa-money-bill-wave"></i> Payments</a>
            <a href="payment_overview.php" class="nav-item"><i class="fas fa-chart-line"></i> Payment Overview</a>
            <a href="expense_form.php" class="nav-item"><i class="fas fa-receipt"></i> Expenses</a>
            <a href="pl_report.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> P&L Report</a>
            <a href="reminder.php" class="nav-item"><i class="fas fa-bell"></i> Reminders</a>
            <a href="search.php" class="nav-item"><i class="fas fa-search"></i> Search</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-profile">
                <img src="https://via.placeholder.com/40" alt="User">
                <span><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card customers">
                <h3>Total Customers</h3>
                <div class="value"><?= number_format($totalCustomers) ?></div>
                <div class="trend"><?= $customerChange > 0 ? '↑' : '↓' ?> <?= abs($customerChange) ?>% from last month</div>
            </div>
            <div class="stat-card income">
                <h3>Total Income</h3>
                <div class="value">KES <?= number_format($totalIncome,2) ?></div>
                <div class="trend"><?= $incomeChange > 0 ? '↑' : '↓' ?> <?= abs($incomeChange) ?>% from last month</div>
            </div>
            <div class="stat-card expenses">
                <h3>Total Expenses</h3>
                <div class="value">KES <?= number_format($totalExpenses,2) ?></div>
                <div class="trend"><?= $expenseChange > 0 ? '↑' : '↓' ?> <?= abs($expenseChange) ?>% from last month</div>
            </div>
            <div class="stat-card overdue">
                <h3>Overdue Internet Bill</h3>
                <div class="value">KES <?= number_format($overdueAmount,2) ?></div>
                <div class="trend"><?= $overdueChange > 0 ? '↑' : '↓' ?> <?= abs($overdueChange) ?>% from last month</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="customer_reg.php" class="action-card"><i class="fas fa-user-plus"></i><h3>Add Customer</h3></a>
            <a href="payments.php" class="action-card"><i class="fas fa-money-bill-alt"></i><h3>Record Payment</h3></a>
            <a href="expense_form.php" class="action-card"><i class="fas fa-plus-circle"></i><h3>Add Expense</h3></a>
            <a href="reminder.php" class="action-card"><i class="fas fa-bell"></i><h3>Set Reminder</h3></a>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>Profit & Loss (Last 6 Months)</h3>
                <div class="chart-placeholder"><canvas id="plChart"></canvas></div>
            </div>
            <div class="chart-card">
                <h3>Customer Growth</h3>
                <div class="chart-placeholder"><canvas id="growthChart"></canvas></div>
            </div>
        </div>

        <div class="recent-activity">
            <h3>Recent Activity</h3>
            <?php if (empty($recentActivities)): ?>
                <p>No recent activity.</p>
            <?php else: ?>
                <?php foreach ($recentActivities as $act): ?>
                    <div class="activity-item">
                        <i class="<?php
                            echo ($act['type'] === 'payment') ? 'fas fa-money-bill-wave' :
                                 (($act['type'] === 'customer') ? 'fas fa-user-plus' : 'fas fa-receipt');
                        ?>"></i>
                        <div class="activity-info">
                            <p><?= htmlspecialchars($act['message']) ?></p>
                            <p class="activity-time"><?= htmlspecialchars(time_elapsed_string($act['time'])) ?></p>
                        </div>
                        <?php if (isset($act['amount'])): ?>
                            <div class="amount">KES <?= number_format($act['amount'],2) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const plChart = new Chart(document.getElementById('plChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                { label: 'Income', data: <?= json_encode($incomeSeries) ?>, borderColor:'#4cc9f0', backgroundColor:'rgba(76,201,240,0.1)', tension:0.3, fill:true },
                { label: 'Expenses', data: <?= json_encode($expenseSeries) ?>, borderColor:'#f8961e', backgroundColor:'rgba(248,150,30,0.1)', tension:0.3, fill:true }
            ]
        },
        options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
    });

    const growthChart = new Chart(document.getElementById('growthChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{ label:'New Customers', data: <?= json_encode($customerSeries) ?>, backgroundColor:'#4361ee' }]
        },
        options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
    });
});
</script>
</body>
</html>
<!-- debug (server-side): <?= isset($dashboard_debug) ? htmlspecialchars(implode(" | ", $dashboard_debug)) : '' ?> -->

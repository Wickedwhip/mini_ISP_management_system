<?php
require_once 'session.php';
checkAuth(); // Ensure user is logged in
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettrack Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--dark);
            color: white;
            padding: 20px 0;
        }
        
        .logo {
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo h2 {
            color: white;
        }
        
        .nav-menu {
            margin-top: 20px;
        }
        
        .nav-item {
            display: block;
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-item.active {
            background: var(--primary);
        }
        
        .nav-item i {
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            padding: 20px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--dark);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-card.customers { border-left: 4px solid var(--primary); }
        .stat-card.income { border-left: 4px solid var(--success); }
        .stat-card.expenses { border-left: 4px solid var(--warning); }
        .stat-card.overdue { border-left: 4px solid var(--danger); }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark);
            text-decoration: none;
        }
        
        .action-card:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        .action-card i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        /* Charts */
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .chart-card h3 {
            margin-bottom: 20px;
            color: #666;
        }
        
        .chart-placeholder {
            height: 250px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .recent-activity h3 {
            margin-bottom: 20px;
            color: #666;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item i {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
        }
        
        .activity-info {
            flex: 1;
        }
        
        .activity-info p {
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #999;
            font-size: 12px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>Nettrack</h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="customer_reg.php" class="nav-item">
                    <i class="fas fa-users"></i> Customers
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i> Payments
                </a>
                <a href="payment_overview.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Payment Overview
                </a>
                <a href="expense_form.php" class="nav-item">
                    <i class="fas fa-receipt"></i> Expenses
                </a>
                <a href="pl_report.php" class="nav-item">
                    <i class="fas fa-file-invoice-dollar"></i> P&L Report
                </a>
                <a href="reminder.php" class="nav-item">
                    <i class="fas fa-bell"></i> Reminders
                </a>
                <a href="search.php" class="nav-item">
                    <i class="fas fa-search"></i> Search
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-profile">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card customers">
                    <h3>Total Customers</h3>
                    <div class="value">1,248</div>
                    <div class="trend">↑ 12% from last month</div>
                </div>
                <div class="stat-card income">
                    <h3>Total Income</h3>
                    <div class="value">$24,580</div>
                    <div class="trend">↑ 8% from last month</div>
                </div>
                <div class="stat-card expenses">
                    <h3>Total Expenses</h3>
                    <div class="value">$8,420</div>
                    <div class="trend">↓ 3% from last month</div>
                </div>
                <div class="stat-card overdue">
                    <h3>Overdue Internet Bill</h3>
                    <div class="value">$3,250</div>
                    <div class="trend">↑ 15% from last month</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="customer_reg.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Add Customer</h3>
                </a>
                <a href="payments.php" class="action-card">
                    <i class="fas fa-money-bill-alt"></i>
                    <h3>Record Payment</h3>
                </a>
                <a href="expense_form.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add Expense</h3>
                </a>
                <a href="reminder.php" class="action-card">
                    <i class="fas fa-bell"></i>
                    <h3>Set Reminder</h3>
                </a>
            </div>
            
            <!-- Charts -->
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Profit & Loss (Last 6 Months)</h3>
                    <div class="chart-placeholder">
                        <canvas id="plChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Customer Growth</h3>
                    <div class="chart-placeholder">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <div class="activity-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="activity-info">
                        <p>Payment received from John Doe</p>
                        <p class="activity-time">10 minutes ago</p>
                    </div>
                    <div class="amount">$120</div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-user-plus"></i>
                    <div class="activity-info">
                        <p>New customer registered: Sarah Smith</p>
                        <p class="activity-time">1 hour ago</p>
                    </div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-receipt"></i>
                    <div class="activity-info">
                        <p>Expense recorded: Office supplies</p>
                        <p class="activity-time">3 hours ago</p>
                    </div>
                    <div class="amount">$85</div>
                </div>
                <div class="activity-item">
                    <i class="fas fa-bell"></i>
                    <div class="activity-info">
                        <p>Reminder: Internet bill due tomorrow</p>
                        <p class="activity-time">Yesterday</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js for the graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // You would replace this with actual data from your database
        document.addEventListener('DOMContentLoaded', function() {
            // Profit & Loss Chart
            const plChart = new Chart(document.getElementById('plChart'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'Income',
                            data: [12000, 19000, 15000, 18000, 22000, 24500],
                            borderColor: '#4cc9f0',
                            backgroundColor: 'rgba(76, 201, 240, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Expenses',
                            data: [8000, 9500, 10000, 8500, 12000, 8400],
                            borderColor: '#f8961e',
                            backgroundColor: 'rgba(248, 150, 30, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Customer Growth Chart
            const growthChart = new Chart(document.getElementById('growthChart'), {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'New Customers',
                        data: [45, 60, 55, 70, 85, 90],
                        backgroundColor: '#4361ee'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
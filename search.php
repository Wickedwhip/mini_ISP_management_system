<?php
include 'db_connect.php';
require_once 'session.php';
checkAuth(); // Ensure user is logged in

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Fetch customers based on search
$sql = "SELECT * FROM customers 
        WHERE name LIKE '%$search%' 
           OR phone LIKE '%$search%' 
           OR package LIKE '%$search%'
        ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Customers - Nettrack</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .search-box { margin-bottom: 20px; }
        input[type=text] { padding: 8px; width: 300px; border-radius: 5px; border: 1px solid #ccc; }
        button { padding: 8px 12px; border: none; background: #007BFF; color: white; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background: #007BFF; color: white; }
        tr:hover { background: #f1f1f1; }
    </style>
</head>
<body>
    <h2>Search Customers - Nettrack</h2>
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search by name, phone, or package" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Package</th>
            <th>Install Fee</th>
            <th>Router Cost</th>
            <th>Ethernet Cost</th>
            <th>Start Date</th>
            <th>Status</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['package']) ?></td>
                    <td><?= number_format($row['install_fee'],2) ?></td>
                    <td><?= number_format($row['router_cost'],2) ?></td>
                    <td><?= number_format($row['ethernet_cost'],2) ?></td>
                    <td><?= $row['start_date'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="10" style="text-align:center;">No customers found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>

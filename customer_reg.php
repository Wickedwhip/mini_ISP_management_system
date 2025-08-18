<?php
require_once 'session.php';
requireLogin(); // locks page to logged-in users
checkTimeout(); // optional, for session expiry

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $package = $_POST['package'];
    $install_fee = $_POST['install_fee'];
    $router_cost = $_POST['router_cost'];
    $ethernet_cost = $_POST['ethernet_cost'];
    $start_date = $_POST['start_date'];
    $status = $_POST['status'];

    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, package, install_fee, router_cost, ethernet_cost, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        echo "<p class='error'>❌ Prepare failed: " . $conn->error . "</p>";
    } else {
        $stmt->bind_param("ssssdddss", $name, $phone, $address, $package, $install_fee, $router_cost, $ethernet_cost, $start_date, $status);
        if ($stmt->execute()) {
            echo "<p class='success'>✅ New customer added successfully!</p>";
        } else {
            echo "<p class='error'>❌ Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add Customer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
            width: 420px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #007BFF;
            color: #fff;
            border: none;
            font-size: 16px;
            margin-top: 15px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .success { color: green; font-weight: bold; text-align: center; }
        .error { color: red; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add New Customer</h2>
        <form method="POST">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Phone:</label>
            <input type="text" name="phone" required>

            <label>Address:</label>
            <input type="text" name="address">

            <label>Package:</label>
            <input type="text" name="package">

            <label>Install Fee:</label>
            <input type="number" step="0.01" name="install_fee">

            <label>Router Cost:</label>
            <input type="number" step="0.01" name="router_cost">

            <label>Ethernet Cost:</label>
            <input type="number" step="0.01" name="ethernet_cost">

            <label>Start Date:</label>
            <input type="date" name="start_date" required>

            <label>Status:</label>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <button type="submit">Add Customer</button>
        </form>
    </div>
</body>
</html>

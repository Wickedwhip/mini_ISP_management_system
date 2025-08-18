<!-- expense_form.php -->
<?php
require_once 'session.php';
checkAuth(); // Ensure user is logged in

session_start();
include("db_connect.php"); // connect to your mini_ISP_management_system DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expense_name = $_POST['expense_name'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    $sql = "INSERT INTO expenses (expense_name, amount, date) 
            VALUES ('$expense_name', '$amount', '$date')";

    if ($conn->query($sql) === TRUE) {
        echo "Expense recorded successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Expense Form</title>
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
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      width: 400px;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    input {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      margin-top: 20px;
      background: #007bff;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Record Expense</h2>
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

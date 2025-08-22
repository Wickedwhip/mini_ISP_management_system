<?php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

$msg = '';
$customers = $conn->query("SELECT id, name, installation_fee, router_cost, ethernet_cost, phone FROM customers ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = isset($_POST['customer_id']) && $_POST['customer_id'] !== '' ? (int)$_POST['customer_id'] : 0;
    $date_paid = !empty($_POST['date_paid']) ? $_POST['date_paid'] : date('Y-m-d');
    $method = !empty($_POST['method']) ? trim($_POST['method']) : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
    $bill_type = 'internet';

    if ($customer_id <= 0) {
        $msg = "❌ Please select a customer.";
    } else {
        $s = $conn->prepare("SELECT COALESCE(installation_fee,0) AS inst, COALESCE(router_cost,0) AS router, COALESCE(ethernet_cost,0) AS eth FROM customers WHERE id = ?");
        $s->bind_param("i", $customer_id);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        $s->close();

        if (!$row) {
            $msg = "❌ Customer not found.";
        } else {
            $amount = (float)$row['inst'] + (float)$row['router'] + (float)$row['eth'];
            if ($amount <= 0) {
                $msg = "❌ Computed amount is zero — check customer fees.";
            } else {
                $stmt = $conn->prepare("INSERT INTO payments (customer_id, amount, date_paid, method, remarks, bill_type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("idssss", $customer_id, $amount, $date_paid, $method, $remarks, $bill_type);
                if ($stmt->execute()) {
                    $up = $conn->prepare("UPDATE customers SET status='active' WHERE id = ?");
                    $up->bind_param("i", $customer_id);
                    $up->execute();
                    $up->close();
                    $stmt->close();
                    header("Location: payments.php?msg=" . urlencode("✅ Payment recorded. Amount: KES " . number_format($amount,2)));
                    exit;
                } else {
                    $msg = "❌ Save failed: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Add Payment - Nettrack</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root {
    --bg: #f4f6f9;
    --card: #ffffff;
    --muted: #6b7280;
    --accent: #007BFF;
    --accent-600: #0056b3;
    --radius: 10px;
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background:var(--bg);
    color:#111827;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
  }

  /* page layout: header + centred content */
  .header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    max-width:1100px;
    margin:20px auto;
    padding:0 16px;
  }
  .header .back {
    color:var(--accent);
    text-decoration:none;
    font-weight:600;
  }
  .header h1 { margin:0; font-size:18px; color:#111827; }

  .wrap {
    max-width:760px;
    margin:18px auto 60px;
    padding:20px;
    display:block;
  }

  /* centred card */
  .card {
    background:var(--card);
    border-radius:var(--radius);
    padding:22px;
    box-shadow:0 8px 30px rgba(15,23,42,0.06);
    margin:0 auto;
  }

  .row { display:flex; gap:12px; }
  .col { flex:1; min-width:0; }

  label{display:block;font-weight:600;margin-bottom:8px;color:#111827;font-size:13px}
  .hint{font-size:12px;color:var(--muted);margin-top:6px}

  input, select, textarea {
    width:100%;
    padding:10px 12px;
    border:1px solid #e6e9ee;
    border-radius:8px;
    font-size:14px;
    color:#111827;
    background:#fff;
  }
  input[readonly]{background:#f8fafc;color:#111827}

  button.primary {
    display:inline-block;
    padding:12px 18px;
    background:var(--accent);
    color:#fff;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:700;
    margin-top:14px;
  }
  button.primary:hover{background:var(--accent-600)}

  .msg { margin:12px 0; padding:10px 12px; border-radius:8px; color:#fff; background:#d9534f; font-weight:700; }
  .success { background:#16a34a; }

  /* responsive */
  @media (min-width:900px){
    .wrap { padding:0 16px; }
    .card { padding:28px; }
    .two { display:flex; gap:16px; }
    .two .col{ flex:1 }
  }
  @media (max-width:599px){
    .header { padding:0 12px; }
    .wrap { margin:12px 12px 40px; }
  }

  .meta-line { display:flex; gap:10px; align-items:center; color:var(--muted); font-size:13px; margin-top:6px }
  .customer-amount { font-weight:800; font-size:16px; color:#111827; }
</style>
</head>
<body>

  <div class="header">
    <a class="back" href="payments.php">⬅ Back to payments</a>
    <h1>Add Payment</h1>
  </div>

  <div class="wrap">
    <div class="card" role="main" aria-labelledby="form-title">
      <?php if($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <label for="customer_select">Customer <small style="font-weight:600;color:var(--muted)"> (required)</small></label>
        <select name="customer_id" id="customer_select" required>
            <option value="">-- Select Customer --</option>
            <?php 
              // rewind result set if necessary
              if ($customers && $customers->num_rows && $customers->data_seek) { $customers->data_seek(0); }
              while($c = $customers->fetch_assoc()):
                $sum = (float)$c['installation_fee'] + (float)$c['router_cost'] + (float)$c['ethernet_cost'];
            ?>
              <option value="<?= (int)$c['id'] ?>"
                      data-phone="<?= htmlspecialchars($c['phone'] ?? '-') ?>"
                      data-sum="<?= htmlspecialchars(number_format($sum,2,'.','')) ?>"
              >
                <?= htmlspecialchars($c['name']) ?> — KES <?= number_format($sum,2) ?>
              </option>
            <?php endwhile; ?>
        </select>

        <div style="margin-top:14px" class="two">
          <div class="col">
            <label for="customer_phone">Customer No.</label>
            <input type="text" id="customer_phone" readonly placeholder="Phone will appear here">
          </div>
          <div class="col">
            <label for="amount_display">Amount (auto)</label>
            <input type="number" id="amount_display" name="amount_display" step="0.01" readonly>
            <div class="meta-line">
              <div class="customer-amount" id="live-amount">KES 0.00</div>
              <div class="hint">Calculated from installation + router + ethernet</div>
            </div>
          </div>
        </div>

        <div style="margin-top:12px" class="row">
          <div class="col">
            <label for="date_paid">Date Paid</label>
            <input type="date" name="date_paid" id="date_paid" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col">
            <label for="method">Method</label>
            <input type="text" name="method" id="method" placeholder="Cash / Mpesa / Card">
          </div>
        </div>

        <label for="remarks" style="margin-top:14px">Remarks</label>
        <textarea name="remarks" id="remarks" rows="4" placeholder="Optional notes..."></textarea>

        <button type="submit" class="primary">Save Payment</button>
      </form>
    </div>
  </div>

<script>
(function(){
  const sel = document.getElementById('customer_select');
  const phone = document.getElementById('customer_phone');
  const amount = document.getElementById('amount_display');
  const live = document.getElementById('live-amount');

  function updateFromOption(opt){
    if(!opt || !opt.value){
      phone.value = '';
      amount.value = '';
      live.textContent = 'KES 0.00';
      return;
    }
    const ph = opt.getAttribute('data-phone') || '-';
    const s = opt.getAttribute('data-sum') || '0';
    phone.value = ph;
    amount.value = parseFloat(s).toFixed(2);
    live.textContent = 'KES ' + parseFloat(s).toFixed(2);
  }

  sel.addEventListener('change', function(){
    updateFromOption(this.options[this.selectedIndex]);
  });

  // If page reopened with customer_id preselected via query string, try set it
  (function tryPreselect(){
    try {
      const params = new URLSearchParams(window.location.search);
      const cid = params.get('customer_id');
      if(cid){
        for(const o of sel.options){
          if(o.value === cid){ o.selected = true; updateFromOption(o); break; }
        }
      }
    } catch(e){}
  })();
})();
</script>
</body>
</html>

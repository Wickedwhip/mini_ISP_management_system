<?php
// reminder.php
require_once 'session.php';
requireLogin();
checkTimeout();
date_default_timezone_set('Africa/Nairobi');

// Filter: all or overdue only
$filter = $_GET['filter'] ?? 'all';

// Fetch customers and their latest internet payment (if any) including email
$sql = "
  SELECT c.id, c.name, c.phone, c.email, c.location,
         p.last_paid
  FROM customers c
  LEFT JOIN (
      SELECT customer_id, MAX(date_paid) AS last_paid
      FROM payments
      WHERE bill_type = 'internet'
      GROUP BY customer_id
  ) p ON c.id = p.customer_id
  ORDER BY c.name ASC
";
$res = $conn->query($sql);

// build rows
$rows = [];
$today = date('Y-m-d');
if ($res && $res->num_rows) {
    while ($r = $res->fetch_assoc()) {
        $last_paid = $r['last_paid']; // may be null
        $next_due = $last_paid ? date('Y-m-d', strtotime($last_paid . ' +30 days')) : null;

        if ($next_due) {
            $diff = (strtotime($next_due) - strtotime($today)) / 86400;
            $days_left = (int) floor($diff);            // can be negative
        } else {
            $days_left = null;
        }

        if ($last_paid === null) {
            $status = 'No payment';
        } elseif ($days_left < 0) {
            $status = 'Overdue';
        } else {
            $status = 'Active';
        }

        $rows[] = array_merge($r, [
            'next_due'  => $next_due,
            'days_left' => $days_left,
            'status'    => $status,
        ]);
    }
}

// Optionally filter only overdue
if ($filter === 'overdue') {
    $rows = array_filter($rows, function($r){ return $r['status'] === 'Overdue' || $r['status'] === 'No payment'; });
}

// helper for safe output
function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reminders - Nettrack</title>
<style>
:root{--bg:#f4f6f9;--card:#fff;--accent:#0f172a;--muted:#6b7280;--green:#10b981;--red:#ef4444;--orange:#f59e0b}
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,Helvetica,sans-serif;background:var(--bg);color:#111}
.container{max-width:1000px;margin:28px auto;padding:16px}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.header h1{font-size:18px;margin:0}
.controls{display:flex;gap:8px;align-items:center}
.btn{display:inline-block;padding:8px 12px;border-radius:8px;text-decoration:none;color:#fff;background:#2563eb}
.btn.ghost{background:transparent;color:var(--accent);border:1px solid #e6e9ee}
.card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.06)}
.table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:10px;border-bottom:1px solid #eef2f6;text-align:left;font-size:14px}
th{background:#f8fafc;color:#111;text-transform:uppercase;font-size:12px}
tr:hover td{background:#fbfcfd}
.badge{display:inline-block;padding:6px 8px;border-radius:999px;font-weight:700;font-size:12px}
.badge.active{background:rgba(16,185,129,0.12);color:var(--green)}
.badge.overdue{background:rgba(239,68,68,0.08);color:var(--red)}
.badge.none{background:rgba(245,158,11,0.08);color:var(--orange)}
.meta{color:var(--muted);font-size:13px}
.action-links a{margin-right:8px;text-decoration:none;color:#2563eb;font-weight:600}
.small{font-size:13px;color:var(--muted)}
@media(max-width:800px){
  .header{flex-direction:column;align-items:flex-start;gap:10px}
  th,td{font-size:13px}
}
.center { text-align:center; color:var(--muted); padding:18px 0; }
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Payment Reminders</h1>
      <div class="controls">
        <a class="btn" href="payments.php">Payments</a>
        <a class="btn ghost" href="reminder.php?filter=all">All customers</a>
        <a class="btn ghost" href="reminder.php?filter=overdue">Overdue / No payment</a>
      </div>
    </div>

    <div class="card">
      <p class="meta">This shows each customer's last internet payment, the next due date (30 days after last), and quick actions to record payment or send a reminder.</p>

      <?php if (empty($rows)): ?>
        <div class="center">No customers found.</div>
      <?php else: ?>
        <table class="table" role="table" aria-label="Customer reminders">
          <thead>
            <tr>
              <th>#</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Last Paid</th>
              <th>Next Due</th>
              <th>Days</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; foreach ($rows as $r):
                $statusClass = $r['status'] === 'Active' ? 'active' : ($r['status'] === 'Overdue' ? 'overdue' : 'none');
                $days = $r['days_left'];
                $daysText = $days === null ? '—' : ($days < 0 ? abs($days).'d overdue' : $days.'d left');
                $last = $r['last_paid'] ?: '—';
                $next = $r['next_due'] ?: '—';

                // prepare SMS & email bodies
                $smsBody = "Hello {$r['name']}, your Nettrack internet payment is due on {$next}. Please pay KES ... Thank you.";
                $emailBody = "Hi {$r['name']},\n\nThis is a reminder that your Nettrack internet payment is due on {$next}.\n\nPlease settle promptly.\n\nThanks,\nNettrack Billing";

                $emailAddr = trim($r['email'] ?? '');
                $mailto = '';
                $gmailUrl = '';
                if ($emailAddr !== '') {
                    // mailto (system handler)
                    $mailto = 'mailto:' . rawurlencode($emailAddr)
                            . '?subject=' . rawurlencode('Nettrack payment reminder')
                            . '&body=' . rawurlencode($emailBody);

                    // Gmail compose (works in browser)
                    $gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1'
                            . '&to=' . rawurlencode($emailAddr)
                            . '&su=' . rawurlencode('Nettrack payment reminder')
                            . '&body=' . rawurlencode($emailBody);
                }

                // SMS link (mobile)
                $smsLink = '';
                if (!empty($r['phone'])) {
                    // use sms: protocol (some clients expect ?body=, some &body=); this is broadly supported on mobile
                    $smsLink = 'sms:' . rawurlencode($r['phone']) . '?body=' . rawurlencode($smsBody);
                }
            ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <div style="font-weight:700"><?= h($r['name']) ?></div>
                <div class="small"><?= h($r['location'] ?? '') ?></div>
              </td>
              <td class="small"><?= h($r['phone'] ?? '-') ?></td>
              <td class="small"><?= $emailAddr ? h($emailAddr) : '<span class="small">No email</span>' ?></td>
              <td><?= h($last) ?></td>
              <td><?= h($next) ?></td>
              <td class="small"><?= h($daysText) ?></td>
              <td><span class="badge <?= $statusClass ?>"><?= h($r['status']) ?></span></td>
              <td class="action-links">
                <?php if ($r['status'] === 'Overdue' || $r['status'] === 'No payment'): ?>
                  <a href="add_payment.php?customer_id=<?= (int)$r['id'] ?>">Record Payment</a> |
                <?php else: ?>
                  <a href="add_payment.php?customer_id=<?= (int)$r['id'] ?>">Quick Pay</a> |
                <?php endif; ?>

                <?php if ($smsLink): ?>
                  <a href="<?= $smsLink ?>">SMS</a> |
                <?php endif; ?>

                <?php if ($emailAddr !== ''): ?>
                  <a href="<?= $mailto ?>">Mail</a> |
                  <a href="<?= $gmailUrl ?>" target="_blank" rel="noopener">Open in Gmail</a>
                <?php else: ?>
                  <span class="small">No email</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

<?php
/**
 * receipt.php
 * Shows the digital receipt after a successful payment.
 * Data comes from $_SESSION['receipt'] set in payment.php.
 */
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['receipt'])) {
    header("Location: index.php");
    exit();
}

$r = $_SESSION['receipt'];

// Clear receipt from session after displaying so refreshing won't re-show it
// (we keep it available just for this one load)
$method_labels = ['card' => 'Credit / Debit Card', 'ewallet' => 'E-Wallet'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Booking Confirmed | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .receipt-wrapper { max-width: 560px; margin: 0 auto; }

    /* Success badge */
    .success-badge { text-align: center; margin-bottom: 24px; }
    .success-badge .check { width: 60px; height: 60px; border-radius: 50%; background: #dcfce7; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 10px; }
    .success-badge h2 { margin: 0; font-size: 24px; font-weight: 800; color: #166534; }
    .success-badge p { margin: 6px 0 0; color: #64748b; font-size: 14px; }

    /* Receipt card */
    .receipt-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; box-shadow: 0 6px 22px rgba(15,23,42,0.06); }

    /* Reservation number highlight */
    .rsvp-number { background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; text-align: center; margin-bottom: 20px; }
    .rsvp-number .label { font-size: 12px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; }
    .rsvp-number .number { font-size: 26px; font-weight: 800; color: #0b58ff; letter-spacing: 2px; margin-top: 4px; }

    /* Access code */
    .access-box { background: #fefce8; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 16px; text-align: center; margin-bottom: 20px; }
    .access-box .label { font-size: 12px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; }
    .access-box .code { font-size: 30px; font-weight: 800; color: #b45309; letter-spacing: 6px; margin-top: 4px; font-family: monospace; }
    .access-box .hint { font-size: 12px; color: #92400e; margin-top: 6px; }

    /* Row details */
    .detail-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .label { color: #64748b; }
    .detail-row .value { font-weight: 600; color: #0f172a; text-align: right; }

    /* Total */
    .total-row { margin-top: 14px; background: #f0f7ff; border-radius: 10px; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
    .total-row .label { font-size: 15px; font-weight: 700; color: #0f172a; }
    .total-row .amount { font-size: 26px; font-weight: 800; color: #0b58ff; }

    /* Divider */
    .divider { border: none; border-top: 1px dashed #e5e7eb; margin: 18px 0; }

    /* Buttons */
    .btn-row { display: flex; gap: 12px; margin-top: 24px; }
    .btn-primary { flex: 1; padding: 12px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .btn-outline { flex: 1; padding: 12px; border-radius: 10px; background: #fff; color: #0b58ff; font-weight: 700; font-size: 14px; border: 2px solid #bfdbfe; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .btn-outline:hover { background: #eff6ff; }
    @media print {
      .no-print { display: none; }
      .page { background: #fff; padding: 0; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <?php include 'navbar_client.php'; ?>
</div>

<div class="page">
  <div class="container receipt-wrapper">

    <!-- Success header -->
    <div class="success-badge">
      <div class="check">✓</div>
      <h2>Booking Confirmed!</h2>
      <p>Your locker has been reserved. Save your reservation number below.</p>
    </div>

    <div class="receipt-card">

      <!-- Reservation Number -->
      <div class="rsvp-number">
        <div class="label">Reservation Number</div>
        <div class="number"><?php echo htmlspecialchars($r['reservation_number']); ?></div>
      </div>

      <!-- Access Code -->
      <div class="access-box">
        <div class="label">🔐 Locker Access Code</div>
        <div class="code"><?php echo htmlspecialchars($r['access_code']); ?></div>
        <div class="hint">Show this code at check-in or use it to open your locker.</div>
      </div>

      <!-- Booking details -->
      <div class="detail-row">
        <span class="label">Guest Name</span>
        <span class="value"><?php echo htmlspecialchars($r['full_name']); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Locker</span>
        <span class="value">#<?php echo $r['locker_id']; ?> — <?php echo htmlspecialchars($r['locker_size']); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Location</span>
        <span class="value"><?php echo htmlspecialchars($r['location']); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Check-in</span>
        <span class="value"><?php echo date('M d, Y  h:i A', strtotime($r['start_time'])); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Check-out</span>
        <span class="value"><?php echo date('M d, Y  h:i A', strtotime($r['end_time'])); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Rate Type</span>
        <span class="value"><?php echo ucfirst($r['rate_type']); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Duration</span>
        <span class="value">
          <?php
            echo $r['duration'] . ' ' . ($r['rate_type'] == 'daily' ? 'day' : 'hour');
            echo $r['duration'] > 1 ? 's' : '';
            if ($r['rate_type'] == 'daily') echo ' (' . $r['hours_total'] . ' hrs)';
          ?>
        </span>
      </div>

      <hr class="divider">

      <!-- Payment details -->
      <div class="detail-row">
        <span class="label">Payment Method</span>
        <span class="value"><?php echo $method_labels[$r['payment_method']]; ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Transaction Ref</span>
        <span class="value" style="font-family:monospace; font-size:13px;"><?php echo strtoupper($r['tx_ref']); ?></span>
      </div>
      <div class="detail-row">
        <span class="label">Payment Status</span>
        <span class="value" style="color:#16a34a;">✓ Paid</span>
      </div>

      <div class="total-row">
        <span class="label">Total Paid</span>
        <span class="amount">₱<?php echo number_format($r['total_price'], 2); ?></span>
      </div>

      <!-- Action buttons -->
      <div class="btn-row no-print">
        <a class="btn-primary" href="reservations.php">View My Reservations</a>
        <a class="btn-outline" href="#" onclick="window.print(); return false;">🖨 Print Receipt</a>
      </div>

    </div>

  </div>
</div>

<?php
// Clear receipt after display so refreshing goes somewhere sensible
unset($_SESSION['receipt']);
?>
</body>
</html>
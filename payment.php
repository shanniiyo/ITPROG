<?php
/**
 * payment.php
 * Dummy payment page.
 * Reads pending_reservation from session, shows order summary,
 * accepts a dummy card/ewallet, then creates the reservation + payment records
 * and generates a unique reservation number.
 */
session_start();
include 'db_connect.php';
include 'mailer.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Must have a pending reservation in session
if (!isset($_SESSION['pending_reservation'])) {
    header("Location: search.php");
    exit();
}

$pend     = $_SESSION['pending_reservation'];
$user_id  = $_SESSION['user_id'];

// Fetch locker info again
$locker_id = (int) $pend['locker_id'];
$lsql      = "SELECT * FROM locker_rsvp WHERE locker_id=$locker_id LIMIT 1";
$lresult   = mysqli_query($conn, $lsql);
$locker    = mysqli_fetch_assoc($lresult);

$size_labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
$error = '';

// --- Handle payment form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method']; // 'card' or 'ewallet'

    // Basic validation (dummy — we just check fields are filled)
    $valid = true;
    if ($payment_method == 'card') {
        if (empty(trim($_POST['card_number'])) || empty(trim($_POST['card_name'])) ||
            empty(trim($_POST['card_expiry'])) || empty(trim($_POST['card_cvv']))) {
            $error = "Please fill in all card details.";
            $valid = false;
        }
    } else {
        if (empty(trim($_POST['ewallet_number']))) {
            $error = "Please enter your e-wallet number.";
            $valid = false;
        }
    }

    if ($valid) {
        // --- Create the reservation ---
        $start_time     = $pend['start_time'];
        $end_time       = $pend['end_time'];
        $duration       = (int) $pend['duration'];
        $hours_total    = (int) $pend['hours_total'];
        $rate_type      = $pend['rate_type'];
        $total_price    = (float) $pend['total_price'];

        $rsvp_sql = "INSERT INTO rsvp_details
                        (locker_id, user_id, start_time, end_time, duration_select, extn_count, status, penalty_fee)
                     VALUES
                        ($locker_id, $user_id, '$start_time', '$end_time', $hours_total, 0, 'active', 0.00)";

        if (!mysqli_query($conn, $rsvp_sql)) {
            $error = "Could not create reservation: " . mysqli_error($conn);
        } else {
            $rsvp_id = mysqli_insert_id($conn);

            // --- Generate unique reservation number ---
            // Format: SL-YYYY-XXXXXX (year + zero-padded rsvp_id)
            $reservation_number = 'SL-' . date('Y') . '-' . str_pad($rsvp_id, 6, '0', STR_PAD_LEFT);

            // --- Generate access code (6-char alphanumeric) ---
            $access_code = strtoupper(substr(md5(uniqid($rsvp_id, true)), 0, 6));

            // Insert access code
            $ac_sql = "INSERT INTO locker_access (rsvp_id, access_code) VALUES ($rsvp_id, '$access_code')";
            mysqli_query($conn, $ac_sql);

            // --- Create payment record ---
            $tx_ref  = strtoupper(substr(md5(uniqid('pay', true)), 0, 12));
            $pay_sql = "INSERT INTO payments
                            (rsvp_id, amount_paid, payment_method, payment_status, transaction_ref, rate_type)
                        VALUES
                            ($rsvp_id, $total_price, '$payment_method', 'paid', '$tx_ref', '$rate_type')";
            mysqli_query($conn, $pay_sql);

            // --- Mark locker as occupied ---
            mysqli_query($conn, "UPDATE locker_rsvp SET status='occupied' WHERE locker_id=$locker_id");

            // Save receipt info to session then clear pending
            $_SESSION['receipt'] = [
                'rsvp_id'            => $rsvp_id,
                'reservation_number' => $reservation_number,
                'access_code'        => $access_code,
                'locker_id'          => $locker_id,
                'locker_size'        => $size_labels[$locker['size']],
                'location'           => $locker['location'],
                'start_time'         => $start_time,
                'end_time'           => $end_time,
                'rate_type'          => $rate_type,
                'duration'           => $duration,
                'hours_total'        => $hours_total,
                'total_price'        => $total_price,
                'payment_method'     => $payment_method,
                'tx_ref'             => $tx_ref,
                'full_name'          => $_SESSION['full_name'],
            ];
            unset($_SESSION['pending_reservation']);

            // --- Send simulated confirmation email ---
            // Fetch the user's email
            $user_res  = mysqli_query($conn, "SELECT email FROM users WHERE user_id=$user_id LIMIT 1");
            $user_row  = mysqli_fetch_assoc($user_res);
            $user_email = $user_row['email'];

            notify_confirmation(
                $conn,
                $user_id,
                $rsvp_id,
                $user_email,
                $_SESSION['full_name'],
                $reservation_number,
                $locker_id,
                $size_labels[$locker['size']],
                $locker['location'],
                date('M d, Y h:i A', strtotime($start_time)),
                date('M d, Y h:i A', strtotime($end_time)),
                number_format($total_price, 2),
                $access_code
            );

            header("Location: receipt.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .pay-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 900px; margin: 0 auto; }
    @media(max-width: 700px) { .pay-grid { grid-template-columns: 1fr; } }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; box-shadow: 0 6px 22px rgba(15,23,42,0.06); }
    .card h2 { margin: 0 0 20px; font-size: 20px; font-weight: 800; color: #0f172a; }
    /* Summary rows */
    .summary-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .summary-row:last-child { border-bottom: none; }
    .summary-row .label { color: #64748b; }
    .summary-row .value { font-weight: 600; color: #0f172a; }
    .total-row { margin-top: 12px; background: #f0f7ff; border-radius: 10px; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
    .total-row .label { font-size: 15px; font-weight: 700; color: #0f172a; }
    .total-row .amount { font-size: 26px; font-weight: 800; color: #0b58ff; }
    /* Payment method tabs */
    .method-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
    .method-tab { padding: 11px; border-radius: 10px; border: 2px solid #e5e7eb; background: #f8fafc; text-align: center; cursor: pointer; font-size: 14px; font-weight: 600; color: #475569; }
    .method-tab.active { border-color: #0b58ff; background: #eff6ff; color: #0b58ff; }
    /* Form */
    .form__label { display: block; margin: 14px 0 6px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
    .card-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-pay { width: 100%; padding: 14px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 15px; border: none; cursor: pointer; margin-top: 20px; }
    .btn-pay:hover { opacity: 0.9; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
    .dummy-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #92400e; margin-bottom: 16px; }
    .page__header { text-align: center; margin-bottom: 24px; }
    .page__title { margin: 0; font-size: 28px; font-weight: 800; color: #0f172a; }
    .page__subtitle { margin: 6px 0 0; color: #64748b; }
  </style>
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="page">
  <div class="container">

    <div class="page__header">
      <h1 class="page__title">Confirm &amp; Pay</h1>
      <p class="page__subtitle">Review your reservation before paying.</p>
    </div>

    <?php if ($error): ?>
      <div style="max-width:900px; margin: 0 auto 16px;">
        <div class="alert--error"><?php echo htmlspecialchars($error); ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="" id="payForm">
    <div class="pay-grid">

      <!-- LEFT: Order Summary -->
      <div class="card">
        <h2>📋 Reservation Summary</h2>

        <div class="summary-row">
          <span class="label">Locker</span>
          <span class="value">#<?php echo $locker['locker_id']; ?> — <?php echo $size_labels[$locker['size']]; ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Location</span>
          <span class="value"><?php echo htmlspecialchars($locker['location']); ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Rate Type</span>
          <span class="value"><?php echo ucfirst($pend['rate_type']); ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Duration</span>
          <span class="value">
            <?php
              echo $pend['duration'] . ' ' . ($pend['rate_type'] == 'daily' ? 'day' : 'hour');
              echo $pend['duration'] > 1 ? 's' : '';
              if ($pend['rate_type'] == 'daily') echo ' (' . $pend['hours_total'] . ' hrs)';
            ?>
          </span>
        </div>
        <div class="summary-row">
          <span class="label">Check-in</span>
          <span class="value"><?php echo date('M d, Y  h:i A', strtotime($pend['start_time'])); ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Check-out</span>
          <span class="value"><?php echo date('M d, Y  h:i A', strtotime($pend['end_time'])); ?></span>
        </div>
        <div class="summary-row">
          <span class="label">Price/Hour</span>
          <span class="value">₱<?php echo number_format($locker['pricer_per_hr'], 2); ?></span>
        </div>

        <div class="total-row">
          <span class="label">Total Amount</span>
          <span class="amount">₱<?php echo number_format($pend['total_price'], 2); ?></span>
        </div>
      </div>

      <!-- RIGHT: Payment Form -->
      <div class="card">
        <h2>💳 Payment Details</h2>

        <div class="dummy-note">
          🧪 <strong>Demo mode:</strong> This is a simulated payment. No real charges will be made. Enter any values below.
        </div>

        <!-- Payment method selector -->
        <div class="method-tabs">
          <div class="method-tab active" id="tab-card" onclick="setMethod('card')">💳 Credit / Debit Card</div>
          <div class="method-tab" id="tab-ewallet" onclick="setMethod('ewallet')">📱 E-Wallet</div>
        </div>
        <input type="hidden" name="payment_method" id="payment_method" value="card">

        <!-- Card fields -->
        <div id="card-fields">
          <label class="form__label">Cardholder Name</label>
          <input class="form__control" type="text" name="card_name" placeholder="Juan dela Cruz">

          <label class="form__label">Card Number</label>
          <input class="form__control" type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">

          <div class="card-row">
            <div>
              <label class="form__label">Expiry Date</label>
              <input class="form__control" type="text" name="card_expiry" placeholder="MM/YY" maxlength="5">
            </div>
            <div>
              <label class="form__label">CVV</label>
              <input class="form__control" type="text" name="card_cvv" placeholder="123" maxlength="4">
            </div>
          </div>
        </div>

        <!-- E-wallet fields -->
        <div id="ewallet-fields" style="display:none;">
          <label class="form__label">E-Wallet Number</label>
          <input class="form__control" type="text" name="ewallet_number" placeholder="09XX XXX XXXX">
        </div>

        <button class="btn-pay" type="submit">
          Pay ₱<?php echo number_format($pend['total_price'], 2); ?> Now
        </button>
      </div>

    </div>
    </form>

  </div>
</div>

<script>
  function setMethod(type) {
    document.getElementById('payment_method').value = type;
    if (type === 'card') {
      document.getElementById('tab-card').classList.add('active');
      document.getElementById('tab-ewallet').classList.remove('active');
      document.getElementById('card-fields').style.display = 'block';
      document.getElementById('ewallet-fields').style.display = 'none';
    } else {
      document.getElementById('tab-ewallet').classList.add('active');
      document.getElementById('tab-card').classList.remove('active');
      document.getElementById('ewallet-fields').style.display = 'block';
      document.getElementById('card-fields').style.display = 'none';
    }
  }
</script>

</body>
</html>
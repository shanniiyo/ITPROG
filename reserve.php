<?php
/**
 * reserve.php
 * Step 1: Customer fills in duration, rate type, start time.
 * Auto-calculates price and shows a summary before going to payment.
 *
 * Rules:
 * - Must be at least 24 hours in advance.
 * - Rate type: hourly or daily.
 * - Daily = 24 hours worth of the hourly rate.
 */
session_start();
include 'db_connect.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Must have a locker_id passed in from search.php
if (!isset($_GET['locker_id'])) {
    header("Location: search.php");
    exit();
}

$locker_id = (int) $_GET['locker_id'];

// Fetch locker details
$sql    = "SELECT * FROM locker_rsvp WHERE locker_id=$locker_id AND status='available' AND is_active=1 LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Locker not found or not available. <a href='search.php'>Go back</a></p>";
    exit();
}

$locker = mysqli_fetch_assoc($result);

// Size label map
$size_labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];

$error = '';

// --- Handle form submission (show summary) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $rate_type = $_POST['rate_type'];           // 'hourly' or 'daily'
    $duration  = (int) $_POST['duration'];      // number of hours or days
    $start_str = trim($_POST['start_time']);     // datetime-local string

    // Validate
    if ($duration < 1) {
        $error = "Duration must be at least 1.";
    } else {
        $start_ts = strtotime($start_str);

        // Must be at least 24 hours from now
        if ($start_ts < strtotime('+24 hours')) {
            $error = "Reservations must be made at least 24 hours in advance.";
        } else {
            // Calculate end time
            if ($rate_type == 'daily') {
                $hours_total = $duration * 24;
            } else {
                $hours_total = $duration;
            }

            $end_ts  = $start_ts + ($hours_total * 3600);
            $end_str = date('Y-m-d H:i:s', $end_ts);

            // Check for conflicting reservations on the same locker
            $start_db = date('Y-m-d H:i:s', $start_ts);
            $conflict_sql = "SELECT rsvp_id FROM rsvp_details
                             WHERE locker_id=$locker_id
                               AND status IN ('active')
                               AND NOT (end_time <= '$start_db' OR start_time >= '$end_str')
                             LIMIT 1";
            $conflict_res = mysqli_query($conn, $conflict_sql);

            if (mysqli_num_rows($conflict_res) > 0) {
                $error = "That time slot is already taken. Please choose a different start time or locker.";
            } else {
                // Calculate price
                $price_per_hr = (float) $locker['pricer_per_hr'];
                $total_price  = $price_per_hr * $hours_total;

                // Store in session for payment page
                $_SESSION['pending_reservation'] = [
                    'locker_id'   => $locker_id,
                    'rate_type'   => $rate_type,
                    'duration'    => $duration,
                    'hours_total' => $hours_total,
                    'start_time'  => $start_db,
                    'end_time'    => $end_str,
                    'total_price' => $total_price,
                ];

                // Redirect to payment
                header("Location: payment.php");
                exit();
            }
        }
    }
}

// Minimum selectable start time = now + 24h (for the datetime-local input min attribute)
$min_start = date('Y-m-d\TH:i', strtotime('+24 hours'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reserve Locker | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .reserve-wrapper { max-width: 580px; margin: 0 auto; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px; box-shadow: 0 6px 22px rgba(15,23,42,0.06); }
    .card h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; color: #0f172a; }
    .card p.sub { margin: 0 0 20px; color: #64748b; font-size: 14px; }
    /* Locker info box */
    .locker-info { background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
    .locker-info .li-name { font-weight: 700; color: #0f172a; font-size: 15px; }
    .locker-info .li-meta { font-size: 13px; color: #475569; margin-top: 2px; }
    .locker-info .li-price { font-size: 18px; font-weight: 800; color: #0b58ff; }
    /* Form */
    .form__label { display: block; margin: 16px 0 6px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
    /* Rate type toggle */
    .rate-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 4px; }
    .rate-btn { padding: 10px; border-radius: 10px; border: 2px solid #e5e7eb; background: #f8fafc; text-align: center; cursor: pointer; font-size: 14px; font-weight: 600; color: #475569; user-select: none; }
    .rate-btn.active { border-color: #0b58ff; background: #eff6ff; color: #0b58ff; }
    /* Price preview */
    .price-preview { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; margin-top: 20px; }
    .price-preview p { margin: 0; font-size: 14px; color: #166534; }
    .price-preview .total { font-size: 24px; font-weight: 800; color: #15803d; margin-top: 4px; }
    /* Button */
    .btn-reserve { width: 100%; padding: 13px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 15px; border: none; cursor: pointer; margin-top: 20px; }
    .btn-reserve:hover { opacity: 0.9; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
    .back-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #3b82f6; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="page">
  <div class="container reserve-wrapper">
    <div class="card">
      <h2>Reserve a Locker</h2>
      <p class="sub">Choose your rental duration and start time.</p>

      <!-- Locker summary box -->
      <div class="locker-info">
        <div>
          <div class="li-name">Locker #<?php echo $locker['locker_id']; ?> &mdash; <?php echo $size_labels[$locker['size']]; ?></div>
          <div class="li-meta">📍 <?php echo htmlspecialchars($locker['location']); ?></div>
        </div>
        <div class="li-price">₱<?php echo number_format($locker['pricer_per_hr'], 2); ?>/hr</div>
      </div>

      <?php if ($error): ?>
        <div class="alert--error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="" id="reserveForm">

        <!-- Rate Type -->
        <label class="form__label">Rate Type</label>
        <div class="rate-toggle">
          <div class="rate-btn active" id="btn-hourly" onclick="setRate('hourly')">⏱ Hourly</div>
          <div class="rate-btn" id="btn-daily" onclick="setRate('daily')">📅 Daily</div>
        </div>
        <input type="hidden" name="rate_type" id="rate_type" value="hourly">

        <!-- Duration -->
        <label class="form__label" id="duration-label">Number of Hours</label>
        <input class="form__control" type="number" name="duration" id="duration"
               min="1" value="1" required>

        <!-- Start Time -->
        <label class="form__label">Start Date &amp; Time</label>
        <input class="form__control" type="datetime-local" name="start_time" id="start_time"
               min="<?php echo $min_start; ?>" required>

        <!-- Live price preview -->
        <div class="price-preview" id="price-preview">
          <p>Estimated Total</p>
          <div class="total" id="price-total">₱<?php echo number_format($locker['pricer_per_hr'], 2); ?></div>
          <p id="price-breakdown" style="font-size:12px; margin-top:4px;">1 hour × ₱<?php echo number_format($locker['pricer_per_hr'], 2); ?></p>
        </div>

        <button class="btn-reserve" type="submit">Proceed to Payment →</button>
      </form>

      <a class="back-link" href="search.php">← Back to Search</a>
    </div>
  </div>
</div>

<script>
  const pricePerHr = <?php echo (float)$locker['pricer_per_hr']; ?>;

  function setRate(type) {
    document.getElementById('rate_type').value = type;
    if (type === 'hourly') {
      document.getElementById('btn-hourly').classList.add('active');
      document.getElementById('btn-daily').classList.remove('active');
      document.getElementById('duration-label').textContent = 'Number of Hours';
    } else {
      document.getElementById('btn-daily').classList.add('active');
      document.getElementById('btn-hourly').classList.remove('active');
      document.getElementById('duration-label').textContent = 'Number of Days';
    }
    updatePrice();
  }

  function updatePrice() {
    const rateType = document.getElementById('rate_type').value;
    const duration = parseInt(document.getElementById('duration').value) || 1;
    const hours    = rateType === 'daily' ? duration * 24 : duration;
    const total    = (pricePerHr * hours).toFixed(2);
    const unit     = rateType === 'daily' ? 'day' : 'hour';

    document.getElementById('price-total').textContent = '₱' + parseFloat(total).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById('price-breakdown').textContent =
      duration + ' ' + unit + (duration > 1 ? 's' : '') +
      (rateType === 'daily' ? ' (' + hours + ' hours)' : '') +
      ' × ₱' + pricePerHr.toFixed(2) + '/hr';
  }

  document.getElementById('duration').addEventListener('input', updatePrice);
</script>

</body>
</html>
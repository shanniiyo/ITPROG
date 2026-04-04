<?php
/**
 * reservations.php
 * Shows the logged-in user's current/upcoming reservations.
 * - Cancel: allowed only if check-in is MORE than 12 hours away.
 * - Extend: allowed only on 'active' reservations. Extends by extra hours/days.
 *
 * Business rules:
 * - Past reservations are NOT shown (per project spec).
 * - Cancel = mark as 'cancelled', free up locker.
 * - No refund if cancellation is within 12 hours of check-in.
 * - Extend checks that the locker is free for the extended period.
 */
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// ---- CANCEL ACTION ----
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['rsvp_id'])) {
    $rsvp_id = (int) $_GET['rsvp_id'];

    // Fetch the reservation to verify it belongs to this user
    $check_sql = "SELECT rd.*, lr.locker_id as lid
                  FROM rsvp_details rd
                  JOIN locker_rsvp lr ON rd.locker_id = lr.locker_id
                  WHERE rd.rsvp_id=$rsvp_id AND rd.user_id=$user_id LIMIT 1";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) == 1) {
        $rsvp = mysqli_fetch_assoc($check_res);
        $start_ts = strtotime($rsvp['start_time']);
        $now_ts   = time();
        $diff_hrs = ($start_ts - $now_ts) / 3600;

        if ($rsvp['status'] == 'active' && $diff_hrs > 12) {
            // Cancel allowed — free up locker
            mysqli_query($conn, "UPDATE rsvp_details SET status='cancelled' WHERE rsvp_id=$rsvp_id");
            mysqli_query($conn, "UPDATE locker_rsvp SET status='available' WHERE locker_id=" . $rsvp['locker_id']);
            $message  = "Reservation cancelled successfully. Your locker has been released.";
            $msg_type = 'success';
        } elseif ($diff_hrs <= 12 && $diff_hrs > 0) {
            $message  = "Cancellation not allowed — check-in is within 12 hours. No refund will be issued.";
            $msg_type = 'error';
        } else {
            $message  = "This reservation cannot be cancelled.";
            $msg_type = 'error';
        }
    }
}

// ---- EXTEND FORM SUBMIT ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'extend') {
    $rsvp_id    = (int) $_POST['rsvp_id'];
    $extra      = (int) $_POST['extra_duration'];
    $rate_type  = $_POST['extend_rate_type']; // 'hourly' or 'daily'

    // Fetch reservation
    $fetch_sql = "SELECT rd.*, lr.pricer_per_hr
                  FROM rsvp_details rd
                  JOIN locker_rsvp lr ON rd.locker_id = lr.locker_id
                  WHERE rd.rsvp_id=$rsvp_id AND rd.user_id=$user_id AND rd.status='active' LIMIT 1";
    $fetch_res = mysqli_query($conn, $fetch_sql);

    if (mysqli_num_rows($fetch_res) == 1 && $extra >= 1) {
        $rsvp = mysqli_fetch_assoc($fetch_res);

        $extra_hours  = ($rate_type == 'daily') ? $extra * 24 : $extra;
        $new_end_ts   = strtotime($rsvp['end_time']) + ($extra_hours * 3600);
        $new_end_str  = date('Y-m-d H:i:s', $new_end_ts);
        $old_end_str  = $rsvp['end_time'];
        $locker_id    = $rsvp['locker_id'];

        // Check no conflict in the extended window
        $conflict_sql = "SELECT rsvp_id FROM rsvp_details
                         WHERE locker_id=$locker_id AND rsvp_id != $rsvp_id
                           AND status IN ('active')
                           AND NOT (end_time <= '$old_end_str' OR start_time >= '$new_end_str')
                         LIMIT 1";
        $conflict_res = mysqli_query($conn, $conflict_sql);

        if (mysqli_num_rows($conflict_res) > 0) {
            $message  = "Cannot extend — the locker is reserved by someone else during that time.";
            $msg_type = 'error';
        } else {
            // Update end time and extension count
            $new_count = (int)$rsvp['extn_count'] + 1;
            mysqli_query($conn, "UPDATE rsvp_details SET end_time='$new_end_str', extn_count=$new_count WHERE rsvp_id=$rsvp_id");

            // Add a payment record for the extension cost
            $price_per_hr = (float) $rsvp['pricer_per_hr'];
            $ext_cost     = $price_per_hr * $extra_hours;
            $tx_ref       = strtoupper(substr(md5(uniqid('ext', true)), 0, 12));
            $pay_sql      = "INSERT INTO payments (rsvp_id, amount_paid, payment_method, payment_status, transaction_ref, rate_type)
                             VALUES ($rsvp_id, $ext_cost, 'card', 'paid', '$tx_ref', '$rate_type')";
            mysqli_query($conn, $pay_sql);

            $message  = "Reservation extended to " . date('M d, Y h:i A', $new_end_ts) . ". Additional charge: ₱" . number_format($ext_cost, 2);
            $msg_type = 'success';
        }
    } else {
        $message  = "Could not extend reservation. Make sure the extra duration is at least 1.";
        $msg_type = 'error';
    }
}

// ---- FETCH USER'S RESERVATIONS (active only — no past history per spec) ----
$rsvp_sql = "SELECT rd.*, lr.size, lr.location, lr.pricer_per_hr,
                    la.access_code,
                    p.transaction_ref, p.payment_method, p.amount_paid
             FROM rsvp_details rd
             JOIN locker_rsvp lr ON rd.locker_id = lr.locker_id
             LEFT JOIN locker_access la ON la.rsvp_id = rd.rsvp_id
             LEFT JOIN payments p ON p.rsvp_id = rd.rsvp_id
             WHERE rd.user_id=$user_id
               AND rd.status IN ('active', 'cancelled')
             ORDER BY rd.created_at DESC";

// Note: 'active' = upcoming or currently checked-in; 'cancelled' shown so user
// can see they cancelled. 'completed'/'expired' are not shown per spec.
$rsvp_res = mysqli_query($conn, $rsvp_sql);

$size_labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
$method_labels = ['card' => 'Card', 'ewallet' => 'E-Wallet'];

// Status badge colours
function status_badge($status) {
    $map = [
        'active'    => 'badge--green',
        'cancelled' => 'badge--red',
        'completed' => 'badge--gray',
        'expired'   => 'badge--gray',
    ];
    return $map[$status] ?? 'badge--gray';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Reservations | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .page__header { text-align: center; margin-bottom: 28px; }
    .page__title { margin: 0; font-size: 30px; font-weight: 800; color: #0f172a; }
    .page__subtitle { margin: 8px 0 0; color: #64748b; }

    .alert { padding: 13px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
    .alert--success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

    /* Reservation card */
    .rsvp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 22px; margin-bottom: 16px; box-shadow: 0 4px 14px rgba(15,23,42,0.05); }
    .rsvp-card__top { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
    .rsvp-num { font-size: 18px; font-weight: 800; color: #0b58ff; }
    .rsvp-locker { font-size: 14px; color: #475569; margin-top: 2px; }

    /* Badge */
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
    .badge--green  { background: #dcfce7; color: #166534; }
    .badge--red    { background: #fee2e2; color: #991b1b; }
    .badge--gray   { background: #f1f5f9; color: #475569; }

    /* Detail grid */
    .rsvp-details { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; margin-bottom: 16px; }
    .detail-item { background: #f8fafc; border-radius: 10px; padding: 10px 12px; }
    .detail-item .di-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
    .detail-item .di-value { font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 2px; }

    /* Access code */
    .access-pill { display: inline-flex; align-items: center; gap: 8px; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 6px 12px; font-size: 13px; color: #92400e; font-family: monospace; font-weight: 700; letter-spacing: 3px; }

    /* Action buttons */
    .rsvp-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .btn-cancel { padding: 9px 16px; border-radius: 9px; background: #fff; color: #ef4444; border: 2px solid #fca5a5; font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: none; }
    .btn-cancel:hover { background: #fef2f2; }
    .btn-extend { padding: 9px 16px; border-radius: 9px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; border: none; font-weight: 700; font-size: 13px; cursor: pointer; }
    .btn-extend:hover { opacity: 0.9; }

    /* Extend form (inline, hidden by default) */
    .extend-form { margin-top: 14px; padding: 16px; background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 12px; display: none; }
    .extend-form h4 { margin: 0 0 12px; font-size: 14px; font-weight: 700; color: #0f172a; }
    .extend-form .ef-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .ef-group { display: flex; flex-direction: column; gap: 4px; }
    .ef-group label { font-size: 12px; font-weight: 700; color: #0f172a; }
    .ef-group select, .ef-group input { padding: 9px 10px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 14px; background: #fff; outline: none; }
    .btn-confirm-extend { padding: 9px 18px; border-radius: 9px; background: #0b58ff; color: #fff; border: none; font-weight: 700; font-size: 13px; cursor: pointer; }

    .no-rsvp { text-align: center; padding: 60px 20px; color: #64748b; }
    .no-rsvp h3 { font-size: 18px; color: #0f172a; margin-bottom: 8px; }
    .no-rsvp a { color: #3b82f6; text-decoration: none; font-weight: 600; }
  </style>
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="page">
  <div class="container">

    <div class="page__header">
      <h1 class="page__title">My Reservations</h1>
      <p class="page__subtitle">Manage your active locker bookings.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert--<?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($rsvp_res) == 0): ?>
      <div class="no-rsvp">
        <h3>No reservations yet</h3>
        <p>You haven't reserved any lockers. <a href="search.php">Find a locker now →</a></p>
      </div>
    <?php else: ?>
      <?php while ($row = mysqli_fetch_assoc($rsvp_res)): ?>
        <?php
          $rsvp_id   = $row['rsvp_id'];
          $start_ts  = strtotime($row['start_time']);
          $now_ts    = time();
          $diff_hrs  = ($start_ts - $now_ts) / 3600;
          $can_cancel = ($row['status'] == 'active' && $diff_hrs > 12);
          $can_extend = ($row['status'] == 'active');
          // Generate reservation number from rsvp_id
          $rsvp_num  = 'SL-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($rsvp_id, 6, '0', STR_PAD_LEFT);
        ?>
        <div class="rsvp-card">
          <div class="rsvp-card__top">
            <div>
              <div class="rsvp-num"><?php echo $rsvp_num; ?></div>
              <div class="rsvp-locker">
                Locker #<?php echo $row['locker_id']; ?> —
                <?php echo $size_labels[$row['size']]; ?> |
                📍 <?php echo htmlspecialchars($row['location']); ?>
              </div>
            </div>
            <span class="badge <?php echo status_badge($row['status']); ?>">
              <?php echo ucfirst($row['status']); ?>
            </span>
          </div>

          <!-- Details grid -->
          <div class="rsvp-details">
            <div class="detail-item">
              <div class="di-label">Check-in</div>
              <div class="di-value"><?php echo date('M d, Y h:i A', strtotime($row['start_time'])); ?></div>
            </div>
            <div class="detail-item">
              <div class="di-label">Check-out</div>
              <div class="di-value"><?php echo date('M d, Y h:i A', strtotime($row['end_time'])); ?></div>
            </div>
            <div class="detail-item">
              <div class="di-label">Duration</div>
              <div class="di-value"><?php echo $row['duration_select']; ?> hr(s)</div>
            </div>
            <div class="detail-item">
              <div class="di-label">Amount Paid</div>
              <div class="di-value">₱<?php echo number_format($row['amount_paid'], 2); ?></div>
            </div>
            <div class="detail-item">
              <div class="di-label">Payment</div>
              <div class="di-value"><?php echo $method_labels[$row['payment_method']] ?? '—'; ?></div>
            </div>
            <div class="detail-item">
              <div class="di-label">Extensions</div>
              <div class="di-value"><?php echo (int)$row['extn_count']; ?></div>
            </div>
          </div>

          <!-- Access Code -->
          <?php if ($row['access_code']): ?>
            <div>
              <span style="font-size:12px; font-weight:700; color:#64748b;">🔐 Access Code:</span>
              <span class="access-pill"><?php echo htmlspecialchars($row['access_code']); ?></span>
            </div>
          <?php endif; ?>

          <!-- Action buttons -->
          <?php if ($row['status'] == 'active'): ?>
          <div class="rsvp-actions">

            <?php if ($can_cancel): ?>
              <a class="btn-cancel"
                 href="reservations.php?action=cancel&rsvp_id=<?php echo $rsvp_id; ?>"
                 onclick="return confirm('Are you sure you want to cancel this reservation?');">
                ✕ Cancel Reservation
              </a>
            <?php elseif ($diff_hrs > 0): ?>
              <span style="font-size:13px; color:#ef4444;">
                ⚠ Cancellation not available — within 12 hours of check-in (no refund).
              </span>
            <?php endif; ?>

            <?php if ($can_extend): ?>
              <button class="btn-extend"
                      onclick="toggleExtend(<?php echo $rsvp_id; ?>)">
                ⏱ Extend Reservation
              </button>
            <?php endif; ?>

          </div>

          <!-- Extend form (hidden by default) -->
          <?php if ($can_extend): ?>
          <div class="extend-form" id="extend-<?php echo $rsvp_id; ?>">
            <h4>Extend Reservation</h4>
            <form method="POST" action="">
              <input type="hidden" name="action" value="extend">
              <input type="hidden" name="rsvp_id" value="<?php echo $rsvp_id; ?>">
              <div class="ef-row">
                <div class="ef-group">
                  <label>Rate Type</label>
                  <select name="extend_rate_type">
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                  </select>
                </div>
                <div class="ef-group">
                  <label>Extra Duration</label>
                  <input type="number" name="extra_duration" min="1" value="1" style="width:80px;">
                </div>
                <div class="ef-group">
                  <button class="btn-confirm-extend" type="submit">Confirm Extension</button>
                </div>
              </div>
              <p style="font-size:12px; color:#3b82f6; margin: 8px 0 0;">
                Current check-out: <?php echo date('M d, Y h:i A', strtotime($row['end_time'])); ?>
              </p>
              <p style="font-size:12px; color:#64748b; margin:4px 0 0;">
                Rate: ₱<?php echo number_format($row['pricer_per_hr'], 2); ?>/hr — additional charges apply.
              </p>
            </form>
          </div>
          <?php endif; ?>
          <?php endif; // active status ?>

        </div>
      <?php endwhile; ?>
    <?php endif; ?>

  </div>
</div>

<script>
function toggleExtend(id) {
  const el = document.getElementById('extend-' + id);
  el.style.display = el.style.display === 'block' ? 'none' : 'block';
}
</script>

</body>
</html>
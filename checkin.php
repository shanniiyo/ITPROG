<?php
/**
 * checkin.php
 * Check-in / Check-out management for Front Desk Staff (and higher roles).
 *
 * Features:
 * - Look up reservation by reservation number (SL-YYYY-XXXXXX) or rsvp_id
 * - View reservation details
 * - Mark reservation as started (check-in) → locker becomes occupied
 * - Mark reservation as completed (check-out) → locker becomes available
 * - Apply late-checkout penalty fee if checked out past end_time
 * - Create walk-in reservations (staff picks locker, duration, start time)
 */
session_start();
include 'db_connect.php';
include 'mailer.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff'])
) {
    echo "<script>
            alert('Access Denied.');
            window.location.href='admin_login.php';
          </script>";
    exit();
}

$role    = $_SESSION['admin_role'];
$message = '';
$msg_type = '';
$found_rsvp = null;

// Late penalty rate: per hour past end_time (configurable here)
define('PENALTY_PER_HOUR', 50.00);

// ============================================================
// WALK-IN RESERVATION (POST) — staff and sys_admin only
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'walkin') {

    if (!in_array($role, ['sys_admin', 'staff'])) {
        $message  = "Access Denied: Only staff and system administrators can create walk-in reservations.";
        $msg_type = 'error';
    } else {

    $locker_id  = (int) $_POST['walkin_locker_id'];
    $user_id    = (int) $_POST['walkin_user_id'];
    $rate_type  = $_POST['walkin_rate_type'];
    $duration   = (int) $_POST['walkin_duration'];
    $start_str  = trim($_POST['walkin_start_time']);
    $pay_method = $_POST['walkin_payment_method'];

    $start_ts    = strtotime($start_str);
    $extra_hours = ($rate_type == 'daily') ? $duration * 24 : $duration;
    $end_ts      = $start_ts + ($extra_hours * 3600);
    $end_str     = date('Y-m-d H:i:s', $end_ts);
    $start_db    = date('Y-m-d H:i:s', $start_ts);

    // Fetch locker price
    $lck = $conn->query("SELECT * FROM locker_rsvp WHERE locker_id=$locker_id AND is_active=1 LIMIT 1")->fetch_assoc();

    if (!$lck) {
        $message = "Locker not found.";
        $msg_type = 'error';
    } else {
        // Check conflict
        $conflict = $conn->query(
            "SELECT rsvp_id FROM rsvp_details
             WHERE locker_id=$locker_id AND status='active'
               AND NOT (end_time <= '$start_db' OR start_time >= '$end_str')
             LIMIT 1"
        );

        if ($conflict->num_rows > 0) {
            $message  = "That time slot conflicts with an existing reservation.";
            $msg_type = 'error';
        } else {
            $total_price = (float)$lck['pricer_per_hr'] * $extra_hours;

            // Insert reservation
            $conn->query(
                "INSERT INTO rsvp_details
                    (locker_id, user_id, start_time, end_time, duration_select, extn_count, status, penalty_fee)
                 VALUES
                    ($locker_id, $user_id, '$start_db', '$end_str', $extra_hours, 0, 'active', 0.00)"
            );
            $rsvp_id = $conn->insert_id;

            // Access code
            $access_code = strtoupper(substr(md5(uniqid($rsvp_id, true)), 0, 6));
            $conn->query("INSERT INTO locker_access (rsvp_id, access_code) VALUES ($rsvp_id, '$access_code')");

            // Payment
            $tx_ref = strtoupper(substr(md5(uniqid('wkin', true)), 0, 12));
            $conn->query(
                "INSERT INTO payments (rsvp_id, amount_paid, payment_method, payment_status, transaction_ref, rate_type)
                 VALUES ($rsvp_id, $total_price, '$pay_method', 'paid', '$tx_ref', '$rate_type')"
            );

            // Mark locker occupied
            $conn->query("UPDATE locker_rsvp SET status='occupied' WHERE locker_id=$locker_id");

            // Reservation number
            $rsvp_num = 'SL-' . date('Y') . '-' . str_pad($rsvp_id, 6, '0', STR_PAD_LEFT);

            // Notify user if they have an account
            $u_res = $conn->query("SELECT email, full_name FROM users WHERE user_id=$user_id LIMIT 1");
            if ($u_res && $u_res->num_rows == 1) {
                $u = $u_res->fetch_assoc();
                $size_labels_map = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
                notify_confirmation(
                    $conn, $user_id, $rsvp_id, $u['email'], $u['full_name'], $rsvp_num,
                    $locker_id, $size_labels_map[$lck['size']], $lck['location'],
                    date('M d, Y h:i A', $start_ts), date('M d, Y h:i A', $end_ts),
                    number_format($total_price, 2), $access_code
                );
            }

            $message  = "Walk-in reservation created! Reservation #: <strong>$rsvp_num</strong> | Access Code: <strong>$access_code</strong> | Total: ₱" . number_format($total_price, 2);
            $msg_type = 'success';
        }
    }
    } // end role check else
}

// ============================================================
// CHECK-IN ACTION
// ============================================================
if (isset($_GET['action']) && $_GET['action'] == 'checkin' && isset($_GET['rsvp_id'])) {
    $rsvp_id = (int) $_GET['rsvp_id'];
    $rsvp    = $conn->query("SELECT * FROM rsvp_details WHERE rsvp_id=$rsvp_id LIMIT 1")->fetch_assoc();

    if ($rsvp && $rsvp['status'] == 'active') {
        // Mark locker as occupied (it already should be, but ensure consistency)
        $conn->query("UPDATE locker_rsvp SET status='occupied' WHERE locker_id=" . $rsvp['locker_id']);
        $message  = "Check-in confirmed for Reservation #SL-" . date('Y', strtotime($rsvp['created_at'])) . "-" . str_pad($rsvp_id, 6, '0', STR_PAD_LEFT) . ".";
        $msg_type = 'success';
    } else {
        $message  = "Cannot check in — reservation not found or not active.";
        $msg_type = 'error';
    }
}

// ============================================================
// CHECK-OUT ACTION
// ============================================================
if (isset($_GET['action']) && $_GET['action'] == 'checkout' && isset($_GET['rsvp_id'])) {
    $rsvp_id = (int) $_GET['rsvp_id'];
    $rsvp    = $conn->query("SELECT * FROM rsvp_details WHERE rsvp_id=$rsvp_id LIMIT 1")->fetch_assoc();

    if ($rsvp && $rsvp['status'] == 'active') {
        $now_ts    = time();
        $end_ts    = strtotime($rsvp['end_time']);
        $late_hrs  = 0;
        $penalty   = 0.00;

        if ($now_ts > $end_ts) {
            $late_hrs = ceil(($now_ts - $end_ts) / 3600);
            $penalty  = $late_hrs * PENALTY_PER_HOUR;
        }

        // Complete the reservation
        $conn->query(
            "UPDATE rsvp_details
             SET status='completed', penalty_fee=$penalty
             WHERE rsvp_id=$rsvp_id"
        );
        // Free locker
        $conn->query("UPDATE locker_rsvp SET status='available' WHERE locker_id=" . $rsvp['locker_id']);

        // Record penalty payment if any
        if ($penalty > 0) {
            $tx_ref = strtoupper(substr(md5(uniqid('pen', true)), 0, 12));
            $conn->query(
                "INSERT INTO payments (rsvp_id, amount_paid, payment_method, payment_status, transaction_ref, rate_type)
                 VALUES ($rsvp_id, $penalty, 'card', 'paid', '$tx_ref', 'hourly')"
            );
        }

        $msg_penalty = $penalty > 0 ? " Late fee applied: ₱" . number_format($penalty, 2) . " ($late_hrs hr(s) late)." : " Checked out on time.";
        $message  = "Check-out completed." . $msg_penalty;
        $msg_type = 'success';
    } else {
        $message  = "Cannot check out — reservation not found or already completed.";
        $msg_type = 'error';
    }
}

// ============================================================
// LOOK UP RESERVATION
// ============================================================
$search_term = '';
if (isset($_GET['search']) && trim($_GET['search']) != '') {
    $search_term = trim($_GET['search']);

    // Extract trailing number from SL-YYYY-XXXXXX format, or treat as plain ID
    if (preg_match('/(\d+)$/', $search_term, $m)) {
        $lookup_id = (int) $m[1];
    } else {
        $lookup_id = 0;
    }

    $found_rsvp = $conn->query(
        "SELECT rd.*, lr.size, lr.location, lr.pricer_per_hr,
                u.full_name, u.email, u.phone_number,
                la.access_code,
                (SELECT SUM(p2.amount_paid) FROM payments p2
                 WHERE p2.rsvp_id = rd.rsvp_id AND p2.payment_status = 'paid') AS amount_paid,
                (SELECT payment_method FROM payments
                 WHERE rsvp_id = rd.rsvp_id ORDER BY payment_id ASC LIMIT 1) AS payment_method
         FROM rsvp_details rd
         JOIN locker_rsvp lr ON rd.locker_id = lr.locker_id
         JOIN users u ON rd.user_id = u.user_id
         LEFT JOIN locker_access la ON la.rsvp_id = rd.rsvp_id
         WHERE rd.rsvp_id = $lookup_id
         LIMIT 1"
    )->fetch_assoc();

    if (!$found_rsvp) {
        $message  = "No reservation found for: " . htmlspecialchars($search_term);
        $msg_type = 'error';
    }
}

// Fetch available lockers for walk-in form
$avail_lockers = $conn->query(
    "SELECT * FROM locker_rsvp WHERE is_active=1 ORDER BY location, size"
);

// Fetch users for walk-in form
$all_users = $conn->query("SELECT user_id, full_name, email FROM users ORDER BY full_name");

$size_labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];

function rsvp_number($id, $created_at) {
    return 'SL-' . date('Y', strtotime($created_at)) . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
}

function status_badge_class($status) {
    return ['active' => 'badge--green', 'completed' => 'badge--gray', 'cancelled' => 'badge--red', 'expired' => 'badge--gray'][$status] ?? 'badge--gray';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Check-in / Check-out | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .page__header { text-align: center; margin-bottom: 28px; }
    .page__title { margin: 0; font-size: 30px; font-weight: 800; color: #0f172a; }
    .page__subtitle { margin: 8px 0 0; color: #64748b; }

    .alert { padding: 13px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
    .alert--success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .alert--error   { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    @media(max-width:800px){ .two-col { grid-template-columns: 1fr; } }

    .card-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px 24px; box-shadow: 0 4px 14px rgba(15,23,42,0.05); margin-bottom: 24px; }
    .card-wrap h2 { margin: 0 0 18px; font-size: 18px; font-weight: 800; color: #0f172a; }

    /* Search bar */
    .search-row { display: flex; gap: 10px; }
    .search-input { flex: 1; padding: 11px 14px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; outline: none; }
    .search-input:focus { border-color: rgba(59,130,246,0.65); background: #fff; }
    .btn-search { padding: 11px 20px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; }

    /* Reservation detail card */
    .rsvp-detail { margin-top: 18px; padding: 18px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; }
    .rsvp-detail .rsvp-num { font-size: 20px; font-weight: 800; color: #0b58ff; margin-bottom: 12px; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .di { background: #fff; border-radius: 9px; padding: 9px 12px; border: 1px solid #f1f5f9; }
    .di-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
    .di-value { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 3px; }

    /* Action buttons */
    .action-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .btn-checkin { padding: 10px 20px; border-radius: 9px; background: #16a34a; color: #fff; font-weight: 700; font-size: 13px; text-decoration: none; border: none; cursor: pointer; }
    .btn-checkin:hover { opacity: 0.9; }
    .btn-checkout { padding: 10px 20px; border-radius: 9px; background: #0b58ff; color: #fff; font-weight: 700; font-size: 13px; text-decoration: none; border: none; cursor: pointer; }
    .btn-checkout:hover { opacity: 0.9; }
    .btn-disabled { padding: 10px 20px; border-radius: 9px; background: #e5e7eb; color: #94a3b8; font-weight: 700; font-size: 13px; cursor: not-allowed; }

    /* Badges */
    .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .badge--green { background: #dcfce7; color: #166534; }
    .badge--red   { background: #fee2e2; color: #991b1b; }
    .badge--gray  { background: #f1f5f9; color: #475569; }

    /* Access code */
    .access-pill { display: inline-block; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 3px 12px; font-family: monospace; font-size: 16px; font-weight: 800; color: #b45309; letter-spacing: 3px; margin-left: 6px; }

    /* Walk-in form */
    .form__label { display: block; margin: 12px 0 5px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 10px 12px; border-radius: 9px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); background: #fff; }
    .btn-walkin { width: 100%; margin-top: 16px; padding: 12px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; }
    .btn-walkin:hover { opacity: 0.9; }
    .price-preview-sm { margin-top: 12px; padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; font-size: 14px; color: #166534; }
    .price-preview-sm strong { font-size: 20px; }

    /* Late fee warning */
    .late-warn { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 10px; font-size: 13px; margin-top: 10px; }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="container">

    <div class="page__header">
      <h1 class="page__title">Check-in / Check-out</h1>
      <p class="page__subtitle">
        Look up a reservation to verify, check-in, or check-out a guest.
      </p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert--<?php echo $msg_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="two-col">

      <!-- LEFT: Lookup + Check-in/out -->
      <div>
        <div class="card-wrap">
          <h2>🔍 Reservation Lookup</h2>
          <form method="GET" action="">
            <div class="search-row">
              <input class="search-input" type="text" name="search"
                     value="<?php echo htmlspecialchars($search_term); ?>"
                     placeholder="Enter Reservation No. (e.g. SL-2026-000001)">
              <button class="btn-search" type="submit">Look Up</button>
            </div>
          </form>

          <?php if ($found_rsvp): ?>
            <?php
              $r       = $found_rsvp;
              $rid     = $r['rsvp_id'];
              $rnum    = rsvp_number($rid, $r['created_at']);
              $now_ts  = time();
              $end_ts  = strtotime($r['end_time']);
              $is_late = ($now_ts > $end_ts && $r['status'] == 'active');
              $late_hrs = $is_late ? ceil(($now_ts - $end_ts) / 3600) : 0;
              $penalty  = $is_late ? $late_hrs * PENALTY_PER_HOUR : 0;
            ?>
            <div class="rsvp-detail">
              <div class="rsvp-num">
                <?php echo $rnum; ?>
                <span class="badge <?php echo status_badge_class($r['status']); ?>" style="margin-left:10px;vertical-align:middle;">
                  <?php echo ucfirst($r['status']); ?>
                </span>
              </div>

              <div class="detail-grid">
                <div class="di"><div class="di-label">Guest</div><div class="di-value"><?php echo htmlspecialchars($r['full_name']); ?></div></div>
                <div class="di"><div class="di-label">Email / Phone</div><div class="di-value"><?php echo htmlspecialchars($r['email']); ?><br><?php echo htmlspecialchars($r['phone_number'] ?? '—'); ?></div></div>
                <div class="di"><div class="di-label">Locker</div><div class="di-value">#<?php echo $r['locker_id']; ?> — <?php echo $size_labels[$r['size']]; ?></div></div>
                <div class="di"><div class="di-label">Location</div><div class="di-value"><?php echo htmlspecialchars($r['location']); ?></div></div>
                <div class="di"><div class="di-label">Check-in</div><div class="di-value"><?php echo date('M d, Y h:i A', strtotime($r['start_time'])); ?></div></div>
                <div class="di"><div class="di-label">Check-out</div><div class="di-value"><?php echo date('M d, Y h:i A', strtotime($r['end_time'])); ?></div></div>
                <div class="di"><div class="di-label">Total Paid (incl. extensions)</div><div class="di-value">₱<?php echo number_format($r['amount_paid'], 2); ?></div></div>
                <div class="di"><div class="di-label">Extensions</div><div class="di-value"><?php echo (int)$r['extn_count']; ?></div></div>
              </div>

              <?php if ($r['access_code']): ?>
                <div style="margin-bottom:12px;">
                  <span style="font-size:12px;font-weight:700;color:#64748b;">🔐 Access Code:</span>
                  <span class="access-pill"><?php echo htmlspecialchars($r['access_code']); ?></span>
                </div>
              <?php endif; ?>

              <?php if ($is_late): ?>
                <div class="late-warn">
                  ⚠ This guest is <strong><?php echo $late_hrs; ?> hour(s)</strong> past their check-out time.
                  Late fee if checked out now: <strong>₱<?php echo number_format($penalty, 2); ?></strong>
                  (₱<?php echo number_format(PENALTY_PER_HOUR, 2); ?>/hr × <?php echo $late_hrs; ?> hr)
                </div>
              <?php endif; ?>

              <div class="action-row">
                <?php if ($r['status'] == 'active'): ?>
                  <a class="btn-checkin"
                     href="checkin.php?action=checkin&rsvp_id=<?php echo $rid; ?>&search=<?php echo urlencode($search_term); ?>"
                     onclick="return confirm('Confirm check-in for this reservation?');">
                    ✓ Confirm Check-in
                  </a>
                  <a class="btn-checkout"
                     href="checkin.php?action=checkout&rsvp_id=<?php echo $rid; ?>&search=<?php echo urlencode($search_term); ?>"
                     onclick="return confirm('Confirm check-out? <?php echo $is_late ? "A late fee of PHP " . number_format($penalty,2) . " will be applied." : ""; ?>');">
                    ✓ Confirm Check-out
                  </a>
                <?php elseif ($r['status'] == 'completed'): ?>
                  <span class="btn-disabled">Already Checked Out</span>
                <?php elseif ($r['status'] == 'cancelled'): ?>
                  <span class="btn-disabled">Reservation Cancelled</span>
                <?php else: ?>
                  <span class="btn-disabled"><?php echo ucfirst($r['status']); ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Walk-in Reservation (staff and sys_admin only) -->
      <?php if (in_array($role, ['sys_admin', 'staff'])): ?>
      <div>
        <div class="card-wrap">
          <h2>🚶 Walk-in Reservation</h2>
          <p style="font-size:13px; color:#64748b; margin:0 0 16px;">
            Create a reservation for a walk-in guest who doesn't have an online booking.
          </p>
          <form method="POST" action="" id="walkinForm">
            <input type="hidden" name="action" value="walkin">

            <label class="form__label">Customer Account</label>
            <select class="form__control" name="walkin_user_id" required>
              <option value="">— Select registered user —</option>
              <?php while ($u = $all_users->fetch_assoc()): ?>
                <option value="<?php echo $u['user_id']; ?>">
                  <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['email']); ?>)
                </option>
              <?php endwhile; ?>
            </select>

            <label class="form__label">Locker</label>
            <select class="form__control" name="walkin_locker_id" id="walkin_locker_id" required onchange="updateWalkinPrice()">
              <option value="">— Select available locker —</option>
              <?php
              $avail_lockers->data_seek(0);
              while ($lck = $avail_lockers->fetch_assoc()):
              ?>
                <option value="<?php echo $lck['locker_id']; ?>"
                        data-price="<?php echo $lck['pricer_per_hr']; ?>">
                  #<?php echo $lck['locker_id']; ?> — <?php echo $size_labels[$lck['size']]; ?>
                  @ <?php echo htmlspecialchars($lck['location']); ?>
                  (₱<?php echo $lck['pricer_per_hr']; ?>/hr) [<?php echo ucfirst($lck['status']); ?>]
                </option>
              <?php endwhile; ?>
            </select>

            <label class="form__label">Rate Type</label>
            <select class="form__control" name="walkin_rate_type" id="walkin_rate_type" onchange="updateWalkinPrice()">
              <option value="hourly">Hourly</option>
              <option value="daily">Daily</option>
            </select>

            <label class="form__label">Duration</label>
            <input class="form__control" type="number" name="walkin_duration" id="walkin_duration"
                   min="1" value="1" required oninput="updateWalkinPrice()">

            <label class="form__label">Start Date &amp; Time</label>
            <input class="form__control" type="datetime-local" name="walkin_start_time" required>

            <label class="form__label">Payment Method</label>
            <select class="form__control" name="walkin_payment_method">
              <option value="card">Credit / Debit Card</option>
              <option value="ewallet">E-Wallet</option>
            </select>

            <div class="price-preview-sm" id="walkin-price-preview">
              Estimated Total: <strong id="walkin-price-total">₱0.00</strong>
              <span id="walkin-price-breakdown" style="font-size:12px; color:#166534; display:block; margin-top:3px;"></span>
            </div>

            <button class="btn-walkin" type="submit"
                    onclick="return confirm('Create this walk-in reservation?');">
              ➕ Create Walk-in Reservation
            </button>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div>
        <div class="card-wrap">
          <h2>🚶 Walk-in Reservation</h2>
          <p style="font-size:13px; color:#94a3b8; margin:0;">
            Walk-in reservations can only be created by Front Desk Staff or System Administrators.
          </p>
        </div>
      </div>
      <?php endif; ?>

    </div>

  </div>
</div>

<!-- Browser side only, No Node.js, Express, or server-side JS-->
<script>
function updateWalkinPrice() {
    const locker  = document.getElementById('walkin_locker_id');
    const rateEl  = document.getElementById('walkin_rate_type');
    const durEl   = document.getElementById('walkin_duration');

    const selected = locker.options[locker.selectedIndex];
    const price    = parseFloat(selected?.dataset?.price) || 0;
    const rate     = rateEl.value;
    const dur      = parseInt(durEl.value) || 1;
    const hours    = rate === 'daily' ? dur * 24 : dur;
    const total    = price * hours;
    const unit     = rate === 'daily' ? 'day' : 'hour';

    document.getElementById('walkin-price-total').textContent =
        '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById('walkin-price-breakdown').textContent =
        dur + ' ' + unit + (dur > 1 ? 's' : '') +
        (rate === 'daily' ? ' (' + hours + ' hrs)' : '') +
        ' × ₱' + price.toFixed(2) + '/hr';
}
</script>

</body>
</html>
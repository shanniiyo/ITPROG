<?php
/**
 * analytics.php
 * Revenue & Utilization Reports — accessible by sys_admin and manager only.
 *
 * Sections:
 * 1. Summary cards (total revenue, active lockers, occupancy rate)
 * 2. Revenue table — daily / weekly / monthly toggle
 * 3. Locker utilization table (per-locker reservation count + revenue)
 */
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager'])
) {
    echo "<script>
            alert('Access Denied: Only managers and system administrators can view reports.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$period = isset($_GET['period']) ? $_GET['period'] : 'daily';
$allowed_periods = ['daily', 'weekly', 'monthly'];
if (!in_array($period, $allowed_periods)) $period = 'daily';

// --- Revenue query depending on period ---
if ($period == 'daily') {
    $group_format = '%Y-%m-%d';
    $label_format = '%b %d, %Y';
    $period_label = 'Daily Revenue (Last 30 Days)';
    $date_filter  = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($period == 'weekly') {
    $group_format = '%x-W%v';   // ISO year-week
    $label_format = '%x-W%v';
    $period_label = 'Weekly Revenue (Last 12 Weeks)';
    $date_filter  = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)";
} else {
    $group_format = '%Y-%m';
    $label_format = '%b %Y';
    $period_label = 'Monthly Revenue (Last 12 Months)';
    $date_filter  = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
}

// Revenue by period — FIX: join rsvp_details to exclude cancelled reservations
$rev_sql = "SELECT
                DATE_FORMAT(p.created_at, '$group_format')  AS period_key,
                DATE_FORMAT(p.created_at, '$label_format')  AS period_label,
                COUNT(DISTINCT p.rsvp_id)                   AS total_reservations,
                SUM(p.amount_paid)                          AS gross_revenue
            FROM payments p
            JOIN rsvp_details rd ON rd.rsvp_id = p.rsvp_id
            WHERE p.payment_status = 'paid'
              AND rd.status != 'cancelled'
            $date_filter
            GROUP BY period_key
            ORDER BY period_key DESC";
$rev_res = $conn->query($rev_sql);

// Summary stats — FIX: all exclude cancelled reservations
$total_revenue_res = $conn->query(
    "SELECT SUM(p.amount_paid) as total
     FROM payments p
     JOIN rsvp_details rd ON rd.rsvp_id = p.rsvp_id
     WHERE p.payment_status = 'paid' AND rd.status != 'cancelled'"
);
$total_revenue_row = $total_revenue_res->fetch_assoc();
$total_revenue     = (float)($total_revenue_row['total'] ?? 0);

$total_lockers_res = $conn->query("SELECT COUNT(*) as cnt FROM locker_rsvp WHERE is_active=1");
$total_lockers     = (int)$total_lockers_res->fetch_assoc()['cnt'];

$occupied_res  = $conn->query("SELECT COUNT(*) as cnt FROM locker_rsvp WHERE status='occupied' AND is_active=1");
$occupied_cnt  = (int)$occupied_res->fetch_assoc()['cnt'];
$occupancy_pct = $total_lockers > 0 ? round(($occupied_cnt / $total_lockers) * 100, 1) : 0;

// FIX: total reservations count excludes cancelled
$total_rsvp_res = $conn->query("SELECT COUNT(*) as cnt FROM rsvp_details WHERE status != 'cancelled'");
$total_rsvp     = (int)$total_rsvp_res->fetch_assoc()['cnt'];

// Locker utilization — per locker — FIX: exclude cancelled reservations
$util_sql = "SELECT
                lr.locker_id,
                lr.size,
                lr.location,
                lr.status,
                COUNT(rd.rsvp_id)               AS total_reservations,
                COALESCE(SUM(rd.duration_select), 0) AS total_hours_rented,
                COALESCE(SUM(p.amount_paid), 0)  AS total_revenue
             FROM locker_rsvp lr
             LEFT JOIN rsvp_details rd
                ON rd.locker_id = lr.locker_id AND rd.status != 'cancelled'
             LEFT JOIN payments p
                ON p.rsvp_id = rd.rsvp_id AND p.payment_status = 'paid'
             WHERE lr.is_active = 1
             GROUP BY lr.locker_id
             ORDER BY total_revenue DESC";
$util_res = $conn->query($util_sql);

$size_labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics & Reports | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .page__header { text-align: center; margin-bottom: 28px; }
    .page__title { margin: 0; font-size: 30px; font-weight: 800; color: #0f172a; }
    .page__subtitle { margin: 8px 0 0; color: #64748b; }

    /* Summary cards */
    .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    @media(max-width:800px){ .summary-grid { grid-template-columns: 1fr 1fr; } }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px 22px; box-shadow: 0 4px 14px rgba(15,23,42,0.05); }
    .stat-card__label { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
    .stat-card__value { font-size: 28px; font-weight: 800; color: #0f172a; margin: 6px 0 2px; }
    .stat-card__sub { font-size: 13px; color: #64748b; }
    .stat-card--blue .stat-card__value { color: #0b58ff; }
    .stat-card--green .stat-card__value { color: #16a34a; }
    .stat-card--amber .stat-card__value { color: #d97706; }

    /* Period toggle */
    .period-tabs { display: flex; gap: 8px; margin-bottom: 18px; }
    .period-tab { padding: 8px 18px; border-radius: 9px; border: 1.5px solid #e5e7eb; background: #fff; font-size: 13px; font-weight: 700; color: #475569; text-decoration: none; }
    .period-tab.active { border-color: #0b58ff; background: #eff6ff; color: #0b58ff; }
    .period-tab:hover { background: #f1f5f9; }

    /* Card wrapper */
    .card-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px 24px; box-shadow: 0 4px 14px rgba(15,23,42,0.05); margin-bottom: 24px; }
    .card-wrap h2 { margin: 0 0 16px; font-size: 18px; font-weight: 800; color: #0f172a; }

    /* Table */
    .ana-table { width: 100%; border-collapse: collapse; }
    .ana-table th { text-align: left; padding: 10px 14px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
    .ana-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
    .ana-table tr:last-child td { border-bottom: none; }
    .ana-table .num { font-weight: 700; color: #0f172a; }
    .ana-table .revenue { font-weight: 800; color: #0b58ff; }

    /* Status badge */
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
    .status-dot.available { background: #22c55e; }
    .status-dot.occupied  { background: #ef4444; }
    .status-dot.out_of_service { background: #94a3b8; }

    /* Revenue bar */
    .rev-bar-wrap { background: #f1f5f9; border-radius: 4px; height: 8px; min-width: 80px; overflow: hidden; }
    .rev-bar { background: linear-gradient(90deg, #0b58ff, #0ea5e9); height: 100%; border-radius: 4px; }

    /* Export note */
    .export-note { font-size: 12px; color: #94a3b8; margin-top: 16px; }

    .back-link { display: inline-block; margin-bottom: 18px; font-size: 13px; color: #3b82f6; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page">
  <div class="container">

    <a class="back-link" href="reports.php">← Back to Support & Issues</a>

    <div class="page__header">
      <h1 class="page__title">📊 Analytics & Revenue Reports</h1>
      <p class="page__subtitle">
        Logged in as: <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $_SESSION['admin_role']))); ?></strong>
      </p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="stat-card stat-card--blue">
        <div class="stat-card__label">Total Revenue</div>
        <div class="stat-card__value">₱<?php echo number_format($total_revenue, 2); ?></div>
        <div class="stat-card__sub">All-time gross</div>
      </div>
      <div class="stat-card stat-card--green">
        <div class="stat-card__label">Total Reservations</div>
        <div class="stat-card__value"><?php echo number_format($total_rsvp); ?></div>
        <div class="stat-card__sub">All-time bookings</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Active Lockers</div>
        <div class="stat-card__value"><?php echo $total_lockers; ?></div>
        <div class="stat-card__sub"><?php echo $occupied_cnt; ?> currently occupied</div>
      </div>
      <div class="stat-card stat-card--amber">
        <div class="stat-card__label">Occupancy Rate</div>
        <div class="stat-card__value"><?php echo $occupancy_pct; ?>%</div>
        <div class="stat-card__sub">Based on current status</div>
      </div>
    </div>

    <!-- Revenue Table with Period Toggle -->
    <div class="card-wrap">
      <h2><?php echo $period_label; ?></h2>
      <div class="period-tabs">
        <a class="period-tab <?php echo $period == 'daily'   ? 'active' : ''; ?>" href="?period=daily">Daily</a>
        <a class="period-tab <?php echo $period == 'weekly'  ? 'active' : ''; ?>" href="?period=weekly">Weekly</a>
        <a class="period-tab <?php echo $period == 'monthly' ? 'active' : ''; ?>" href="?period=monthly">Monthly</a>
      </div>

      <div style="overflow-x:auto;">
        <table class="ana-table">
          <thead>
            <tr>
              <th>Period</th>
              <th>Reservations</th>
              <th>Gross Revenue</th>
              <th>Avg. Per Reservation</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $has_rev = false;
            while ($rev = $rev_res->fetch_assoc()):
                $has_rev = true;
                $avg = $rev['total_reservations'] > 0
                     ? $rev['gross_revenue'] / $rev['total_reservations']
                     : 0;
            ?>
            <tr>
              <td class="num"><?php echo htmlspecialchars($rev['period_label']); ?></td>
              <td><?php echo number_format($rev['total_reservations']); ?></td>
              <td class="revenue">₱<?php echo number_format($rev['gross_revenue'], 2); ?></td>
              <td>₱<?php echo number_format($avg, 2); ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if (!$has_rev): ?>
            <tr>
              <td colspan="4" style="text-align:center; color:#94a3b8; padding:24px;">
                No payment data for this period.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="export-note">* This report is read-only. Contact the system administrator to export data.</p>
    </div>

    <!-- Locker Utilization Table -->
    <?php
    // Find max revenue for bar scaling
    $util_rows = [];
    while ($u = $util_res->fetch_assoc()) $util_rows[] = $u;
    $max_rev = 0;
    foreach ($util_rows as $u) if ((float)$u['total_revenue'] > $max_rev) $max_rev = (float)$u['total_revenue'];
    ?>
    <div class="card-wrap">
      <h2>🧳 Locker Utilization Rates</h2>
      <div style="overflow-x:auto;">
        <table class="ana-table">
          <thead>
            <tr>
              <th>Locker</th>
              <th>Size</th>
              <th>Location</th>
              <th>Status</th>
              <th>Total Reservations
                    includes cancelled</th>
              <th>Total Hours Rented</th>
              <th>Revenue Generated</th>
              <th>Revenue Share</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($util_rows as $u):
                $share = $total_revenue > 0 ? round(((float)$u['total_revenue'] / $total_revenue) * 100, 1) : 0;
                $bar_pct = $max_rev > 0 ? round(((float)$u['total_revenue'] / $max_rev) * 100) : 0;
            ?>
            <tr>
              <td class="num">#<?php echo $u['locker_id']; ?></td>
              <td><?php echo $size_labels[$u['size']] ?? ucfirst($u['size']); ?></td>
              <td><?php echo htmlspecialchars($u['location']); ?></td>
              <td>
                <span class="status-dot <?php echo $u['status']; ?>"></span>
                <?php echo ucfirst(str_replace('_', ' ', $u['status'])); ?>
              </td>
              <td><?php echo number_format($u['total_reservations']); ?></td>
              <td><?php echo number_format((int)$u['total_hours_rented']); ?> hr(s)</td>
              <td class="revenue">₱<?php echo number_format($u['total_revenue'], 2); ?></td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div class="rev-bar-wrap" style="width:80px;">
                    <div class="rev-bar" style="width:<?php echo $bar_pct; ?>%;"></div>
                  </div>
                  <span style="font-size:12px; color:#64748b;"><?php echo $share; ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($util_rows)): ?>
            <tr>
              <td colspan="8" style="text-align:center; color:#94a3b8; padding:24px;">No locker data available.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="export-note">* Utilization is calculated from all-time reservation history.</p>
    </div>

  </div>
</div>

</body>
</html>
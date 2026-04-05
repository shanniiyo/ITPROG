<?php
/**
 * notifications.php
 * Shows the logged-in user's simulated email inbox.
 * Emails are stored in the notifications table by mailer.php.
 */
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Mark a notification as read when clicked
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $notif_id = (int) $_GET['read'];
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE notif_id=$notif_id AND user_id=$user_id");
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications for this user, newest first
$sql = "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Count unread
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = (int) $unread_row['cnt'];

// Type labels and icons
$type_labels = [
    'confirmation'        => 'Booking Confirmed',
    'expiration_reminder' => 'Expiration Reminder',
    'cancellation'        => 'Cancellation',
    'extension'           => 'Reservation Extended',
];
$type_colors = [
    'confirmation'        => 'green',
    'expiration_reminder' => 'amber',
    'cancellation'        => 'red',
    'extension'           => 'blue',
];
$type_icons = [
    'confirmation'        => '✓',
    'expiration_reminder' => '⏰',
    'cancellation'        => '✕',
    'extension'           => '⏱',
];

// Which notification to open (view full body)
$open_id = isset($_GET['open']) ? (int)$_GET['open'] : null;
$open_notif = null;
if ($open_id) {
    $open_res = mysqli_query($conn, "SELECT * FROM notifications WHERE notif_id=$open_id AND user_id=$user_id LIMIT 1");
    if (mysqli_num_rows($open_res) == 1) {
        $open_notif = mysqli_fetch_assoc($open_res);
        // Mark as read
        mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE notif_id=$open_id");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notifications | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .notif-wrapper { max-width: 720px; margin: 0 auto; }

    /* Page header */
    .page__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page__title { margin: 0; font-size: 26px; font-weight: 800; color: #0f172a; }
    .page__title span { font-size: 14px; font-weight: 600; background: #ef4444; color: #fff; border-radius: 999px; padding: 2px 10px; margin-left: 10px; vertical-align: middle; }
    .mark-all { font-size: 13px; color: #3b82f6; text-decoration: none; }
    .mark-all:hover { text-decoration: underline; }

    /* Notification list */
    .notif-list { display: flex; flex-direction: column; gap: 10px; }

    .notif-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; display: flex; gap: 14px; align-items: flex-start; cursor: pointer; text-decoration: none; color: inherit; transition: box-shadow 0.15s; }
    .notif-item:hover { box-shadow: 0 4px 14px rgba(15,23,42,0.08); }
    .notif-item.unread { border-left: 4px solid #0b58ff; background: #f8fbff; }

    /* Icon bubble */
    .notif-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; font-weight: 700; }
    .notif-icon.green  { background: #dcfce7; color: #166534; }
    .notif-icon.amber  { background: #fef3c7; color: #92400e; }
    .notif-icon.red    { background: #fee2e2; color: #991b1b; }
    .notif-icon.blue   { background: #dbeafe; color: #1e40af; }

    .notif-body { flex: 1; min-width: 0; }
    .notif-subject { font-size: 14px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-meta { font-size: 12px; color: #94a3b8; margin-top: 3px; }
    .notif-preview { font-size: 13px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #0b58ff; flex-shrink: 0; margin-top: 6px; }

    /* Email valid badge */
    .email-badge { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 999px; margin-left: 6px; font-weight: 600; }
    .email-badge.valid   { background: #dcfce7; color: #166534; }
    .email-badge.invalid { background: #fee2e2; color: #991b1b; }

    /* Open notification panel */
    .notif-open { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 28px; margin-bottom: 20px; box-shadow: 0 6px 22px rgba(15,23,42,0.06); }
    .notif-open__header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .notif-open__subject { font-size: 18px; font-weight: 800; color: #0f172a; }
    .notif-open__meta { font-size: 13px; color: #64748b; margin-top: 4px; }
    .notif-open__to { font-size: 13px; color: #475569; margin-bottom: 16px; }
    .notif-open__body { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; font-size: 14px; color: #0f172a; line-height: 1.8; white-space: pre-wrap; font-family: monospace; }
    .btn-back { display: inline-block; font-size: 13px; color: #3b82f6; text-decoration: none; margin-bottom: 16px; }
    .btn-back:hover { text-decoration: underline; }

    /* Empty state */
    .empty { text-align: center; padding: 60px 20px; color: #64748b; }
    .empty h3 { font-size: 18px; color: #0f172a; margin-bottom: 8px; }
  </style>
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="page">
  <div class="container notif-wrapper">

    <?php if ($open_notif): ?>
      <!-- ====== OPEN / READ VIEW ====== -->
      <a class="btn-back" href="notifications.php">← Back to Notifications</a>

      <div class="notif-open">
        <div class="notif-open__header">
          <div>
            <div class="notif-open__subject"><?php echo htmlspecialchars($open_notif['subject']); ?></div>
            <div class="notif-open__meta">
              <?php echo $type_labels[$open_notif['type']] ?? $open_notif['type']; ?>
              &bull;
              <?php echo date('M d, Y h:i A', strtotime($open_notif['created_at'])); ?>
            </div>
          </div>
          <span class="notif-icon <?php echo $type_colors[$open_notif['type']] ?? 'blue'; ?>" style="width:44px;height:44px;font-size:20px;">
            <?php echo $type_icons[$open_notif['type']] ?? '📧'; ?>
          </span>
        </div>

        <div class="notif-open__to">
          <strong>To:</strong> <?php echo htmlspecialchars($open_notif['sent_to']); ?>
          <?php if ($open_notif['email_valid']): ?>
            <span class="email-badge valid">✓ Valid Email — Sent</span>
          <?php else: ?>
            <span class="email-badge invalid">✕ Invalid Email — Not Sent</span>
          <?php endif; ?>
        </div>

        <div class="notif-open__body"><?php echo htmlspecialchars($open_notif['body']); ?></div>
      </div>

    <?php else: ?>
      <!-- ====== INBOX LIST VIEW ====== -->
      <div class="page__header">
        <h1 class="page__title">
          Notifications
          <?php if ($unread_count > 0): ?>
            <span><?php echo $unread_count; ?></span>
          <?php endif; ?>
        </h1>
        <?php if ($unread_count > 0): ?>
          <a class="mark-all" href="notifications.php?mark_all=1">Mark all as read</a>
        <?php endif; ?>
      </div>

      <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="empty">
          <h3>No notifications yet</h3>
          <p>Emails about your reservations will appear here.</p>
        </div>
      <?php else: ?>
        <div class="notif-list">
          <?php while ($notif = mysqli_fetch_assoc($result)): ?>
            <?php
              $color   = $type_colors[$notif['type']] ?? 'blue';
              $icon    = $type_icons[$notif['type']] ?? '📧';
              $label   = $type_labels[$notif['type']] ?? $notif['type'];
              $is_unread = !$notif['is_read'];
              // Get first line of body as preview
              $preview = strtok($notif['body'], "\n");
            ?>
            <a class="notif-item <?php echo $is_unread ? 'unread' : ''; ?>"
               href="notifications.php?open=<?php echo $notif['notif_id']; ?>">

              <div class="notif-icon <?php echo $color; ?>"><?php echo $icon; ?></div>

              <div class="notif-body">
                <div class="notif-subject"><?php echo htmlspecialchars($notif['subject']); ?></div>
                <div class="notif-meta">
                  <?php echo $label; ?>
                  &bull;
                  <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                  <?php if ($notif['email_valid']): ?>
                    <span class="email-badge valid">Sent</span>
                  <?php else: ?>
                    <span class="email-badge invalid">Invalid Email</span>
                  <?php endif; ?>
                </div>
                <div class="notif-preview"><?php echo htmlspecialchars($preview); ?></div>
              </div>

              <?php if ($is_unread): ?>
                <div class="unread-dot"></div>
              <?php endif; ?>

            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

</body>
</html>
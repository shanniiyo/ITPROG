<?php
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='admin_login.php';
          </script>";
    exit();
}

$user_role = $_SESSION['admin_role'];

// Resolve a locker issue (staff, manager, sys_admin can all do this)
if (isset($_POST['resolve_locker_issue'])) {
    $report_id = (int) $_POST['report_id'];
    $stmt = $conn->prepare("UPDATE locker_reports SET description = CONCAT(description, ' — [RESOLVED]') WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    header("Location: reports.php");
    exit();
}

// All roles can see locker issue logs
$locker_issues = $conn->query("SELECT * FROM locker_reports ORDER BY created_at DESC");

// FIX: Only sys_admin and manager can see general support messages (not staff)
$general_messages = null;
if (in_array($user_role, ['sys_admin', 'manager'])) {
    $general_messages = $conn->query("SELECT * FROM support_messages ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports & Support | SmartLocker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* FIX: Proper scoped table styles for this page */
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #ffffff;
        }
        .form-table th {
            text-align: left;
            padding: 11px 14px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }
        .form-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }
        .form-table tr:last-child td {
            border-bottom: none;
        }
        /* FIX: Issue type badge replaces <mark> */
        .issue-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            background: #fef3c7;
            color: #92400e;
            white-space: nowrap;
        }
        /* Resolved row dim */
        .form-table tr.resolved td {
            opacity: 0.55;
        }
        /* Resolve button */
        .btn-resolve {
            padding: 5px 12px;
            border-radius: 8px;
            background: #0b58ff;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-resolve:hover { opacity: 0.85; }
        .btn-resolve:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        /* Analytics link button */
        .analytics-btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 10px;
            background: linear-gradient(90deg,#0b58ff,#0ea5e9);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .analytics-btn:hover { opacity: 0.9; }

        .panel { margin-bottom: 28px; }
        .panel__head { margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .panel__title { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; }
        .panel__body { overflow-x: auto; }

        .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
        .page__header { text-align: center; margin-bottom: 28px; }
        .page__title { margin: 0; font-size: 30px; font-weight: 800; color: #0f172a; }
        .page__subtitle { margin: 8px 0 0; color: #64748b; }
        .card-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px 24px; box-shadow: 0 4px 14px rgba(15,23,42,0.05); margin-bottom: 24px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="page">
    <div class="container">

        <div class="page__header">
            <h1 class="page__title">Support & Issue Reports</h1>
            <p class="page__subtitle">Logged in as: <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user_role))); ?></strong></p>
        </div>

        <!-- Analytics shortcut for manager / sys_admin -->
        <?php if (in_array($user_role, ['sys_admin', 'manager'])): ?>
            <a class="analytics-btn" href="analytics.php">📊 View Revenue & Utilization Reports →</a>
        <?php endif; ?>

        <!-- LOCKER ISSUE LOGS (all roles) -->
        <div class="card-wrap">
            <div class="panel__head">
                <h2 class="panel__title">🔧 Locker Issue Logs</h2>
            </div>
            <div class="panel__body">
                <table class="form-table">
                    <thead>
                        <tr>
                            <th>RSVP ID</th>
                            <th>Locker</th>
                            <th>Issue Type</th>
                            <th>Description</th>
                            <th>Reported At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_issues = false;
                        while ($row = $locker_issues->fetch_assoc()):
                            $has_issues = true;
                            $is_resolved = str_contains($row['description'], '[RESOLVED]');
                        ?>
                        <tr class="<?php echo $is_resolved ? 'resolved' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['rsvp_id']); ?></td>
                            <td>#<?php echo htmlspecialchars($row['locker_id']); ?></td>
                            <td><span class="issue-badge"><?php echo htmlspecialchars($row['issue_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td style="white-space:nowrap; font-size:12px; color:#64748b;">
                                <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <?php if (!$is_resolved): ?>
                                <form method="POST" action="reports.php" style="margin:0;">
                                    <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="resolve_locker_issue" class="btn-resolve">
                                        ✓ Resolve
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="font-size:12px; color:#16a34a; font-weight:600;">✓ Resolved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_issues): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#94a3b8; padding:24px;">
                                No locker issues reported.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GENERAL SUPPORT MESSAGES (sys_admin + manager only) -->
        <?php if ($general_messages): ?>
        <div class="card-wrap">
            <div class="panel__head">
                <h2 class="panel__title">💬 General Support Messages</h2>
            </div>
            <div class="panel__body">
                <table class="form-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Received At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_msgs = false;
                        while ($msg = $general_messages->fetch_assoc()):
                            $has_msgs = true;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"
                                   class="link--email">
                                    <?php echo htmlspecialchars($msg['email']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge--info">
                                    <?php echo htmlspecialchars($msg['subject']); ?>
                                </span>
                            </td>
                            <td class="td--message"><?php echo htmlspecialchars($msg['message']); ?></td>
                            <td style="white-space:nowrap; font-size:12px; color:#64748b;">
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_msgs): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#94a3b8; padding:24px;">
                                No support messages yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <p style="font-size:12px; color:#94a3b8; margin-top:10px;">
            * Records are read-only and cannot be deleted per system policy.
        </p>

    </div>
</main>

</body>
</html>
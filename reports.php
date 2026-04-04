<?php
session_start();
include 'db_connect.php'; 

if (
    !isset($_SESSION['admin_id']) || 
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff']) 
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$user_role = $_SESSION['admin_role'];

if (isset($_POST['resolve_locker_issue'])) {
    $report_id = $_POST['report_id'];
    // Update description to show it's resolved without deleting the record
    $stmt = $conn->prepare("UPDATE locker_reports SET description = CONCAT(description, ' - [RESOLVED]') WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
}

// viewable for staff
$locker_issues = $conn->query("SELECT * FROM locker_reports ORDER BY created_at DESC");

// stuff thats only for the admin and managers
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
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="page">
    <section class="container">
        
        <header class="page__header">
            <h1 class="page__title">Support & Issues Report</h1>
            <p class="page__subtitle">Logged in as: <strong><?php echo htmlspecialchars($user_role); ?></strong></p>
        </header>

        <article class="panel">
            <header class="panel__head">
                <h2 class="panel__title">Locker Issue Logs</h2>
            </header>
            <table class="form-table">
                <thead>
                    <tr>
                        <th>RSVP ID</th>
                        <th>Locker</th>
                        <th>Issue</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $locker_issues->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['rsvp_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['locker_id']); ?></td>
                        <td><mark><?php echo htmlspecialchars($row['issue_type']); ?></mark></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>
                            <form method="POST" action="reports.php">
                                <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="resolve_locker_issue" class="btn btn--sm">Resolve</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </article>

       <?php if ($general_messages): ?>
            <article class="panel">
                <header class="panel__head">
                    <h2 class="panel__title">General Support Messages</h2>
                </header>
                <div class="panel__body">
                    <table class="report-table">
                    <thead>
                        <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($msg = $general_messages->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                            <td><a href="mailto:<?php echo $msg['email']; ?>" class="link--email"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                            <td><span class="badge badge--info"><?php echo htmlspecialchars($msg['subject']); ?></span></td>
                            <td class="td--message"><?php echo htmlspecialchars($msg['message']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    </table>
                </div>
            </article>
        <?php endif; ?>

        <footer style="margin-top: 2rem;">
            <small>* Records are read-only and cannot be deleted per system policy.</small>
        </footer>

    </section>
</main>

</body>
</html>
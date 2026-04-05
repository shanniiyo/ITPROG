<?php
session_start();
include 'db_connect.php';
/**
 * support.php (PHP-ready)
 * - Forms are front-end only for now.
 * - Later: set form action to a handler (e.g., support_submit.php / issue_submit.php)
 * - Later: add validation + DB insert + email notifications
 */
$feedback_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // for support_messages
    if (isset($_POST['message'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $subject = $_POST['subject'];
        $message = $_POST['message'];

        $stmt = $conn->prepare("INSERT INTO support_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $feedback_msg = "Thank you! Your message has been sent.";
        }
    } 
    
    // for locker_reports
    else if (isset($_POST['locker_id'])) {
        $reservation_no_input = trim($_POST['reservation_no']);
        $locker_id   = $_POST['locker_id'];
        $issue_type  = $_POST['issue_type'];
        $description = $_POST['issue_desc'];

        // Extract numeric rsvp_id from format SL-YYYY-000001 → 1
        // Also accepts a plain number entered directly
        if (preg_match('/(\d+)$/', $reservation_no_input, $matches)) {
            $rsvp_id = (int) $matches[1];
        } else {
            $rsvp_id = 0;
        }

        // Verify the rsvp_id actually exists in the DB
        $check = $conn->prepare("SELECT rsvp_id FROM rsvp_details WHERE rsvp_id = ? LIMIT 1");
        $check->bind_param("i", $rsvp_id);
        $check->execute();
        $check->store_result();

        if ($rsvp_id > 0 && $check->num_rows > 0) {
            // Get the locker_id from rsvp_details instead of trusting user input
            $get_locker = $conn->prepare("SELECT locker_id FROM rsvp_details WHERE rsvp_id = ? LIMIT 1");
            $get_locker->bind_param("i", $rsvp_id);
            $get_locker->execute();
            $locker_result = $get_locker->get_result();
            $locker_row = $locker_result->fetch_assoc();
            $verified_locker_id = $locker_row['locker_id'];
    
            $stmt = $conn->prepare("INSERT INTO locker_reports (rsvp_id, locker_id, issue_type, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $rsvp_id, $verified_locker_id, $issue_type, $description);
            if ($stmt->execute()) {
                $feedback_msg = "Locker report submitted. Our staff will check it shortly.";
            }
        } else {
            $feedback_msg = "Error: Reservation number not found. Please check and try again.";
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Support | SmartLocker</title>

  <!-- Reuse the SAME CSS across pages -->
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<?php include 'navbar_client.php'; ?>


<!-- =========================
     PAGE CONTENT
========================= -->
<main class="page">
  <div class="container">

    <!-- Page header -->
    <div class="page__header">
      <h1 class="page__title">Customer Support</h1>
      <p class="page__subtitle">We're here to help. Get in touch with us.</p>
    </div>

    <!-- Top 3 cards -->
    <section class="support-cards">
      <article class="mini-card">
        <div class="mini-card__icon mini-card__icon--blue">
          <img src="images/icon-faq.png" alt="">
        </div>
        <h3 class="mini-card__title">FAQ</h3>
        <p class="mini-card__text">Find answers to common questions</p>
      </article>

      <article class="mini-card">
        <div class="mini-card__icon mini-card__icon--green">
          <img src="images/icon-phone.png" alt="">
        </div>
        <h3 class="mini-card__title">Call Us</h3>
        <p class="mini-card__text">
          +1 (800) 123-4567<br>
          <span class="muted">Mon–Fri, 9AM–6PM EST</span>
        </p>
      </article>

      <article class="mini-card">
        <div class="mini-card__icon mini-card__icon--purple">
          <img src="images/icon-email.png" alt="">
        </div>
        <h3 class="mini-card__title">Email</h3>
        <p class="mini-card__text">
          support@smartlocker.com<br>
          <span class="muted">Response within 24 hours</span>
        </p>
      </article>
    </section>

    <!-- Feedback message -->
    <?php if ($feedback_msg): ?>
      <div style="max-width:980px; margin: 0 auto 16px; padding: 13px 16px; border-radius: 10px; background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; font-size: 14px;">
        <?php echo htmlspecialchars($feedback_msg); ?>
      </div>
    <?php endif; ?>

    <!-- Two-column forms -->
    <section class="support-grid">

      <!-- Contact Support -->
      <section class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Contact Support</h2>
          <p class="panel__sub">Send us a message and we'll get back to you soon</p>
        </div>

        <!-- Later: action="support_submit.php" method="POST" -->
        <form class="form" method="POST" action="">
          <label class="form__label">Your Name</label>
          <input class="form__control" type="text" name="name" placeholder="John Doe">

          <label class="form__label">Email Address</label>
          <input class="form__control" type="email" name="email" placeholder="john@example.com">

          <label class="form__label">Subject</label>
          <select class="form__control" name="subject">
            <option value="" selected disabled>Select a subject</option>
            <option>Reservation</option>
            <option>Payment</option>
            <option>Locker Access</option>
            <option>Other</option>
          </select>

          <label class="form__label">Message</label>
          <textarea class="form__control" name="message" rows="5" placeholder="Describe your issue or question..."></textarea>

          <button class="btn btn--primary btn--full" type="submit">Send Message</button>
        </form>
      </section>

      <!-- Report Locker Issue -->
      <section class="panel">
        <div class="panel__head panel__head--warn">
          <div class="warn-badge">
            <img src="images/icon-warning.png" alt="">
          </div>
          <div>
            <h2 class="panel__title">Report Locker Issue</h2>
            <p class="panel__sub">Report a problem with a specific locker</p>
          </div>
        </div>

        <!-- Later: action="issue_submit.php" method="POST" -->
        <form class="form" method="POST" action="">
          <label class="form__label">Reservation Number</label>
          <input class="form__control" type="text" name="reservation_no" placeholder="SL-2026-0001">

          <label class="form__label">Locker ID</label>
          <input class="form__control" type="text" name="locker_id" placeholder="L001">

          <label class="form__label">Issue Type</label>
          <select class="form__control" name="issue_type">
            <option value="" selected disabled>Select issue type</option>
            <option>Cannot open locker</option>
            <option>Payment issue</option>
            <option>Locker damaged</option>
            <option>Item left behind</option>
            <option>Other</option>
          </select>

          <label class="form__label">Description</label>
          <textarea class="form__control" name="issue_desc" rows="5" placeholder="Please describe the issue in detail..."></textarea>

          <button class="btn btn--danger btn--full" type="submit">Submit Report</button>
        </form>
      </section>

    </section>

  </div>
</main>

</body>
</html>
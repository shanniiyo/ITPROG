<?php
$reservation_number = "SL-2026-00124";
$customer_name = "Juan Dela Cruz";
$locker_location = "NAIA Terminal 3";
$locker_size = "Medium";
$checkin = "April 10, 2026 10:00 AM";
$checkout = "April 10, 2026 6:00 PM";
$total_amount = "600.00";
$payment_method = "GCash";
$payment_date = "April 9, 2026 2:15 PM";
?>

<!DOCTYPE html>
<html>
<head>
  <title>Email Templates</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
  <h2>SmartLocker Admin</h2>
</div>

<div class="container">
  <h1>Dummy Email Notifications</h1>

  <div class="email-grid">

    <div class="email-card">
      <h3>Reservation Confirmation</h3>
      <p><strong>Subject:</strong> Your SmartLocker Reservation is Confirmed</p>
      <div class="email-preview">
        <p>Hello <?php echo $customer_name; ?>,</p>
        <p>Your reservation has been successfully confirmed.</p>
        <p>Reservation Number: <?php echo $reservation_number; ?></p>
        <p>Locker Location: <?php echo $locker_location; ?></p>
        <p>Locker Size: <?php echo $locker_size; ?></p>
        <p>Check-in: <?php echo $checkin; ?></p>
        <p>Check-out: <?php echo $checkout; ?></p>
        <p>Total Amount: ₱<?php echo $total_amount; ?></p>
      </div>
      <button>Send Test Email</button>
    </div>

    <div class="email-card">
      <h3>Reservation Reminder</h3>
      <p><strong>Subject:</strong> Reminder: Your SmartLocker Reservation</p>
      <div class="email-preview">
        <p>Hello <?php echo $customer_name; ?>,</p>
        <p>This is a reminder for your upcoming SmartLocker reservation.</p>
        <p>Reservation Number: <?php echo $reservation_number; ?></p>
        <p>Location: <?php echo $locker_location; ?></p>
        <p>Check-in: <?php echo $checkin; ?></p>
      </div>
      <button>Send Test Email</button>
    </div>

    <div class="email-card">
      <h3>Reservation Cancellation</h3>
      <p><strong>Subject:</strong> Your SmartLocker Reservation Has Been Cancelled</p>
      <div class="email-preview">
        <p>Hello <?php echo $customer_name; ?>,</p>
        <p>Your SmartLocker reservation has been cancelled successfully.</p>
        <p>Reservation Number: <?php echo $reservation_number; ?></p>
        <p>Locker Location: <?php echo $locker_location; ?></p>
      </div>
      <button>Send Test Email</button>
    </div>

    <div class="email-card">
      <h3>Payment Receipt</h3>
      <p><strong>Subject:</strong> SmartLocker Payment Receipt</p>
      <div class="email-preview">
        <p>Hello <?php echo $customer_name; ?>,</p>
        <p>Thank you for your payment.</p>
        <p>Reservation Number: <?php echo $reservation_number; ?></p>
        <p>Amount Paid: ₱<?php echo $total_amount; ?></p>
        <p>Payment Method: <?php echo $payment_method; ?></p>
        <p>Payment Date: <?php echo $payment_date; ?></p>
      </div>
      <button>Send Test Email</button>
    </div>

  </div>
</div>

</body>
</html>
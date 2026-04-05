<?php
/**
 * mailer.php
 * Simulated email system for SmartLocker.
 *
 * HOW IT WORKS:
 * - Validates email format: must match  something@domain.extension
 * - Stores the "email" as a notification record in the DB
 * - The user can view their inbox at notifications.php
 * - No real email is ever sent — this is 100% simulated
 *
 * USAGE (include this file, then call):
 *   send_email($conn, $user_id, $rsvp_id, 'confirmation', $to_email, $subject, $body);
 */

/**
 * Checks if an email looks valid: sample@domainExample.com
 * Must have: characters @ characters . characters
 */
function is_valid_email_format($email) {
    return (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
}

/**
 * "Sends" a simulated email by saving it to the notifications table.
 *
 * @param mysqli $conn       DB connection
 * @param int    $user_id    The recipient user's ID
 * @param int|null $rsvp_id  Related reservation ID (can be null)
 * @param string $type       'confirmation' | 'expiration_reminder' | 'cancellation' | 'extension'
 * @param string $to_email   The user's email address
 * @param string $subject    Email subject line
 * @param string $body       Email body (plain text)
 * @return bool              true if saved successfully
 */
function send_email($conn, $user_id, $rsvp_id, $type, $to_email, $subject, $body) {
    $email_valid = is_valid_email_format($to_email) ? 1 : 0;

    $user_id  = (int) $user_id;
    $rsvp_id_val = $rsvp_id ? (int)$rsvp_id : 'NULL';
    $type     = mysqli_real_escape_string($conn, $type);
    $to_email = mysqli_real_escape_string($conn, $to_email);
    $subject  = mysqli_real_escape_string($conn, $subject);
    $body     = mysqli_real_escape_string($conn, $body);

    $sql = "INSERT INTO notifications (user_id, rsvp_id, type, subject, body, sent_to, email_valid)
            VALUES ($user_id, $rsvp_id_val, '$type', '$subject', '$body', '$to_email', $email_valid)";

    return mysqli_query($conn, $sql);
}

/**
 * Builds and sends a CONFIRMATION email after a reservation is made.
 */
function notify_confirmation($conn, $user_id, $rsvp_id, $to_email, $full_name, $rsvp_number, $locker_id, $locker_size, $location, $start_time, $end_time, $total_price, $access_code) {
    $subject = "Booking Confirmed: $rsvp_number | SmartLocker";

    $body  = "Hello $full_name,\n\n";
    $body .= "Your locker reservation has been confirmed!\n\n";
    $body .= "--- RESERVATION DETAILS ---\n";
    $body .= "Reservation No. : $rsvp_number\n";
    $body .= "Locker          : #$locker_id — $locker_size\n";
    $body .= "Location        : $location\n";
    $body .= "Check-in        : $start_time\n";
    $body .= "Check-out       : $end_time\n";
    $body .= "Total Paid      : PHP $total_price\n";
    $body .= "Access Code     : $access_code\n\n";
    $body .= "Please show your access code at check-in.\n\n";
    $body .= "Thank you for choosing SmartLocker!\n";
    $body .= "support@smartlocker.com";

    send_email($conn, $user_id, $rsvp_id, 'confirmation', $to_email, $subject, $body);
}

/**
 * Builds and sends an EXPIRATION REMINDER email (sent when a reservation is about to expire or has expired).
 */
function notify_expiration($conn, $user_id, $rsvp_id, $to_email, $full_name, $rsvp_number, $end_time) {
    $subject = "Reminder: Your Reservation $rsvp_number is Expiring | SmartLocker";

    $body  = "Hello $full_name,\n\n";
    $body .= "This is a reminder that your locker reservation is expiring soon.\n\n";
    $body .= "--- RESERVATION DETAILS ---\n";
    $body .= "Reservation No. : $rsvp_number\n";
    $body .= "Check-out Time  : $end_time\n\n";
    $body .= "If you need more time, please log in and extend your reservation before it expires.\n\n";
    $body .= "If you have already collected your belongings, no action is needed.\n\n";
    $body .= "Thank you for choosing SmartLocker!\n";
    $body .= "support@smartlocker.com";

    send_email($conn, $user_id, $rsvp_id, 'expiration_reminder', $to_email, $subject, $body);
}

/**
 * Builds and sends a CANCELLATION confirmation email.
 */
function notify_cancellation($conn, $user_id, $rsvp_id, $to_email, $full_name, $rsvp_number, $locker_id, $location) {
    $subject = "Reservation Cancelled: $rsvp_number | SmartLocker";

    $body  = "Hello $full_name,\n\n";
    $body .= "Your reservation has been successfully cancelled.\n\n";
    $body .= "--- CANCELLED RESERVATION ---\n";
    $body .= "Reservation No. : $rsvp_number\n";
    $body .= "Locker          : #$locker_id\n";
    $body .= "Location        : $location\n\n";
    $body .= "As per our policy, no refund will be issued if the cancellation was within 12 hours of check-in.\n\n";
    $body .= "We hope to serve you again soon!\n";
    $body .= "support@smartlocker.com";

    send_email($conn, $user_id, $rsvp_id, 'cancellation', $to_email, $subject, $body);
}

/**
 * Builds and sends an EXTENSION confirmation email.
 */
function notify_extension($conn, $user_id, $rsvp_id, $to_email, $full_name, $rsvp_number, $new_end_time, $extra_duration, $rate_type, $extra_cost) {
    $subject = "Reservation Extended: $rsvp_number | SmartLocker";

    $unit  = $rate_type == 'daily' ? 'day' : 'hour';
    $units = $extra_duration > 1 ? $unit . 's' : $unit;

    $body  = "Hello $full_name,\n\n";
    $body .= "Your reservation has been extended successfully.\n\n";
    $body .= "--- EXTENSION DETAILS ---\n";
    $body .= "Reservation No. : $rsvp_number\n";
    $body .= "Extended By     : $extra_duration $units\n";
    $body .= "New Check-out   : $new_end_time\n";
    $body .= "Additional Cost : PHP $extra_cost\n\n";
    $body .= "Thank you for choosing SmartLocker!\n";
    $body .= "support@smartlocker.com";

    send_email($conn, $user_id, $rsvp_id, 'extension', $to_email, $subject, $body);
}
?>
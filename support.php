<?php
/**
 * support.php (PHP-ready)
 * - Forms are front-end only for now.
 * - Later: set form action to a handler (e.g., support_submit.php / issue_submit.php)
 * - Later: add validation + DB insert + email notifications
 */
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

<!-- =========================
     TOP NAV (same as other pages)
========================= -->
<header class="topbar">
  <div class="container topbar__row">

    <div class="brand">
      <div class="brand__icon">
        <!-- Replace later -->
        <img src="images/logo.png" alt="SmartLocker Logo">
      </div>
      <div class="brand__name">SmartLocker</div>
    </div>

    <nav class="nav" aria-label="Primary">
      <a href="search.php" class="nav__item">
        <img src="images/icon-search.png" class="nav__icon" alt="">
        Search Lockers
      </a>
      <a href="reservations.php" class="nav__item">
        <img src="images/icon-reservations.png" class="nav__icon" alt="">
        My Reservations
      </a>
      <a href="notifications.php" class="nav__item">
        <img src="images/icon-bell.png" class="nav__icon" alt="">
        Notifications
      </a>

      <!-- Active page -->
      <a href="support.php" class="nav__item nav__item--active">
        <img src="images/icon-support.png" class="nav__icon" alt="">
        Support
      </a>
    </nav>

    <div class="nav nav--right" aria-label="Account">
      <a href="account.php" class="nav__item">
        <img src="images/icon-user.png" class="nav__icon" alt="">
        Account
      </a>
      <a href="logout.php" class="nav__item">
        <img src="images/icon-logout.png" class="nav__icon" alt="">
        Logout
      </a>
    </div>

  </div>
</header>

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
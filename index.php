<?php
/**
 * SmartLocker Main Page
 * 
 * SVG icons are commented out.
 * Replace them later with <img src="icons/...">
 */
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SmartLocker</title>

<link rel="stylesheet" href="style.css">

</head>
<body>

<!-- =========================
     NAVBAR / HEADER
========================= -->
<header class="topbar">
<div class="container topbar__row">

<!-- Brand -->
<div class="brand">

<div class="brand__icon">

<!-- SVG icon removed for now -->

<!--
<svg width="18" height="18">
   <path d="M7 11V8a5 5 0 0 1 10 0v3"/>
</svg>
-->

<!-- Replace later -->
<img src="images/logo.png" alt="SmartLocker Logo">

</div>

<div class="brand__name">SmartLocker</div>

</div>


<!-- Main Navigation -->
<nav class="nav">

<a href="search.php" class="nav__item">

<!-- SVG icon removed -->

<!-- <span class="nav__icon">🔎</span> -->

<!-- Replace later -->
<img src="images/icon-search.png" class="nav__icon">

Search Lockers

</a>


<a href="reservations.php" class="nav__item">

<!-- <span class="nav__icon">🗓️</span> -->

<img src="images/icon-reservations.png" class="nav__icon">

My Reservations

</a>


<a href="notifications.php" class="nav__item">

<!-- <span class="nav__icon">🔔</span> -->

<img src="images/icon-bell.png" class="nav__icon">

Notifications

</a>


<a href="support.php" class="nav__item">

<!-- <span class="nav__icon">❓</span> -->

<img src="images/icon-support.png" class="nav__icon">

Support

</a>

</nav>


<!-- Right Side Navigation -->
<div class="nav nav--right">

<a href="account.php" class="nav__item">

<!-- <span class="nav__icon">👤</span> -->

<img src="images/icon-user.png" class="nav__icon">

Account

</a>


<a href="logout.php" class="nav__item">

<!-- <span class="nav__icon">↩</span> -->

<img src="images/icon-logout.png" class="nav__icon">

Logout

</a>

</div>

</div>
</header>



<!-- =========================
     HERO SECTION
========================= -->
<main>

<section class="hero">

<div class="container hero__content">


<!-- Small pill label -->
<div class="pill">

<span class="pill__dot"></span>

Secure • Convenient • Smart

</div>


<!-- Main headline -->
<h1 class="hero__title">

Travel Light,<br>
Store Smart

</h1>


<!-- Description -->
<p class="hero__subtitle">

Reserve secure luggage storage lockers online.  
Skip the wait, avoid overbooking, and explore the city hands-free during your layover or city tour.

</p>


<!-- Buttons -->
<div class="hero__actions">

<a class="btn btn--light" href="search.php">

<!-- Icon removed -->

<!-- <span class="btn__icon">🔎</span> -->

<img src="images/icon-search.png" class="btn__icon">

Find a Locker

</a>


<a class="btn btn--dark" href="reservations.php">

My Reservations

</a>

</div>

</div>

</section>



<!-- =========================
     NEXT SECTION
========================= -->

<section class="section">

<div class="container">

<h2 class="section__title">

Why Choose SmartLocker?

</h2>

<p class="section__subtitle">

Modern luggage storage designed for travelers who value convenience and security.

</p>

</div>

</section>


</main>

</body>
</html>
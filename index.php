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
<?php include 'navbar_client.php'; ?>

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
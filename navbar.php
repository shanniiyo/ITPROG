<?php
// navbar.php — Admin navbar (role-aware)
$nav_role = $_SESSION['admin_role'] ?? '';
$nav_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<header class="topbar">
  <div class="container topbar__row">

    <div class="brand">
      <div class="brand__icon">
        <img src="images/logo.png" alt="SmartLocker Logo">
      </div>
      <div class="brand__name">SmartLocker</div>
    </div>

    <nav class="nav" aria-label="Primary">

      <a href="dashboard.php" class="nav__item">
        <img src="images/icon-search.png" class="nav__icon" alt="">
        Locker Inventory
      </a>

      <!-- Add New Locker: sys_admin only -->
      <?php if ($nav_role == 'sys_admin'): ?>
      <a href="add_locker.php" class="nav__item">
        <img src="images/icon-bell.png" class="nav__icon" alt="">
        Add New Locker
      </a>
      <?php endif; ?>

      <!-- Check-in / Check-out: all roles -->
      <a href="checkin.php" class="nav__item">
        <img src="images/icon-reservations.png" class="nav__icon" alt="">
        Check-in / Check-out
      </a>

      <!-- Reports: all roles -->
      <a href="reports.php" class="nav__item">
        <img src="images/icon-reservations.png" class="nav__icon" alt="">
        Reports
      </a>

      <!-- Analytics: manager + sys_admin only -->
      <?php if (in_array($nav_role, ['sys_admin', 'manager'])): ?>
      <a href="analytics.php" class="nav__item">
        <img src="images/icon-reservations.png" class="nav__icon" alt="">
        Analytics
      </a>
      <?php endif; ?>

    </nav>

    <div class="nav nav--right" aria-label="Account">
      <!-- Create Admin: sys_admin only -->
      <?php if ($nav_role == 'sys_admin'): ?>
      <a href="admin_register.php" class="nav__item">
        <img src="images/icon-user.png" class="nav__icon" alt="">
        Manage Admins
      </a>
      <?php endif; ?>

      <a href="logout.php" class="nav__item">
        <img src="images/icon-logout.png" class="nav__icon" alt="">
        Logout
      </a>
    </div>

  </div>
</header>
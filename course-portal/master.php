<?php
/* master.php – Shared header & navigation bar */
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    session_start();
}

$isLoggedIn  = isset($_SESSION['username']);
$isAdmin     = $isLoggedIn && $_SESSION['role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<!-- Hero Banner -->
<div class="ep-hero">
    <div class="ep-hero-inner container">
        <h1>Student <span class="accent">Portal</span></h1>
        <?php if ($isLoggedIn): ?>
            <p class="hero-sub">
                Welcome back, <strong><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) ?></strong>
                &nbsp;&mdash;&nbsp; <?= $isAdmin ? 'Role: <strong>Administrator</strong>' : 'Student ID: <strong>' . htmlspecialchars($_SESSION['studentId']) . '</strong>' ?>
            </p>
        <?php else: ?>
            <p class="hero-sub">University of Quiban Global Campus</p>
        <?php endif; ?>
    </div>
</div>

<!-- Sticky Navbar -->
<nav class="ep-navbar">
    <a href="index.php" class="brand">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 13.5L3.74 12 12 7.5 20.26 12 12 16.5zM5 13.18v4.72c0 1.1 3.13 3.1 7 3.1s7-2 7-3.1v-4.72L12 17.5 5 13.18z"/>
        </svg>
        U<span>Q</span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
    </button>

    <ul class="ep-nav-links" id="navLinks">
        <!-- Left side -->
        <li><a href="index.php"   class="<?= navActive('index.php',   $currentPage) ?>">&#8962; Home</a></li>
        <li><a href="courses.php" class="<?= navActive('courses.php', $currentPage) ?>">&#128218; Browse Courses</a></li>
        <?php if ($isLoggedIn && !$isAdmin): ?>
        <li><a href="my_courses.php" class="<?= navActive('my_courses.php', $currentPage) ?>">&#128197; My Schedule</a></li>
        <?php endif; ?>
        <li><a href="contact.php" class="<?= navActive('contact.php', $currentPage) ?>">&#9742; Contact</a></li>
        <?php if ($isAdmin): ?>
        <li class="nav-employee">
            <a href="admin_dashboard.php" class="<?= navActive('admin_dashboard.php', $currentPage) ?>">&#9733; Admin Dashboard</a>
        </li>
        <?php endif; ?>

        <!-- Divider spacer -->
        <li style="flex:1;"></li>

        <!-- Right side -->
        <?php if ($isLoggedIn): ?>
        <li><a href="profile.php" class="<?= navActive('profile.php', $currentPage) ?>"> &#128100; My Profile</a></li>
        <li class="nav-logout"><a href="logout.php">&#10006; Logout</a></li>
        <?php else: ?>
        <li><a href="login.php"        class="nav-btn nav-btn-outline <?= navActive('login.php',        $currentPage) ?>">Login</a></li>
        <li><a href="registration.php" class="nav-btn nav-btn-solid   <?= navActive('registration.php', $currentPage) ?>">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    // Mobile nav toggle
    document.getElementById('navToggle').addEventListener('click', function() {
        document.getElementById('navLinks').classList.toggle('open');
    });
</script>

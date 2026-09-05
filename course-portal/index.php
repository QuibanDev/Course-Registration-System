<?php
error_reporting(E_ALL ^ E_NOTICE);
require 'db.php';
$conn = Database::getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Home</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:860px; margin:0 auto; padding:0 20px;">

<?php if ($isLoggedIn && !$isAdmin):
    // Quick stats for a logged-in student
    $stmt = $conn->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(c.credits),0) AS credits
                             FROM enrollments e JOIN courses c ON c.id = e.course_id
                             WHERE e.student_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
?>
    <!-- Logged-in student welcome -->
    <div class="ep-card text-center">
        <div class="profile-avatar-lg" style="width:80px;height:80px;line-height:80px;font-size:30px;">
            <?= strtoupper(substr($_SESSION['firstName'],0,1) . substr($_SESSION['lastName'],0,1)) ?>
        </div>
        <h2 style="font-size:26px; margin-bottom:6px;">
            Welcome back, <?= htmlspecialchars($_SESSION['firstName']) ?>!
        </h2>
        <p class="text-muted" style="margin-bottom:24px;">
            You're enrolled in <strong><?= (int)$stats['n'] ?></strong> course<?= $stats['n'] == 1 ? '' : 's' ?>
            (<strong><?= (int)$stats['credits'] ?></strong> credits this term).
        </p>
        <div class="flex flex-center gap-2" style="flex-wrap:wrap;">
            <a href="courses.php"    class="ep-btn ep-btn-primary">&#128218; Browse Courses</a>
            <a href="my_courses.php" class="ep-btn ep-btn-gold">&#128197; My Schedule</a>
            <a href="profile.php"    class="ep-btn ep-btn-outline">&#128100; My Profile</a>
        </div>
    </div>

<?php elseif ($isLoggedIn && $isAdmin): ?>
    <!-- Logged-in admin welcome -->
    <div class="ep-card text-center">
        <div class="profile-avatar-lg" style="width:80px;height:80px;line-height:80px;font-size:30px;">
            <?= strtoupper(substr($_SESSION['firstName'],0,1) . substr($_SESSION['lastName'],0,1)) ?>
        </div>
        <h2 style="font-size:26px; margin-bottom:6px;">
            Welcome back, <?= htmlspecialchars($_SESSION['firstName']) ?>!
        </h2>
        <p class="text-muted" style="margin-bottom:24px;">
            Manage the course catalog and monitor enrollment from the admin dashboard.
        </p>
        <div class="flex flex-center gap-2" style="flex-wrap:wrap;">
            <a href="admin_dashboard.php" class="ep-btn ep-btn-gold">&#9733; Admin Dashboard</a>
            <a href="courses.php"         class="ep-btn ep-btn-primary">&#128218; Browse Courses</a>
        </div>
    </div>

<?php else: ?>
    <!-- Guest landing -->
    <div class="ep-card text-center">
        <div class="auth-icon-wrap">&#127891;</div>
        <h2 style="font-size:28px; margin-bottom:10px;">Welcome to the Online Course Registration System</h2>
        <p class="text-muted" style="max-width:460px; margin:0 auto 28px;">
           Search Catalog | Register Classes | Manage Schedule
        </p>
        <div class="flex flex-center gap-2">
            <a href="login.php"        class="ep-btn ep-btn-primary">&#8594; Login</a>
            <a href="registration.php" class="ep-btn ep-btn-outline">Create Account</a>
        </div>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <div class="fc-icon">&#128269;</div>
            <h4>Live Course Search</h4>
            <p>Filter the catalog by department or keyword</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon">&#9989;</div>
            <h4>One-Click Registration</h4>
            <p>Enroll or drop a course instantly</p>
        </div>
        <div class="feature-card">
            <div class="fc-icon">&#128197;</div>
            <h4>Personal Schedule</h4>
            <p>Track your enrolled courses, credit load, and meeting times</p>
        </div>
    </div>

    <div class="ep-section-label" style="margin-top:50px;">Browse a Few Open Courses</div>
    <?php
    $preview = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS taken
                              FROM courses c ORDER BY c.code LIMIT 3");
    ?>
    <div class="resource-grid">
        <?php while ($c = $preview->fetch_assoc()):
            $seatsLeft = $c['capacity'] - $c['taken']; ?>
        <div class="resource-card">
            <div class="rc-icon">&#128218;</div>
            <h4><?= htmlspecialchars($c['code']) ?> &mdash; <?= htmlspecialchars($c['title']) ?></h4>
            <p><?= htmlspecialchars($c['instructor']) ?> &bull; <?= (int)$c['credits'] ?> credits<br>
               <?= $seatsLeft > 0 ? $seatsLeft . ' seats left' : 'Full' ?></p>
        </div>
        <?php endwhile; ?>
    </div>
    <p class="text-center mt-6">
        <a href="courses.php" class="ep-btn ep-btn-outline">See the full catalog &rarr;</a>
    </p>
<?php endif; ?>

</div>
</div>

<?php require 'footer.php'; ?>
</body>
</html>

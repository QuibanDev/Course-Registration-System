<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }

require 'db.php';
$conn = Database::getConnection();

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $s = $result->fetch_assoc();
} else {
    session_unset(); session_destroy(); header("Location: login.php"); exit();
}

// Enrollment summary (students only — admins don't enroll)
$stats = ['n' => 0, 'credits' => 0];
if ($s['role'] === 'student') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(c.credits),0) AS credits
                             FROM enrollments e JOIN courses c ON c.id = e.course_id
                             WHERE e.student_id = ?");
    $stmt->bind_param("i", $s['id']);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:700px; margin:0 auto; padding:0 20px;">
<div class="ep-card">

    <!-- Avatar + name -->
    <div class="text-center" style="margin-bottom:24px;">
        <div class="profile-avatar-lg">
            <?= strtoupper(substr($s['firstName'],0,1).substr($s['lastName'],0,1)) ?>
        </div>
        <h2 style="font-size:24px; margin-bottom:4px;">
            <?= htmlspecialchars($s['firstName'].' '.$s['lastName']) ?>
        </h2>
        <p class="text-muted" style="margin-bottom:10px;"><?= htmlspecialchars($s['email']) ?></p>
        <span class="role-badge">&#10003; <?= htmlspecialchars(ucfirst($s['role'])) ?></span>
    </div>

    <hr style="border:none; border-top:1px solid var(--grey-200); margin:24px 0;">

    <!-- Account Credentials -->
    <div class="ep-section-label">Account Credentials</div>
    <div class="row-2col mb-4">
        <div class="profile-field">
            <span class="profile-field-label">Email Address</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['email']) ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Student ID</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['studentId']) ?></div>
        </div>
    </div>
    <div class="profile-field mb-4">
        <span class="profile-field-label">Password</span>
        <div class="profile-field-value masked">
            &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
            &nbsp;<small style="letter-spacing:0; color:var(--grey-400); font-size:12px;">(bcrypt hash)</small>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="ep-section-label">Personal Information</div>
    <div class="row-2col mb-4">
        <div class="profile-field">
            <span class="profile-field-label">First Name</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['firstName']) ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Last Name</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['lastName']) ?></div>
        </div>
    </div>
    <div class="row-2col mb-4">
        <div class="profile-field">
            <span class="profile-field-label">Major</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['major'] ?: '—') ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Phone Number</span>
            <div class="profile-field-value"><?= htmlspecialchars($s['phone'] ?: '—') ?></div>
        </div>
    </div>

    <?php if ($s['role'] === 'student'): ?>
    <!-- Enrollment Summary -->
    <div class="ep-section-label">Enrollment Summary</div>
    <div class="row-2col mb-4">
        <div class="profile-field">
            <span class="profile-field-label">Courses Enrolled</span>
            <div class="profile-field-value"><?= (int)$stats['n'] ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Total Credits</span>
            <div class="profile-field-value"><?= (int)$stats['credits'] ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="profile-field mb-4">
        <span class="profile-field-label">Member Since</span>
        <div class="profile-field-value"><?= date('M j, Y', strtotime($s['created_at'])) ?></div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2" style="margin-top:32px; flex-wrap:wrap;">
        <?php if ($s['role'] === 'student'): ?>
        <a href="my_courses.php" class="ep-btn ep-btn-gold">&#128197; My Schedule</a>
        <a href="courses.php"    class="ep-btn ep-btn-primary">&#128218; Browse Courses</a>
        <?php else: ?>
        <a href="admin_dashboard.php" class="ep-btn ep-btn-gold">&#9733; Admin Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="ep-btn ep-btn-danger">&#10006; Logout</a>
    </div>

</div>
</div>
</div>

<?php require 'footer.php'; ?>
</body>
</html>

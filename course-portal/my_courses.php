<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
if ($_SESSION['role'] !== 'student') {
    require 'master.php';
    echo '<div class="page-content"><div class="container" style="max-width:500px;margin:0 auto;padding:0 20px;">
    <div class="ep-card text-center">
        <div style="font-size:56px;margin-bottom:16px;">&#128683;</div>
        <h2>Access Denied</h2>
        <p class="text-muted" style="margin:12px 0 24px;">Only students have a course schedule.</p>
        <a href="index.php" class="ep-btn ep-btn-outline">&#8592; Go Home</a>
    </div></div></div>';
    require 'footer.php'; exit();
}

require 'db.php';
$conn = Database::getConnection();

$stmt = $conn->prepare("SELECT c.*, e.enrolled_at
                         FROM enrollments e JOIN courses c ON c.id = e.course_id
                         WHERE e.student_id = ? ORDER BY c.department, c.code");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$myCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalCredits = array_sum(array_column($myCourses, 'credits'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Schedule</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:820px; margin:0 auto; padding:0 20px;">
<div class="ep-card">

    <div class="text-center" style="margin-bottom:8px;">
        <div class="auth-icon-wrap">&#128197;</div>
        <h2 style="font-size:26px; margin-bottom:6px;">My Schedule</h2>
        <p class="text-muted" style="margin-bottom:0;">
            <?= count($myCourses) ?> course<?= count($myCourses) === 1 ? '' : 's' ?> &bull; <?= (int)$totalCredits ?> credits this term
        </p>
    </div>

    <div id="dropAlert" style="display:none;" class="ep-alert mt-6"></div>

    <div id="scheduleList" style="margin-top:24px;">
        <?php if (empty($myCourses)): ?>
        <p class="text-center text-muted" style="padding:30px 0;">
            You haven't enrolled in any courses yet. <a href="courses.php">Browse the catalog</a> to get started.
        </p>
        <?php else: foreach ($myCourses as $c): ?>
        <div class="course-card" id="mycourse-<?= (int)$c['id'] ?>">
            <div class="course-card-main">
                <div class="course-card-head">
                    <span class="course-code"><?= htmlspecialchars($c['code']) ?></span>
                    <span class="course-dept-tag"><?= htmlspecialchars($c['department']) ?></span>
                </div>
                <h4 class="course-title"><?= htmlspecialchars($c['title']) ?></h4>
                <div class="course-meta">
                    <span>&#128100; <?= htmlspecialchars($c['instructor']) ?></span>
                    <span>&#128337; <?= htmlspecialchars($c['schedule']) ?></span>
                    <span>&#127891; <?= (int)$c['credits'] ?> credits</span>
                    <span>&#10003; Enrolled <?= date('M j, Y', strtotime($c['enrolled_at'])) ?></span>
                </div>
            </div>
            <div class="course-card-side">
                <button class="ep-btn ep-btn-danger enroll-btn" data-course="<?= (int)$c['id'] ?>">&#10006; Drop Course</button>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>
</div>
</div>

<style>
.course-card {
    display: flex; justify-content: space-between; align-items: center; gap: 24px;
    padding: 20px 6px; border-bottom: 1px solid var(--grey-200);
}
.course-card:last-child { border-bottom: none; }
.course-card-main { flex: 1; min-width: 0; }
.course-card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.course-code { font-weight: 700; color: var(--navy-600); font-size: 14px; letter-spacing: .03em; }
.course-dept-tag {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: var(--gold); background: var(--gold-pale); padding: 2px 10px; border-radius: 50px;
}
.course-title { font-family: 'Inter', sans-serif; font-size: 17px; font-weight: 700; color: var(--grey-800); margin-bottom: 6px; }
.course-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: 12.5px; color: var(--grey-600); }
.course-card-side { flex-shrink: 0; width: 160px; }
.enroll-btn { width: 100%; padding: 10px 16px; font-size: 13px; }
@media (max-width: 640px) {
    .course-card { flex-direction: column; align-items: stretch; }
    .course-card-side { width: 100%; }
}
</style>

<script>
function showAlert(message, isError) {
    const el = document.getElementById('dropAlert');
    el.innerHTML = '<span>' + (isError ? '&#9888;' : '&#10003;') + '</span><span></span>';
    el.querySelector('span:last-child').textContent = message;
    el.className = 'ep-alert mt-6 ' + (isError ? 'ep-alert-danger' : 'ep-alert-success');
    el.style.display = 'flex';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

document.getElementById('scheduleList').addEventListener('click', function(e) {
    const btn = e.target.closest('.enroll-btn');
    if (!btn) return;
    const courseId = btn.dataset.course;
    btn.disabled = true;
    btn.innerHTML = 'Removing...';

    fetch('enroll_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=drop&course_id=' + encodeURIComponent(courseId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('mycourse-' + courseId);
            card.style.transition = 'opacity .25s ease';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                if (!document.querySelector('.course-card')) {
                    document.getElementById('scheduleList').innerHTML =
                        '<p class="text-center text-muted" style="padding:30px 0;">You haven\'t enrolled in any courses yet. <a href="courses.php">Browse the catalog</a> to get started.</p>';
                }
            }, 250);
            showAlert(data.message, false);
        } else {
            showAlert(data.message, true);
            btn.disabled = false;
            btn.innerHTML = '&#10006; Drop Course';
        }
    })
    .catch(() => {
        showAlert('Something went wrong. Please try again.', true);
        btn.disabled = false;
        btn.innerHTML = '&#10006; Drop Course';
    });
});
</script>

<?php require 'footer.php'; ?>
</body>
</html>

<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }

require 'db.php';
$conn = Database::getConnection();

$isLoggedIn = isset($_SESSION['username']);
$isStudent  = $isLoggedIn && $_SESSION['role'] === 'student';

// All courses with seats-taken count
$courses = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS taken
                          FROM courses c ORDER BY c.department, c.code")->fetch_all(MYSQLI_ASSOC);

// Which course IDs is this student already enrolled in?
$enrolledIds = [];
if ($isStudent) {
    $stmt = $conn->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $enrolledIds[] = (int)$row['course_id']; }
}

$departments = array_values(array_unique(array_column($courses, 'department')));
sort($departments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Browse Courses</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:980px; margin:0 auto; padding:0 20px;">
<div class="ep-card">

    <div class="text-center" style="margin-bottom:8px;">
        <div class="auth-icon-wrap">&#128218;</div>
        <h2 style="font-size:26px; margin-bottom:6px;">Course Catalog</h2>
        <p class="text-muted" style="margin-bottom:20px;">
            <?= $isStudent ? 'Search and enroll in open courses below.' : ($isLoggedIn ? 'Browse the catalog. Only students can enroll in courses.' : 'Browse the catalog. Log in as a student to enroll.') ?>
        </p>
    </div>

    <div id="enrollAlert" style="display:none;" class="ep-alert"></div>

    <!-- Search / Filter Bar -->
    <div class="row-2col mb-4" style="align-items:end;">
        <div class="ep-form-group" style="margin-bottom:0;">
            <label class="ep-label" for="searchBox">Search</label>
            <div class="ep-input-wrap">
                <input class="ep-input" type="text" id="searchBox" placeholder="Course code, title, or instructor...">
                <span class="ep-input-icon">&#128269;</span>
            </div>
        </div>
        <div class="ep-form-group" style="margin-bottom:0;">
            <label class="ep-label" for="deptFilter">Department</label>
            <div class="ep-input-wrap">
                <select class="ep-input no-icon" id="deptFilter">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <p class="text-muted" id="resultCount" style="margin-bottom:16px; font-size:13px;"></p>

    <!-- Course List -->
    <div id="courseList">
        <?php foreach ($courses as $c):
            $seatsLeft = $c['capacity'] - $c['taken'];
            $isFull    = $seatsLeft <= 0;
            $isEnrolled = in_array((int)$c['id'], $enrolledIds, true);
            $pctFull   = $c['capacity'] > 0 ? min(100, round(($c['taken'] / $c['capacity']) * 100)) : 100;
        ?>
        <div class="course-card"
             data-search="<?= htmlspecialchars(strtolower($c['code'].' '.$c['title'].' '.$c['instructor'])) ?>"
             data-dept="<?= htmlspecialchars($c['department']) ?>"
             id="course-<?= (int)$c['id'] ?>">
            <div class="course-card-main">
                <div class="course-card-head">
                    <span class="course-code"><?= htmlspecialchars($c['code']) ?></span>
                    <span class="course-dept-tag"><?= htmlspecialchars($c['department']) ?></span>
                </div>
                <h4 class="course-title"><?= htmlspecialchars($c['title']) ?></h4>
                <p class="course-desc"><?= htmlspecialchars($c['description']) ?></p>
                <div class="course-meta">
                    <span>&#128100; <?= htmlspecialchars($c['instructor']) ?></span>
                    <span>&#128337; <?= htmlspecialchars($c['schedule']) ?></span>
                    <span>&#127891; <?= (int)$c['credits'] ?> credits</span>
                </div>
            </div>
            <div class="course-card-side">
                <div class="seat-bar-wrap">
                    <div class="seat-bar"><div class="seat-bar-fill" style="width:<?= $pctFull ?>%;"></div></div>
                    <span class="seat-count" id="seats-<?= (int)$c['id'] ?>">
                        <?= $isFull ? 'Full' : $seatsLeft . ' / ' . (int)$c['capacity'] . ' seats left' ?>
                    </span>
                </div>
                <?php if (!$isLoggedIn): ?>
                    <a href="login.php" class="ep-btn ep-btn-outline enroll-btn">Log in to Enroll</a>
                <?php elseif (!$isStudent): ?>
                    <span class="ep-btn ep-btn-outline enroll-btn" style="opacity:.5; cursor:default;">Admin View</span>
                <?php elseif ($isEnrolled): ?>
                    <button class="ep-btn ep-btn-danger enroll-btn" data-course="<?= (int)$c['id'] ?>" data-action="drop">&#10006; Drop</button>
                <?php elseif ($isFull): ?>
                    <span class="ep-btn ep-btn-outline enroll-btn" style="opacity:.5; cursor:default;">Full</span>
                <?php else: ?>
                    <button class="ep-btn ep-btn-primary enroll-btn" data-course="<?= (int)$c['id'] ?>" data-action="enroll">&#10003; Enroll</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p id="noResults" class="text-center text-muted" style="display:none; padding:30px 0;">No courses match your search.</p>

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
.course-desc { font-size: 13px; color: var(--grey-600); line-height: 1.5; margin-bottom: 10px; max-width: 560px; }
.course-meta { display: flex; gap: 18px; flex-wrap: wrap; font-size: 12.5px; color: var(--grey-600); }
.course-card-side { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; width: 190px; }
.seat-bar-wrap { width: 100%; text-align: right; }
.seat-bar { width: 100%; height: 6px; background: var(--grey-200); border-radius: 4px; overflow: hidden; margin-bottom: 5px; }
.seat-bar-fill { height: 100%; background: linear-gradient(90deg, var(--navy-500), var(--gold)); border-radius: 4px; transition: width .3s ease; }
.seat-count { font-size: 12px; color: var(--grey-600); font-weight: 600; }
.enroll-btn { width: 100%; padding: 10px 16px; font-size: 13px; }
@media (max-width: 640px) {
    .course-card { flex-direction: column; align-items: stretch; }
    .course-card-side { width: 100%; align-items: stretch; }
    .seat-bar-wrap { text-align: left; }
}
</style>

<script>
const searchBox   = document.getElementById('searchBox');
const deptFilter  = document.getElementById('deptFilter');
const courseList  = document.getElementById('courseList');
const noResults   = document.getElementById('noResults');
const resultCount = document.getElementById('resultCount');
const cards       = Array.from(document.querySelectorAll('.course-card'));

function applyFilters() {
    const q    = searchBox.value.trim().toLowerCase();
    const dept = deptFilter.value;
    let visible = 0;
    cards.forEach(card => {
        const matchesSearch = !q || card.dataset.search.includes(q);
        const matchesDept   = !dept || card.dataset.dept === dept;
        const show = matchesSearch && matchesDept;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    noResults.style.display = visible === 0 ? '' : 'none';
    resultCount.textContent = visible + ' course' + (visible === 1 ? '' : 's') + ' found';
}
searchBox.addEventListener('input', applyFilters);
deptFilter.addEventListener('change', applyFilters);
applyFilters();

function showAlert(message, isError) {
    const el = document.getElementById('enrollAlert');
    el.innerHTML = '<span>' + (isError ? '&#9888;' : '&#10003;') + '</span><span></span>';
    el.querySelector('span:last-child').textContent = message;
    el.className = 'ep-alert ' + (isError ? 'ep-alert-danger' : 'ep-alert-success');
    el.style.display = 'flex';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

courseList.addEventListener('click', function(e) {
    const btn = e.target.closest('.enroll-btn[data-action]');
    if (!btn) return;
    const courseId = btn.dataset.course;
    const action    = btn.dataset.action;
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Please wait...';

    fetch('enroll_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=' + encodeURIComponent(action) + '&course_id=' + encodeURIComponent(courseId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, false);
            const seatsEl = document.getElementById('seats-' + courseId);
            if (seatsEl) {
                seatsEl.textContent = data.full ? 'Full' : data.seatsLeft + ' / ' + data.capacity + ' seats left';
            }
            const fillEl = btn.closest('.course-card').querySelector('.seat-bar-fill');
            if (fillEl) fillEl.style.width = data.pctFull + '%';

            if (action === 'enroll') {
                btn.dataset.action = 'drop';
                btn.className = 'ep-btn ep-btn-danger enroll-btn';
                btn.innerHTML = '&#10006; Drop';
                btn.disabled = false;
            } else {
                btn.dataset.action = 'enroll';
                btn.className = 'ep-btn ep-btn-primary enroll-btn';
                btn.innerHTML = '&#10003; Enroll';
                btn.disabled = data.full;
                if (data.full) { btn.outerHTML = '<span class="ep-btn ep-btn-outline enroll-btn" style="opacity:.5; cursor:default;">Full</span>'; }
            }
        } else {
            showAlert(data.message, true);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(() => {
        showAlert('Something went wrong. Please try again.', true);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});
</script>

<?php require 'footer.php'; ?>
</body>
</html>

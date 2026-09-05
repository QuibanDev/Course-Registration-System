<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') {
    require 'master.php';
    echo '<div class="page-content"><div class="container" style="max-width:500px;margin:0 auto;padding:0 20px;">
    <div class="ep-card text-center">
        <div style="font-size:56px;margin-bottom:16px;">&#128683;</div>
        <h2>Access Denied</h2>
        <p class="text-muted" style="margin:12px 0 24px;">This page is restricted to administrators only.</p>
        <a href="index.php" class="ep-btn ep-btn-outline">&#8592; Go Home</a>
    </div></div></div>';
    require 'footer.php'; exit();
}

require 'db.php';
$conn = Database::getConnection();

$courses = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS taken
                          FROM courses c ORDER BY c.department, c.code")->fetch_all(MYSQLI_ASSOC);
$totalStudents    = $conn->query("SELECT COUNT(*) AS n FROM students WHERE role='student'")->fetch_assoc()['n'];
$totalEnrollments = $conn->query("SELECT COUNT(*) AS n FROM enrollments")->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:980px; margin:0 auto; padding:0 20px;">
<div class="ep-card">

    <div class="text-center" style="margin-bottom:24px;">
        <div class="auth-icon-wrap" style="background:linear-gradient(135deg,#7c5a0a,#c9a84c);">&#9733;</div>
        <h2 style="font-size:26px; margin-bottom:8px;">Admin Dashboard</h2>
        <span class="role-badge">&#10003; Role: Administrator — Access Granted</span>
    </div>

    <div id="adminAlert" style="display:none;" class="ep-alert mb-4"></div>

    <!-- Quick Stats -->
    <div class="row-2col mb-4" style="grid-template-columns: repeat(3,1fr);">
        <div class="profile-field">
            <span class="profile-field-label">Courses in Catalog</span>
            <div class="profile-field-value" id="statCourses" style="font-size:20px; font-weight:700;"><?= count($courses) ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Registered Students</span>
            <div class="profile-field-value" style="font-size:20px; font-weight:700;"><?= (int)$totalStudents ?></div>
        </div>
        <div class="profile-field">
            <span class="profile-field-label">Total Enrollments</span>
            <div class="profile-field-value" id="statEnrollments" style="font-size:20px; font-weight:700;"><?= (int)$totalEnrollments ?></div>
        </div>
    </div>

    <!-- Add Course -->
    <div class="ep-section-label" style="margin-top:36px;">
        <span style="flex:1;">Add a New Course</span>
    </div>
    <form id="addCourseForm">
        <div class="row-2col">
            <div class="ep-form-group">
                <label class="ep-label">Course Code <span class="req">*</span></label>
                <input class="ep-input no-icon" type="text" name="code" placeholder="e.g. CS101" required>
            </div>
            <div class="ep-form-group">
                <label class="ep-label">Department <span class="req">*</span></label>
                <input class="ep-input no-icon" type="text" name="department" placeholder="e.g. Computer Science" required>
            </div>
        </div>
        <div class="ep-form-group">
            <label class="ep-label">Course Title <span class="req">*</span></label>
            <input class="ep-input no-icon" type="text" name="title" placeholder="e.g. Introduction to Programming" required>
        </div>
        <div class="ep-form-group">
            <label class="ep-label">Description</label>
            <textarea class="ep-input no-icon" name="description" rows="2" placeholder="Short course description"></textarea>
        </div>
        <div class="row-2col">
            <div class="ep-form-group">
                <label class="ep-label">Instructor <span class="req">*</span></label>
                <input class="ep-input no-icon" type="text" name="instructor" placeholder="e.g. Dr. Jane Smith" required>
            </div>
            <div class="ep-form-group">
                <label class="ep-label">Schedule</label>
                <input class="ep-input no-icon" type="text" name="schedule" placeholder="e.g. MWF 9:00 - 9:50 AM">
            </div>
        </div>
        <div class="row-2col">
            <div class="ep-form-group">
                <label class="ep-label">Credits</label>
                <input class="ep-input no-icon" type="number" name="credits" value="3" min="1" max="6">
            </div>
            <div class="ep-form-group">
                <label class="ep-label">Capacity <span class="req">*</span></label>
                <input class="ep-input no-icon" type="number" name="capacity" value="30" min="1" required>
            </div>
        </div>
        <button type="submit" class="ep-btn ep-btn-primary ep-btn-block">&#10003;&nbsp; Add Course</button>
    </form>

    <!-- Course Table -->
    <div class="ep-section-label" style="margin-top:40px;">Manage Catalog</div>
    <div id="courseTable">
        <?php foreach ($courses as $c):
            $seatsLeft = $c['capacity'] - $c['taken']; ?>
        <div class="admin-course-row" id="admin-course-<?= (int)$c['id'] ?>" data-id="<?= (int)$c['id'] ?>">
            <div class="admin-course-view">
                <div class="admin-course-main">
                    <div class="course-card-head">
                        <span class="course-code"><?= htmlspecialchars($c['code']) ?></span>
                        <span class="course-dept-tag"><?= htmlspecialchars($c['department']) ?></span>
                    </div>
                    <h4 class="course-title" data-field="title"><?= htmlspecialchars($c['title']) ?></h4>
                    <div class="course-meta">
                        <span>&#128100; <span data-field="instructor"><?= htmlspecialchars($c['instructor']) ?></span></span>
                        <span>&#128337; <span data-field="schedule"><?= htmlspecialchars($c['schedule']) ?></span></span>
                        <span>&#127891; <span data-field="credits"><?= (int)$c['credits'] ?></span> credits</span>
                        <span>&#128101; <span class="taken-count"><?= (int)$c['taken'] ?></span> / <span data-field="capacity"><?= (int)$c['capacity'] ?></span> enrolled</span>
                    </div>
                </div>
                <div class="admin-course-actions">
                    <button class="ep-btn ep-btn-outline btn-sm" data-act="roster">&#128101; Roster</button>
                    <button class="ep-btn ep-btn-outline btn-sm" data-act="edit">&#9998; Edit</button>
                    <button class="ep-btn ep-btn-danger btn-sm" data-act="delete">&#10006; Delete</button>
                </div>
            </div>
            <div class="admin-course-edit" style="display:none;">
                <div class="row-2col">
                    <div class="ep-form-group">
                        <label class="ep-label">Title</label>
                        <input class="ep-input no-icon" type="text" data-edit="title" value="<?= htmlspecialchars($c['title']) ?>">
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-label">Instructor</label>
                        <input class="ep-input no-icon" type="text" data-edit="instructor" value="<?= htmlspecialchars($c['instructor']) ?>">
                    </div>
                </div>
                <div class="row-2col">
                    <div class="ep-form-group">
                        <label class="ep-label">Schedule</label>
                        <input class="ep-input no-icon" type="text" data-edit="schedule" value="<?= htmlspecialchars($c['schedule']) ?>">
                    </div>
                    <div class="ep-form-group">
                        <label class="ep-label">Credits</label>
                        <input class="ep-input no-icon" type="number" data-edit="credits" value="<?= (int)$c['credits'] ?>" min="1" max="6">
                    </div>
                </div>
                <div class="ep-form-group" style="max-width:200px;">
                    <label class="ep-label">Capacity</label>
                    <input class="ep-input no-icon" type="number" data-edit="capacity" value="<?= (int)$c['capacity'] ?>" min="1">
                </div>
                <div class="flex gap-2">
                    <button class="ep-btn ep-btn-primary btn-sm" data-act="save">&#10003; Save</button>
                    <button class="ep-btn ep-btn-outline btn-sm" data-act="cancel">Cancel</button>
                </div>
            </div>
            <div class="admin-course-roster" style="display:none;"></div>
        </div>
        <?php endforeach; ?>
    </div>
    <p id="noCourses" class="text-center text-muted" style="<?= count($courses) ? 'display:none;' : '' ?> padding:30px 0;">
        No courses in the catalog yet. Add one above.
    </p>

</div>
</div>
</div>

<style>
.admin-course-row { padding: 18px 6px; border-bottom: 1px solid var(--grey-200); }
.admin-course-row:last-child { border-bottom: none; }
.admin-course-view { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.admin-course-main { flex: 1; min-width: 0; }
.admin-course-actions { display: flex; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.btn-sm { padding: 8px 14px; font-size: 12.5px; }
.admin-course-edit { margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--grey-200); }
.admin-course-roster { margin-top: 14px; padding: 14px 18px; background: var(--grey-100); border-radius: var(--radius-sm); }
.roster-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px solid var(--grey-200); }
.roster-row:last-child { border-bottom: none; }
@media (max-width: 640px) {
    .admin-course-view { flex-direction: column; align-items: stretch; }
}
</style>

<script>
function showAlert(message, isError) {
    const el = document.getElementById('adminAlert');
    el.innerHTML = '<span>' + (isError ? '&#9888;' : '&#10003;') + '</span><span></span>';
    el.querySelector('span:last-child').textContent = message;
    el.className = 'ep-alert mb-4 ' + (isError ? 'ep-alert-danger' : 'ep-alert-success');
    el.style.display = 'flex';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// ── Add Course ──────────────────────────────────────────────
document.getElementById('addCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.innerHTML = 'Adding...';

    const params = new URLSearchParams(new FormData(form));
    params.append('action', 'add_course');

    fetch('admin_action.php', { method: 'POST', body: params })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, false);
                setTimeout(() => location.reload(), 700);
            } else {
                showAlert(data.message, true);
                btn.disabled = false; btn.innerHTML = '&#10003;&nbsp; Add Course';
            }
        })
        .catch(() => { showAlert('Something went wrong.', true); btn.disabled = false; btn.innerHTML = '&#10003;&nbsp; Add Course'; });
});

// ── Table actions: edit / save / cancel / delete / roster ─────
document.getElementById('courseTable').addEventListener('click', function(e) {
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    const row = btn.closest('.admin-course-row');
    const id  = row.dataset.id;
    const act = btn.dataset.act;

    if (act === 'edit') {
        row.querySelector('.admin-course-edit').style.display = '';
        row.querySelector('.admin-course-roster').style.display = 'none';
    }

    if (act === 'cancel') {
        row.querySelector('.admin-course-edit').style.display = 'none';
    }

    if (act === 'save') {
        const edit = row.querySelector('.admin-course-edit');
        const fields = {};
        edit.querySelectorAll('[data-edit]').forEach(inp => fields[inp.dataset.edit] = inp.value);

        fetch('admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'edit_course', id, ...fields })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, false);
                row.querySelector('[data-field="title"]').textContent = fields.title;
                row.querySelector('[data-field="instructor"]').textContent = fields.instructor;
                row.querySelector('[data-field="schedule"]').textContent = fields.schedule;
                row.querySelector('[data-field="credits"]').textContent = fields.credits;
                row.querySelector('[data-field="capacity"]').textContent = fields.capacity;
                edit.style.display = 'none';
            } else {
                showAlert(data.message, true);
            }
        })
        .catch(() => showAlert('Something went wrong.', true));
    }

    if (act === 'delete') {
        if (!confirm('Remove this course from the catalog? This will also unenroll every student in it.')) return;
        fetch('admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete_course&id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                row.style.transition = 'opacity .25s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    const remaining = document.querySelectorAll('.admin-course-row').length;
                    document.getElementById('statCourses').textContent = remaining;
                    if (remaining === 0) document.getElementById('noCourses').style.display = '';
                }, 250);
                showAlert(data.message, false);
            } else {
                showAlert(data.message, true);
            }
        })
        .catch(() => showAlert('Something went wrong.', true));
    }

    if (act === 'roster') {
        const rosterEl = row.querySelector('.admin-course-roster');
        if (rosterEl.style.display !== 'none') { rosterEl.style.display = 'none'; return; }
        row.querySelector('.admin-course-edit').style.display = 'none';
        rosterEl.style.display = '';
        rosterEl.innerHTML = 'Loading roster...';

        fetch('admin_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_roster&id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { rosterEl.innerHTML = '<em>Could not load roster.</em>'; return; }
            if (data.roster.length === 0) { rosterEl.innerHTML = '<em>No students enrolled yet.</em>'; return; }
            const esc = str => String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            rosterEl.innerHTML = data.roster.map(s =>
                `<div class="roster-row"><span>${esc(s.firstName)} ${esc(s.lastName)} (${esc(s.studentId)})</span><span>${esc(s.email)}</span></div>`
            ).join('');
        })
        .catch(() => { rosterEl.innerHTML = '<em>Could not load roster.</em>'; });
    }
});
</script>

<?php require 'footer.php'; ?>
</body>
</html>

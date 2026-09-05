<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }
header('Content-Type: application/json');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    respond(false, 'Admin access required.');
}

require 'db.php';
$conn = Database::getConnection();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'add_course': {
        $code       = trim($_POST['code'] ?? '');
        $title      = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $instructor = trim($_POST['instructor'] ?? '');
        $credits    = (int) ($_POST['credits'] ?? 3);
        $capacity   = (int) ($_POST['capacity'] ?? 30);
        $schedule   = trim($_POST['schedule'] ?? '');

        if ($code === '' || $title === '' || $department === '' || $instructor === '' || $capacity < 1) {
            respond(false, 'Please fill in course code, title, department, instructor, and a valid capacity.');
        }

        try {
            $stmt = $conn->prepare("INSERT INTO courses (code,title,description,department,instructor,credits,capacity,schedule)
                                     VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssiis", $code, $title, $description, $department, $instructor, $credits, $capacity, $schedule);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) respond(false, 'A course with that code already exists.');
            respond(false, 'Could not add the course.');
        }

        respond(true, 'Course added to the catalog.', ['courseId' => $conn->insert_id]);
    }

    case 'edit_course': {
        $id         = (int) ($_POST['id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $instructor = trim($_POST['instructor'] ?? '');
        $credits    = (int) ($_POST['credits'] ?? 3);
        $capacity   = (int) ($_POST['capacity'] ?? 30);
        $schedule   = trim($_POST['schedule'] ?? '');

        if ($id <= 0 || $title === '' || $instructor === '' || $capacity < 1) {
            respond(false, 'Please provide a valid title, instructor, and capacity.');
        }

        // Don't let capacity drop below the number of students already enrolled
        $stmt = $conn->prepare("SELECT COUNT(*) AS taken FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $taken = (int) $stmt->get_result()->fetch_assoc()['taken'];
        if ($capacity < $taken) {
            respond(false, "Capacity can't be less than the $taken student(s) currently enrolled.");
        }

        $stmt = $conn->prepare("UPDATE courses SET title=?, instructor=?, credits=?, capacity=?, schedule=? WHERE id=?");
        $stmt->bind_param("ssiisi", $title, $instructor, $credits, $capacity, $schedule, $id);
        $stmt->execute();

        respond(true, 'Course updated.', [
            'taken' => $taken,
            'seatsLeft' => max(0, $capacity - $taken),
        ]);
    }

    case 'delete_course': {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) respond(false, 'Invalid course.');

        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        respond(true, 'Course removed from the catalog.');
    }

    case 'get_roster': {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) respond(false, 'Invalid course.');

        $stmt = $conn->prepare("SELECT s.firstName, s.lastName, s.studentId, s.email, e.enrolled_at
                                 FROM enrollments e JOIN students s ON s.id = e.student_id
                                 WHERE e.course_id = ? ORDER BY s.lastName, s.firstName");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $roster = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        respond(true, '', ['roster' => $roster]);
    }

    default:
        http_response_code(400);
        respond(false, 'Unknown action.');
}

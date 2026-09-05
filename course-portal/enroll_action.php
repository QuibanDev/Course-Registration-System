<?php
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) { ini_set('session.use_only_cookies','1'); session_start(); }
header('Content-Type: application/json');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    respond(false, 'You must be logged in as a student to do that.');
}

require 'db.php';
$conn = Database::getConnection();

$action    = $_POST['action']    ?? '';
$courseId  = (int) ($_POST['course_id'] ?? 0);
$studentId = (int) $_SESSION['id'];

if (!in_array($action, ['enroll', 'drop'], true) || $courseId <= 0) {
    http_response_code(400);
    respond(false, 'Invalid request.');
}

// Fetch the course
$stmt = $conn->prepare("SELECT id, capacity FROM courses WHERE id = ?");
$stmt->bind_param("i", $courseId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    http_response_code(404);
    respond(false, 'Course not found.');
}

if ($action === 'enroll') {
    // Lock the enrollment count check + insert together to avoid overbooking
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS taken FROM enrollments WHERE course_id = ? FOR UPDATE");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $taken = (int) $stmt->get_result()->fetch_assoc()['taken'];

        if ($taken >= $course['capacity']) {
            $conn->rollback();
            respond(false, 'Sorry, that course just filled up.', ['full' => true, 'seatsLeft' => 0, 'capacity' => (int)$course['capacity'], 'pctFull' => 100]);
        }

        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if ($e->getCode() === 1062) {
            respond(false, 'You are already enrolled in this course.');
        }
        respond(false, 'Could not complete enrollment. Please try again.');
    }

    $taken     = $taken + 1;
    $seatsLeft = max(0, $course['capacity'] - $taken);
    respond(true, 'You are now enrolled!', [
        'full'      => $seatsLeft <= 0,
        'seatsLeft' => $seatsLeft,
        'capacity'  => (int) $course['capacity'],
        'pctFull'   => $course['capacity'] > 0 ? min(100, round(($taken / $course['capacity']) * 100)) : 100,
    ]);
} else {
    // Drop
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        respond(false, 'You are not enrolled in this course.');
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS taken FROM enrollments WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $taken     = (int) $stmt->get_result()->fetch_assoc()['taken'];
    $seatsLeft = max(0, $course['capacity'] - $taken);

    respond(true, 'Course dropped from your schedule.', [
        'full'      => $seatsLeft <= 0,
        'seatsLeft' => $seatsLeft,
        'capacity'  => (int) $course['capacity'],
        'pctFull'   => $course['capacity'] > 0 ? min(100, round(($taken / $course['capacity']) * 100)) : 100,
    ]);
}

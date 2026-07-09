<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized action context.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_data') {
        echo json_encode([
            'students' => $pdo->query("SELECT * FROM students")->fetchAll(),
            'instructors' => $pdo->query("SELECT * FROM instructors")->fetchAll(),
            'courses' => $pdo->query("SELECT * FROM courses")->fetchAll(),
            'enrollments' => $pdo->query("SELECT * FROM enrollments")->fetchAll(),
            'grades' => $pdo->query("SELECT * FROM grades")->fetchAll(),
            'attendance' => $pdo->query("SELECT * FROM attendance")->fetchAll()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    try {
        if ($type === 'student') {
            $stmt = $pdo->prepare("INSERT INTO students (first_name, last_name, date_of_birth, gender, email, phone, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$input['first_name'], $input['last_name'], $input['date_of_birth'], $input['gender'], $input['email'], $input['phone'], date('Y-m-d'), $input['status']]);
        } elseif ($type === 'instructor') {
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, last_name, email, department) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['first_name'], $input['last_name'], $input['email'], $input['department']]);
        } elseif ($type === 'course') {
            $stmt = $pdo->prepare("INSERT INTO courses (course_id, course_name, credits, instructor_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([strtoupper($input['course_id']), $input['course_name'], $input['credits'], $input['instructor_id']]);
        } elseif ($type === 'enrollment') {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, semester, enroll_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['student_id'], $input['course_id'], $input['semester'], date('Y-m-d')]);
        } elseif ($type === 'grade') {
            $stmt = $pdo->prepare("INSERT INTO grades (enrollment_id, gpa_marks, letter_grade) VALUES (?, ?, ?)");
            $stmt->execute([$input['enrollment_id'], $input['gpa_marks'], strtoupper($input['letter_grade'])]);
        } elseif ($type === 'attendance') {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, date, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['student_id'], $input['course_id'], $input['date'], $input['status']]);
        }
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
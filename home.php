<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$max_idle_seconds = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_idle_seconds)) {
    log_system_event($conn, $_SESSION['username'], 'SESSION_TIMEOUT_EXPIRED');
    session_unset();
    session_destroy();
    header("Location: index.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System (SMS)</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <h2>SMS Admin</h2>
            <p style="color:#94a3b8; font-size:12px; text-align:center; margin-bottom:15px;">User: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <nav>
                <button class="nav-btn active" onclick="switchTab('students-tab')">Students</button>
                <button class="nav-btn" onclick="switchTab('instructors-tab')">Instructors</button>
                <button class="nav-btn" onclick="switchTab('courses-tab')">Courses</button>
                <button class="nav-btn" onclick="switchTab('enrollments-tab')">Enrollments</button>
                <button class="nav-btn" onclick="switchTab('grades-tab')">Grades</button>
                <button class="nav-btn" onclick="switchTab('attendance-tab')">Attendance</button>
                <a href="logout.php" style="display:block; text-align:center; margin-top:30px; background:#ef4444; color:white; text-decoration:none; padding:10px; border-radius:4px; font-weight:bold; font-size:13px;">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <section id="students-tab" class="tab-content active">
                <div class="tab-header"><h2>Student Profiles</h2><button class="btn-primary" onclick="openModal('student-modal')">+ Add Student</button></div>
                <table id="students-table"><thead><tr><th>ID</th><th>Name</th><th>DOB</th><th>Gender</th><th>Email</th><th>Phone</th><th>Enrollment Date</th><th>Status</th></tr></thead><tbody></tbody></table>
            </section>
            <section id="instructors-tab" class="tab-content">
                <div class="tab-header"><h2>Faculty Members</h2><button class="btn-primary" onclick="openModal('instructor-modal')">+ Add Instructor</button></div>
                <table id="instructors-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th></tr></thead><tbody></tbody></table>
            </section>
            <section id="courses-tab" class="tab-content">
                <div class="tab-header"><h2>Course Catalog</h2><button class="btn-primary" onclick="openModal('course-modal')">+ Add Course</button></div>
                <table id="courses-table"><thead><tr><th>Course Code</th><th>Course Name</th><th>Credits</th><th>Instructor</th></tr></thead><tbody></tbody></table>
            </section>
            <section id="enrollments-tab" class="tab-content">
                <div class="tab-header"><h2>Course Enrollments</h2><button class="btn-primary" onclick="openModal('enrollment-modal')">+ Register Student</button></div>
                <table id="enrollments-table"><thead><tr><th>Enrollment ID</th><th>Student</th><th>Course</th><th>Semester</th><th>Registration Date</th></tr></thead><tbody></tbody></table>
            </section>
            <section id="grades-tab" class="tab-content">
                <div class="tab-header"><h2>Academic Grades</h2><button class="btn-primary" onclick="openModal('grade-modal')">+ Record Grade</button></div>
                <table id="grades-table"><thead><tr><th>Grade ID</th><th>Student</th><th>Course</th><th>GPA Score</th><th>Letter Grade</th></tr></thead><tbody></tbody></table>
            </section>
            <section id="attendance-tab" class="tab-content">
                <div class="tab-header"><h2>Attendance Records</h2><button class="btn-primary" onclick="openModal('attendance-modal')">+ Log Attendance</button></div>
                <table id="attendance-table"><thead><tr><th>Attendance ID</th><th>Student</th><th>Course</th><th>Date</th><th>Status</th></tr></thead><tbody></tbody></table>
            </section>
        </main>
    </div>

    <div id="student-modal" class="modal"><div class="modal-content"><h3>Add New Student</h3><form id="student-form" onsubmit="handleFormSubmit(event, 'student')"><label>First Name:</label><input type="text" id="s-first" required><label>Last Name:</label><input type="text" id="s-last" required><label>Date of Birth:</label><input type="date" id="s-dob" required><label>Gender:</label><select id="s-gender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option><option value="Prefer Not to Say">Prefer Not to Say</option></select><label>Email:</label><input type="email" id="s-email" required><label>Phone Number:</label><input type="text" id="s-phone"><label>Status:</label><select id="s-status"><option value="Active">Active</option><option value="Graduated">Graduated</option><option value="Suspended">Suspended</option><option value="Withdrawn">Withdrawn</option></select><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('student-modal')">Cancel</button><button type="submit" class="btn-primary">Save Student</button></div></form></div></div>
    <div id="instructor-modal" class="modal"><div class="modal-content"><h3>Add Faculty Member</h3><form id="instructor-form" onsubmit="handleFormSubmit(event, 'instructor')"><label>First Name:</label><input type="text" id="i-first" required><label>Last Name:</label><input type="text" id="i-last" required><label>Email:</label><input type="email" id="i-email" required><label>Department:</label><input type="text" id="i-dept" required><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('instructor-modal')">Cancel</button><button type="submit" class="btn-primary">Save Instructor</button></div></form></div></div>
    <div id="course-modal" class="modal"><div class="modal-content"><h3>Create Course</h3><form id="course-form" onsubmit="handleFormSubmit(event, 'course')"><label>Course Code:</label><input type="text" id="c-id" placeholder="e.g., CS-101" required><label>Course Name:</label><input type="text" id="c-name" required><label>Credits:</label><input type="number" id="c-credits" value="3" min="1" max="5" required><label>Instructor Assignment:</label><select id="c-instructor" required><option value="">-- Choose Instructor --</option></select><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('course-modal')">Cancel</button><button type="submit" class="btn-primary">Save Course</button></div></form></div></div>
    <div id="enrollment-modal" class="modal"><div class="modal-content"><h3>New Enrollment</h3><form id="enrollment-form" onsubmit="handleFormSubmit(event, 'enrollment')"><label>Select Student:</label><select id="e-student" required><option value="">-- Select Student --</option></select><label>Select Course:</label><select id="e-course" required><option value="">-- Select Course --</option></select><label>Semester Term:</label><input type="text" id="e-semester" placeholder="e.g., Fall 2026" required><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('enrollment-modal')">Cancel</button><button type="submit" class="btn-primary">Register</button></div></form></div></div>
    <div id="grade-modal" class="modal"><div class="modal-content"><h3>Log Final Grade</h3><form id="grade-form" onsubmit="handleFormSubmit(event, 'grade')"><label>Select Ungraded Enrollment:</label><select id="g-enrollment" required><option value="">-- Select Enrollment Record --</option></select><label>GPA Score:</label><input type="number" step="0.01" id="g-marks" min="0" max="4" required><label>Letter Grade:</label><input type="text" id="g-letter" maxlength="3" required><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('grade-modal')">Cancel</button><button type="submit" class="btn-primary">Submit Grade</button></div></form></div></div>
    <div id="attendance-modal" class="modal"><div class="modal-content"><h3>Log Session Attendance</h3><form id="attendance-form" onsubmit="handleFormSubmit(event, 'attendance')"><label>Select Active Registration Reference:</label><select id="a-enrollment" required onchange="syncAttendanceFields()"><option value="">-- Select Student Course Group --</option></select><input type="hidden" id="a-student"><input type="hidden" id="a-course"><label>Session Date:</label><input type="date" id="a-date" required><label>Status:</label><select id="a-status"><option value="Present">Present</option><option value="Absent">Absent</option><option value="Tardy">Tardy</option><option value="Excused">Excused</option></select><div class="modal-actions"><button type="button" class="btn-secondary" onclick="closeModal('attendance-modal')">Cancel</button><button type="submit" class="btn-primary">Save Entry</button></div></form></div></div>
    
    <script src="script.js"></script>
</body>
</html>
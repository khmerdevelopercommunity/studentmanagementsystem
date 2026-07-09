let appData = {};

document.addEventListener("DOMContentLoaded", () => {
    fetchData();
});

function switchTab(tabId) {
    document.querySelectorAll(".tab-content").forEach(el => el.classList.remove("active"));
    document.querySelectorAll(".nav-btn").forEach(el => el.classList.remove("active"));
    
    document.getElementById(tabId).classList.add("active");
    event.currentTarget.classList.add("active");
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add("active");
    if (modalId === 'course-modal') populateInstructorDropdown();
    if (modalId === 'enrollment-modal') populateEnrollmentDropdowns();
    if (modalId === 'grade-modal') populateGradeDropdown();
    if (modalId === 'attendance-modal') populateAttendanceDropdown();
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove("active");
}

function fetchData() {
    fetch("api.php?action=get_data")
        .then(res => res.json())
        .then(data => {
            appData = data;
            renderTables();
        })
        .catch(err => console.error("Data fetch anomaly:", err));
}

function renderTables() {
    // 1. Students Row Mapping
    const sBody = document.querySelector("#students-table tbody");
    sBody.innerHTML = appData.students.map(s => `<tr>
        <td>${s.student_id}</td>
        <td>${s.first_name} ${s.last_name}</td>
        <td>${s.date_of_birth}</td>
        <td>${s.gender}</td>
        <td>${s.email}</td>
        <td>${s.phone || '-'}</td>
        <td>${s.enrollment_date}</td>
        <td><span style="color:${s.status==='Active'?'#10b981':'#ef4444'}">${s.status}</span></td>
    </tr>`).join('');

    // 2. Instructors Row Mapping
    const iBody = document.querySelector("#instructors-table tbody");
    iBody.innerHTML = appData.instructors.map(i => `<tr>
        <td>${i.instructor_id}</td>
        <td>${i.first_name} ${i.last_name}</td>
        <td>${i.email}</td>
        <td>${i.department}</td>
    </tr>`).join('');

    // 3. Courses Row Mapping
    const cBody = document.querySelector("#courses-table tbody");
    cBody.innerHTML = appData.courses.map(c => {
        const inst = appData.instructors.find(i => i.instructor_id == c.instructor_id);
        return `<tr>
            <td>${c.course_id}</td>
            <td>${c.course_name}</td>
            <td>${c.credits}</td>
            <td>${inst ? inst.first_name + ' ' + inst.last_name : 'Unassigned'}</td>
        </tr>`;
    }).join('');

    // 4. Enrollments Row Mapping
    const eBody = document.querySelector("#enrollments-table tbody");
    eBody.innerHTML = appData.enrollments.map(e => {
        const stud = appData.students.find(s => s.student_id == e.student_id);
        return `<tr>
            <td>${e.enrollment_id}</td>
            <td>${stud ? stud.first_name + ' ' + stud.last_name : 'Unknown ID: ' + e.student_id}</td>
            <td>${e.course_id}</td>
            <td>${e.semester}</td>
            <td>${e.enroll_date}</td>
        </tr>`;
    }).join('');

    // 5. Grades Row Mapping
    const gBody = document.querySelector("#grades-table tbody");
    gBody.innerHTML = appData.grades.map(g => {
        const enroll = appData.enrollments.find(e => e.enrollment_id == g.enrollment_id);
        const stud = enroll ? appData.students.find(s => s.student_id == enroll.student_id) : null;
        return `<tr>
            <td>${g.grade_id}</td>
            <td>${stud ? stud.first_name + ' ' + stud.last_name : '-'}</td>
            <td>${enroll ? enroll.course_id : '-'}</td>
            <td><strong>${g.gpa_marks}</strong></td>
            <td>${g.letter_grade}</td>
        </tr>`;
    }).join('');

    // 6. Attendance Row Mapping
    const aBody = document.querySelector("#attendance-table tbody");
    aBody.innerHTML = appData.attendance.map(a => {
        const stud = appData.students.find(s => s.student_id == a.student_id);
        return `<tr>
            <td>${a.attendance_id}</td>
            <td>${stud ? stud.first_name + ' ' + stud.last_name : '-'}</td>
            <td>${a.course_id}</td>
            <td>${a.date}</td>
            <td><strong>${a.status}</strong></td>
        </tr>`;
    }).join('');
}

function handleFormSubmit(event, type) {
    event.preventDefault();
    let payload = { type: type };

    if (type === 'student') {
        payload.first_name = document.getElementById("s-first").value;
        payload.last_name = document.getElementById("s-last").value;
        payload.date_of_birth = document.getElementById("s-dob").value;
        payload.gender = document.getElementById("s-gender").value;
        payload.email = document.getElementById("s-email").value;
        payload.phone = document.getElementById("s-phone").value;
        payload.status = document.getElementById("s-status").value;
    } else if (type === 'instructor') {
        payload.first_name = document.getElementById("i-first").value;
        payload.last_name = document.getElementById("i-last").value;
        payload.email = document.getElementById("i-email").value;
        payload.department = document.getElementById("i-dept").value;
    } else if (type === 'course') {
        payload.course_id = document.getElementById("c-id").value;
        payload.course_name = document.getElementById("c-name").value;
        payload.credits = document.getElementById("c-credits").value;
        payload.instructor_id = document.getElementById("c-instructor").value;
    } else if (type === 'enrollment') {
        payload.student_id = document.getElementById("e-student").value;
        payload.course_id = document.getElementById("e-course").value;
        payload.semester = document.getElementById("e-semester").value;
    } else if (type === 'grade') {
        payload.enrollment_id = document.getElementById("g-enrollment").value;
        payload.gpa_marks = document.getElementById("g-marks").value;
        payload.letter_grade = document.getElementById("g-letter").value;
    } else if (type === 'attendance') {
        payload.student_id = document.getElementById("a-student").value;
        payload.course_id = document.getElementById("a-course").value;
        payload.date = document.getElementById("a-date").value;
        payload.status = document.getElementById("a-status").value;
    }

    fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal(`${type}-modal`);
            document.getElementById(`${type}-form`).reset();
            fetchData();
        } else {
            alert("Execution error: " + data.error);
        }
    });
}

function populateInstructorDropdown() {
    const select = document.getElementById("c-instructor");
    select.innerHTML = '<option value="">-- Choose Instructor --</option>' + 
        appData.instructors.map(i => `<option value="${i.instructor_id}">${i.first_name} ${i.last_name}</option>`).join('');
}

function populateEnrollmentDropdowns() {
    document.getElementById("e-student").innerHTML = '<option value="">-- Select Student --</option>' + 
        appData.students.map(s => `<option value="${s.student_id}">${s.first_name} ${s.last_name}</option>`).join('');
    document.getElementById("e-course").innerHTML = '<option value="">-- Select Course --</option>' + 
        appData.courses.map(c => `<option value="${c.course_id}">${c.course_id} - ${c.course_name}</option>`).join('');
}

function populateGradeDropdown() {
    document.getElementById("g-enrollment").innerHTML = '<option value="">-- Select Enrollment Record --</option>' + 
        appData.enrollments.filter(e => !appData.grades.some(g => g.enrollment_id == e.enrollment_id)).map(e => {
            const s = appData.students.find(stud => stud.student_id == e.student_id);
            return `<option value="${e.enrollment_id}">${s ? s.first_name + ' ' + s.last_name : e.student_id} (${e.course_id})</option>`;
        }).join('');
}

function populateAttendanceDropdown() {
    document.getElementById("a-enrollment").innerHTML = '<option value="">-- Select Student Course Group --</option>' + 
        appData.enrollments.map(e => {
            const s = appData.students.find(stud => stud.student_id == e.student_id);
            return `<option value="${e.enrollment_id}" data-student="${e.student_id}" data-course="${e.course_id}">${s ? s.first_name + ' ' + s.last_name : e.student_id} - ${e.course_id}</option>`;
        }).join('');
}

function syncAttendanceFields() {
    const select = document.getElementById("a-enrollment");
    const option = select.options[select.selectedIndex];
    document.getElementById("a-student").value = option.getAttribute("data-student") || "";
    document.getElementById("a-course").value = option.getAttribute("data-course") || "";
}
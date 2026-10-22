<?php
$page_title = "Admin Dashboard";
$allowed_roles = ['admin'];

include '../includes/header.php';

// Fetch quick stats for the dashboard
try {
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $active_students = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Active'")->fetchColumn();
    $total_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $total_modules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
    $total_lecturers = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Get recent 5 students
    $stmt_recent = $pdo->query("
        SELECT s.*, c.course_name, c.duration 
        FROM students s 
        LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'Active'
        LEFT JOIN courses c ON c.course_id = e.course_id
        ORDER BY s.id DESC LIMIT 5
    ");
    $recent_students = $stmt_recent->fetchAll();
} catch (PDOException $e) {
    $error = "Could not load stats: " . $e->getMessage();
}
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
            <div>
                <h4 style="margin-bottom: 2px;">Admin Dashboard</h4>
                <p class="grey-text" style="margin: 0;">Department of Forestry, Fisheries & the Environment &bull; Information Technology Business Solutions</p>
            </div>
            <div>
                <a href="register_student.php" class="btn blue darken-2 waves-effect waves-light">
                    <i class="material-icons left">person_add</i>Register Student
                </a>
            </div>
        </div>
        <div class="divider" style="margin: 15px 0;"></div>
        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<!-- Stats Cards Row 1 -->
<div class="row">
    <div class="col s12 m4 l2">
        <div class="card blue darken-1 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">people</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Total Students</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $total_students; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <a href="student_list.php" class="white-text" style="font-size: 0.8rem;">View Directory</a>
            </div>
        </div>
    </div>
    <div class="col s12 m4 l2">
        <div class="card green darken-1 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">how_to_reg</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Active Enrollments</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $active_students; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <span class="white-text" style="font-size: 0.8rem;">1-Course Enforced</span>
            </div>
        </div>
    </div>
    <div class="col s12 m4 l2">
        <div class="card purple darken-1 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">school</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Courses Offered</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $total_courses; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <a href="courses.php" class="white-text" style="font-size: 0.8rem;">Manage Courses</a>
            </div>
        </div>
    </div>
    <div class="col s12 m4 l2">
        <div class="card teal darken-1 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">book</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Modules Offered</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $total_modules; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <a href="modules.php" class="white-text" style="font-size: 0.8rem;">Shared Modules</a>
            </div>
        </div>
    </div>
    <div class="col s12 m4 l2">
        <div class="card indigo darken-1 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">supervisor_account</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Academic Staff</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $total_lecturers; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <a href="lecturers.php" class="white-text" style="font-size: 0.8rem;">Faculty Directory</a>
            </div>
        </div>
    </div>
    <div class="col s12 m4 l2">
        <div class="card blue-grey darken-2 white-text center-align hoverable">
            <div class="card-content" style="padding: 15px 10px;">
                <i class="material-icons medium">manage_accounts</i>
                <h6 style="margin: 5px 0; font-size: 0.9rem;">Portal Accounts</h6>
                <h4 style="margin: 5px 0; font-weight: 700;"><?php echo $total_users; ?></h4>
            </div>
            <div class="card-action" style="padding: 8px;">
                <span class="white-text" style="font-size: 0.8rem;">Security & Logins</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Registrations Table -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title bold" style="font-weight: 600;">
                    <i class="material-icons left blue-text">history</i>Recently Registered Students
                </span>
                <table class="highlight responsive-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Student Number</th>
                            <th>National ID</th>
                            <th>Active Course</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_students)): ?>
                            <?php foreach ($recent_students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($student['student_number'] ?: $student['student_id_number']); ?></code></td>
                                    <td><?php echo htmlspecialchars($student['id_number'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($student['course_name'] ?: $student['course_of_study']); ?></td>
                                    <td>
                                        <span class="chip <?php echo ($student['academic_status'] == STATUS_ACTIVE) ? 'green lighten-4' : 'orange lighten-4'; ?> tiny">
                                            <?php echo htmlspecialchars($student['academic_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="btn-small waves-effect waves-light blue" title="View Profile">
                                            <i class="material-icons">visibility</i>
                                        </a>
                                        <a href="update_student.php?id=<?php echo $student['id']; ?>" class="btn-small waves-effect waves-light orange darken-2" title="Edit Record">
                                            <i class="material-icons">edit</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
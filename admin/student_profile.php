<?php
require_once '../config.php';
require_once '../functions.php';
require_login(['admin']);

// Check if an ID is provided via GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('student_list.php');
}

$student_id = intval($_GET['id']);
$student = Student::findById($pdo, $student_id);

if (!$student) {
    $_SESSION['error_message'] = "No student found with that ID.";
    redirect('student_list.php');
}

// Fetch Active Enrollment, History, and Results
$activeEnrollment = $student->getActiveEnrollment($pdo);
$enrollmentHistory = $student->getEnrollmentHistory($pdo);
$moduleResults = $student->getModuleResults($pdo);

// Handle Admin Reset Student Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_student_password') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password)) {
        $_SESSION['error_message'] = "Please enter a new password.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error_message'] = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
    } else {
        $studentNumber = $student->getStudentNumber();
        $res = User::adminResetPassword($pdo, $studentNumber, $new_password);
        if ($res['success']) {
            $_SESSION['success_message'] = "Password for student " . htmlspecialchars($studentNumber) . " updated successfully!";
            log_action("Admin '" . ($_SESSION['username'] ?? 'admin') . "' reset portal password for student {$studentNumber}.", __DIR__ . '/deleted_logs.txt');
        } else {
            $_SESSION['error_message'] = $res['message'];
        }
    }
    redirect('admin/student_profile.php?id=' . $student_id);
}

$status_class = 'grey';
if ($student->getAcademicStatus() === STATUS_ACTIVE) {
    $status_class = 'green';
} elseif ($student->getAcademicStatus() === STATUS_PENDING) {
    $status_class = 'orange';
} elseif ($student->getAcademicStatus() === STATUS_GRADUATED) {
    $status_class = 'blue';
}

$page_title = "Student Profile";
$allowed_roles = ['admin'];
include '../includes/header.php';
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l10 offset-l1">
        <a href="student_list.php" class="btn-flat waves-effect"><i class="material-icons left">arrow_back</i>Back to Student List</a>
        
        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>

        <!-- Profile Overview Card -->
        <div class="card" style="margin-top: 15px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
                    <div>
                        <span class="card-title bold" style="font-size: 1.6rem; font-weight: 600; margin-bottom: 2px;">
                            <?php echo htmlspecialchars($student->getFullName()); ?>
                        </span>
                        <span class="chip blue darken-2 white-text">Student No: <?php echo htmlspecialchars($student->getStudentNumber()); ?></span>
                        <?php if ($student->getIdNumber()): ?>
                            <span class="chip grey lighten-3">ID No: <?php echo htmlspecialchars($student->getIdNumber()); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="chip <?php echo $status_class; ?> white-text" style="font-size: 0.95rem; padding: 4px 12px;">
                            <?php echo htmlspecialchars($student->getAcademicStatus()); ?>
                        </span>
                    </div>
                </div>

                <div class="divider" style="margin: 20px 0;"></div>

                <div class="row">
                    <div class="col s12 m6">
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">email</i><strong>Email:</strong> <?php echo htmlspecialchars($student->getEmail()); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">cake</i><strong>Date of Birth:</strong> <?php echo date("F j, Y", strtotime($student->getDateOfBirth())); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">school</i><strong>Active Course:</strong> <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?></p>
                    </div>
                    <div class="col s12 m6">
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">event</i><strong>Registration Date:</strong> <?php echo date("F j, Y", strtotime($student->getEnrollmentDate())); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">schedule</i><strong>Duration:</strong> <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left blue-text">history</i><strong>System Registered:</strong> <?php echo date("g:i A, F j, Y", strtotime($student->getRegistrationTimestamp())); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-action">
                <a href="update_student.php?id=<?php echo $student->getId(); ?>" class="btn waves-effect waves-light orange darken-2">
                    <i class="material-icons left">edit</i>Edit Details
                </a>
                <a href="#modal-reset-password" class="btn waves-effect waves-light indigo darken-1 modal-trigger">
                    <i class="material-icons left">vpn_key</i>Reset Password
                </a>
                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student->getId(); ?>);" class="btn waves-effect waves-light red darken-2 right">
                    <i class="material-icons left">delete</i>Delete Record
                </a>
            </div>
        </div>

        <!-- Academic Results / Quarterly Marks Section -->
        <div class="card">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span class="card-title bold" style="font-weight: 600; margin: 0;">
                        <i class="material-icons left orange-text text-darken-3">assessment</i>
                        Academic Results & Quarterly Marks
                    </span>
                    <span class="chip tiny amber lighten-4 amber-text text-darken-4" style="font-weight: 600;">
                        <i class="material-icons left tiny">verified_user</i>Managed Exclusively by Module Lecturers
                    </span>
                </div>
                <p class="grey-text" style="margin-bottom: 15px; margin-top: 5px;">
                    Institutional view: Continuous assessment marks (Q1 - Q4) are submitted and managed by designated faculty lecturers.
                </p>

                <?php if (!empty($moduleResults)): ?>
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>Module Code</th>
                                <th>Module Name</th>
                                <th>Credits</th>
                                <th>Lecturers</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Calculated Average</th>
                                <th>Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moduleResults as $res): ?>
                                <?php 
                                    $modRes = new ModuleResult($res);
                                    $grade = $modRes->getGradeStatus();
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['module_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($res['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($res['credits']); ?></td>
                                    <td><small><?php echo htmlspecialchars($res['lecturers'] ?: 'Unassigned'); ?></small></td>
                                    <td><?php echo $res['quarter1'] !== null ? number_format($res['quarter1'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter2'] !== null ? number_format($res['quarter2'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter3'] !== null ? number_format($res['quarter3'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter4'] !== null ? number_format($res['quarter4'], 1) . '%' : '-'; ?></td>
                                    <td>
                                        <strong style="color: #1565c0; font-size: 1.05rem;">
                                            <?php echo $res['average'] !== null ? number_format($res['average'], 2) . '%' : 'Pending'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="chip tiny <?php echo $grade['color']; ?> white-text">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="card-panel grey lighten-4 center-align" style="padding: 25px;">
                        <i class="material-icons medium grey-text text-lighten-1">assignment_late</i>
                        <p class="grey-text" style="margin: 5px 0 0 0;">No quarterly marks recorded for this student yet. Marks are recorded exclusively by assigned module lecturers.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enrollment History Section -->
        <div class="card">
            <div class="card-content">
                <span class="card-title bold" style="font-weight: 600;">
                    <i class="material-icons left teal-text">history</i>
                    Registration & Course Enrollment History
                </span>
                <p class="grey-text" style="margin-bottom: 15px;">
                    Business Rule: A student may have only one active course enrollment at a time. The Enrollment class maintains registration history.
                </p>

                <?php if (!empty($enrollmentHistory)): ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Enrollment ID</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Duration</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollmentHistory as $enh): ?>
                                <tr>
                                    <td>ENR-<?php echo str_pad($enh['enrollment_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><strong><?php echo htmlspecialchars($enh['course_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($enh['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enh['duration']); ?></td>
                                    <td><?php echo date("F j, Y", strtotime($enh['registration_date'])); ?></td>
                                    <td>
                                        <span class="chip <?php echo ($enh['status'] === 'Active') ? 'green' : 'grey'; ?> white-text">
                                            <?php echo htmlspecialchars($enh['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="grey-text">No enrollment history records found.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Reset Password Modal -->
<div id="modal-reset-password" class="modal">
    <form method="POST" action="student_profile.php?id=<?php echo $student_id; ?>">
        <input type="hidden" name="action" value="reset_student_password">
        <div class="modal-content">
            <h5 class="blue-text text-darken-3" style="display: flex; align-items: center;">
                <i class="material-icons left">vpn_key</i>Reset Student Portal Password
            </h5>
            <p>
                Updating credentials for: <strong><?php echo htmlspecialchars($student->getFullName()); ?></strong> 
                (Student No: <code><?php echo htmlspecialchars($student->getStudentNumber()); ?></code>)
            </p>
            <div class="divider" style="margin: 15px 0;"></div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">lock</i>
                    <input type="password" id="modal_new_password" name="new_password" required minlength="6">
                    <label for="modal_new_password">New Password</label>
                    <span class="helper-text">Minimum 6 characters</span>
                </div>
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">check_circle_outline</i>
                    <input type="password" id="modal_confirm_password" name="confirm_password" required minlength="6">
                    <label for="modal_confirm_password">Confirm New Password</label>
                </div>
            </div>
            <div class="card-panel grey lighten-4" style="padding: 10px 15px; font-size: 0.85rem; margin-top: 0;">
                <i class="material-icons tiny left blue-text">info</i>
                This will immediately update the student's password in the portal authentication database.
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat waves-effect">Cancel</a>
            <button type="submit" class="btn waves-effect waves-light blue darken-3">
                <i class="material-icons left">save</i>Save New Password
            </button>
        </div>
    </form>
</div>

<script src="../js/confirmation.js"></script>
<?php include '../includes/footer.php'; ?>
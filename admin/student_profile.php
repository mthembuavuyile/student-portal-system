<?php
$page_title = "Student Profile";
$allowed_roles = ['admin'];
include '../includes/header.php';

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

$status_class = 'grey';
if ($student->getAcademicStatus() === STATUS_ACTIVE) {
    $status_class = 'green';
} elseif ($student->getAcademicStatus() === STATUS_PENDING) {
    $status_class = 'orange';
} elseif ($student->getAcademicStatus() === STATUS_GRADUATED) {
    $status_class = 'blue';
}
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l10 offset-l1">
        <a href="student_list.php" class="btn-flat waves-effect"><i class="material-icons left">arrow_back</i>Back to Student List</a>
        
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
                <a href="results.php" class="btn waves-effect waves-light blue darken-2">
                    <i class="material-icons left">assessment</i>Manage Marks
                </a>
                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $student->getId(); ?>);" class="btn waves-effect waves-light red darken-2 right">
                    <i class="material-icons left">delete</i>Delete Record
                </a>
            </div>
        </div>

        <!-- Academic Results / Quarterly Marks Section -->
        <div class="card">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="card-title bold" style="font-weight: 600;">
                        <i class="material-icons left orange-text text-darken-3">assessment</i>
                        Academic Results & Quarterly Marks
                    </span>
                    <a href="results.php" class="btn-small orange darken-3 waves-effect waves-light">
                        <i class="material-icons left">add</i>Record Marks
                    </a>
                </div>
                <p class="grey-text" style="margin-bottom: 15px;">
                    Quarterly marks (Q1 - Q4) stored for each module and academic year. Average automatically calculated via calculateAverage().
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
                    <div class="card-panel grey lighten-4 center-align">
                        <p class="grey-text">No academic results recorded for this student yet.</p>
                        <a href="results.php" class="btn blue darken-2 waves-effect waves-light">Record Quarterly Marks</a>
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

<script src="../js/confirmation.js"></script>
<?php include '../includes/footer.php'; ?>
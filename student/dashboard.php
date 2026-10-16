<?php
$page_title = "Student Dashboard";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch the logged-in student's details
$student = null;
$student_data = null;
if (isset($_SESSION['student_id_number'])) {
    $student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
    if ($student) {
        $student_data = $student->toArray();
    }
} else {
    $_SESSION['error_message'] = "Could not identify your student profile.";
    redirect('logout.php');
}

$activeEnrollment = $student ? $student->getActiveEnrollment($pdo) : null;
$moduleResults = $student ? $student->getModuleResults($pdo) : [];

// Calculate cumulative average
$totalAvg = 0;
$avgCount = 0;
foreach ($moduleResults as $res) {
    if ($res['average'] !== null) {
        $totalAvg += (float)$res['average'];
        $avgCount++;
    }
}
$cumulativeAverage = $avgCount > 0 ? round($totalAvg / $avgCount, 2) : null;
?>

<div class="row">
    <div class="col s12">
        <h4>Welcome, <?php echo htmlspecialchars($student ? $student->getFirstName() : 'Student'); ?>!</h4>
        <p class="grey-text">Department of Forestry, Fisheries & the Environment &bull; Student Portal System</p>
        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<!-- Information Cards -->
<div class="row">
    <?php if ($student): ?>
    <div class="col s12 m6 l3">
        <div class="card-panel blue lighten-1 white-text center-align" style="padding: 20px 10px;">
            <i class="material-icons medium">school</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">My Course</h6>
            <p style="font-size: 0.95rem; margin: 5px 0 0 0; font-weight: 500;">
                <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?>
            </p>
            <span class="chip tiny white blue-text" style="margin-top: 5px;">
                <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?>
            </span>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card-panel <?php echo ($student->getAcademicStatus() === STATUS_ACTIVE) ? 'green' : 'orange'; ?> lighten-1 white-text center-align" style="padding: 20px 10px;">
            <i class="material-icons medium">verified_user</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Enrollment Status</h6>
            <p style="font-size: 1.1rem; margin: 5px 0 0 0; font-weight: 600;">
                <?php echo htmlspecialchars($student->getAcademicStatus()); ?>
            </p>
            <span class="chip tiny white green-text text-darken-2" style="margin-top: 5px;">1 Active Course</span>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card-panel indigo lighten-1 white-text center-align" style="padding: 20px 10px;">
            <i class="material-icons medium">assessment</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Academic Average</h6>
            <p style="font-size: 1.4rem; margin: 5px 0 0 0; font-weight: 700;">
                <?php echo $cumulativeAverage !== null ? $cumulativeAverage . '%' : 'Pending'; ?>
            </p>
            <span class="chip tiny white indigo-text" style="margin-top: 5px;">
                <?php echo count($moduleResults); ?> Enrolled Modules
            </span>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card-panel teal lighten-1 white-text center-align" style="padding: 20px 10px;">
            <i class="material-icons medium">event</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Registration Date</h6>
            <p style="font-size: 0.95rem; margin: 5px 0 0 0; font-weight: 500;">
                <?php echo date("F d, Y", strtotime($student->getEnrollmentDate())); ?>
            </p>
            <span class="chip tiny white teal-text" style="margin-top: 5px;">
                No: <?php echo htmlspecialchars($student->getStudentNumber()); ?>
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="col s12">
        <div class="card-panel red white-text">
            There was an issue loading your details. Please contact an administrator.
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Links & Actions -->
<div class="row">
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title bold" style="font-weight: 600;"><i class="material-icons left blue-text">touch_app</i>Quick Actions</span>
                <div class="collection" style="margin-top: 15px;">
                    <a href="profile.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">person</i>View My Profile & Identification
                    </a>
                    <a href="modules.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">book</i>My Enrolled Modules & Lecturers
                    </a>
                    <a href="results.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">assessment</i>View Quarterly Marks & Academic Results
                    </a>
                    <a href="registration_slip.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">receipt</i>View / Print Registration Slip
                    </a>
                    <a href="profile_report.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">description</i>Download Profile Summary Report
                    </a>
                    <a href="<?php echo BASE_URL; ?>change_password.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">vpn_key</i>Update / Change Account Password
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Snapshot -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title bold" style="font-weight: 600;">
                    <i class="material-icons left orange-text text-darken-3">stars</i>
                    Academic Progress Snapshot
                </span>
                <?php if (!empty($moduleResults)): ?>
                    <table class="dense-table striped" style="margin-top: 10px; font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Credits</th>
                                <th>Average</th>
                                <th>Status</th>
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
                                    <td><?php echo htmlspecialchars($res['credits']); ?></td>
                                    <td><strong><?php echo $res['average'] !== null ? number_format($res['average'], 2) . '%' : 'Pending'; ?></strong></td>
                                    <td><span class="chip tiny <?php echo $grade['color']; ?> white-text"><?php echo $grade['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 15px; text-align: right;">
                        <a href="results.php" class="btn-small blue darken-2 waves-effect">View Full Marks Breakdown</a>
                    </div>
                <?php else: ?>
                    <p class="grey-text" style="margin-top: 20px;">No academic results recorded yet for the current academic session.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
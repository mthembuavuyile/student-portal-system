<?php
$page_title = "My Profile";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch student data based on the session ID
if (!isset($_SESSION['student_id_number'])) {
    $_SESSION['error_message'] = "You must be logged in to view your profile.";
    redirect('../index.php');
}

$student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);

if (!$student) {
    $_SESSION['error_message'] = "Could not find your profile information.";
    redirect('student/dashboard.php');
}

$activeEnrollment = $student->getActiveEnrollment($pdo);

$status_class = 'grey';
if ($student->getAcademicStatus() === STATUS_ACTIVE) {
    $status_class = 'green';
} elseif ($student->getAcademicStatus() === STATUS_PENDING) {
    $status_class = 'orange';
}
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
        <div class="card" style="margin-top: 20px;">
            <div class="card-content">
                <span class="card-title activator grey-text text-darken-4">
                    <i class="material-icons left">account_circle</i>
                    My Profile
                </span>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student->getFullName()); ?></p>
                <p><strong>Student Number:</strong> <code><?php echo htmlspecialchars($student->getStudentNumber()); ?></code></p>
                <?php if ($student->getIdNumber()): ?>
                    <p><strong>National ID Number:</strong> <?php echo htmlspecialchars($student->getIdNumber()); ?></p>
                <?php endif; ?>
                <div class="divider" style="margin: 15px 0;"></div>

                <div class="row">
                    <div class="col s12 m6">
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">email</i><strong>Email:</strong> <?php echo htmlspecialchars($student->getEmail()); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">cake</i><strong>Date of Birth:</strong> <?php echo date("F j, Y", strtotime($student->getDateOfBirth())); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">school</i><strong>Course:</strong> <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?></p>
                    </div>
                    <div class="col s12 m6">
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">schedule</i><strong>Duration:</strong> <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">event</i><strong>Enrollment Date:</strong> <?php echo date("F j, Y", strtotime($student->getEnrollmentDate())); ?></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">info</i><strong>Status:</strong> <span class="chip <?php echo $status_class; ?> white-text"><?php echo htmlspecialchars($student->getAcademicStatus()); ?></span></p>
                    </div>
                </div>
            </div>
            <div class="card-action">
                <a href="results.php" class="btn waves-effect waves-light orange darken-3"><i class="material-icons left">assessment</i>Academic Results</a>
                <a href="profile_report.php" class="btn waves-effect waves-light blue darken-2"><i class="material-icons left">print</i>Print Summary</a>
                <a href="registration_slip.php" class="btn waves-effect waves-light teal darken-2"><i class="material-icons left">receipt</i>Registration Slip</a>
                <a href="<?php echo BASE_URL; ?>change_password.php" class="btn waves-effect waves-light purple darken-2"><i class="material-icons left">lock</i>Change Password</a>
            </div>
        </div>

        <!-- Security & Authentication Card -->
        <div class="card">
            <div class="card-content">
                <span class="card-title bold" style="font-weight: 600;">
                    <i class="material-icons left blue-text">security</i>
                    Security & Portal Credentials
                </span>
                <p class="grey-text" style="margin-bottom: 15px;">
                    Manage your Student Portal login credentials and password.
                </p>
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m6">
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">account_circle</i><strong>Portal Username:</strong> <code><?php echo htmlspecialchars($_SESSION['username']); ?></code></p>
                        <p style="margin-bottom: 8px;"><i class="material-icons tiny left">lock</i><strong>Password:</strong> &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull; (Encrypted)</p>
                    </div>
                    <div class="col s12 m6 right-align">
                        <a href="<?php echo BASE_URL; ?>change_password.php" class="btn waves-effect waves-light blue darken-3">
                            <i class="material-icons left">vpn_key</i>Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
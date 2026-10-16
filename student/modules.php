<?php
$page_title = "My Enrolled Modules";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch the logged-in student's details
if (!isset($_SESSION['student_id_number'])) {
    redirect('../index.php');
}

$student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
if (!$student) {
    die("Student profile not found.");
}

$activeEnrollment = $student->getActiveEnrollment($pdo);
$courseModules = [];
if ($activeEnrollment) {
    $courseObj = Course::findById($pdo, (int)$activeEnrollment['course_id']);
    if ($courseObj) {
        $courseModules = $courseObj->getModules($pdo);
    }
}
?>

<div class="row">
    <div class="col s12">
        <a href="dashboard.php" class="btn-flat waves-effect" style="margin-top: 15px;"><i class="material-icons left">arrow_back</i>Back to Dashboard</a>
        <h4><i class="material-icons left medium teal-text">book</i>My Enrolled Modules</h4>
        <p class="grey-text">
            Course: <strong><?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?></strong>
            &bull; Program Duration: <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?>
        </p>
    </div>
</div>

<div class="row">
    <?php if (!empty($courseModules)): ?>
        <?php foreach ($courseModules as $mData): ?>
            <?php 
                $modObj = new Module($mData);
                $lecturers = $modObj->getLecturers($pdo);
            ?>
            <div class="col s12 m6 l4">
                <div class="card hoverable">
                    <div class="card-content">
                        <span class="chip teal lighten-4 teal-text text-darken-3 bold">
                            <?php echo htmlspecialchars($modObj->getModuleCode()); ?>
                        </span>
                        <span class="chip grey lighten-3 right">
                            <?php echo htmlspecialchars($modObj->getCredits()); ?> Credits
                        </span>
                        <h5 style="margin-top: 10px; font-size: 1.15rem; font-weight: 600;">
                            <?php echo htmlspecialchars($modObj->getModuleName()); ?>
                        </h5>
                        <p class="grey-text text-darken-1" style="font-size: 0.85rem;">
                            <i class="material-icons tiny left">schedule</i><?php echo htmlspecialchars($mData['semester'] ?? 'Semester 1'); ?>
                        </p>

                        <div class="divider" style="margin: 12px 0;"></div>

                        <p style="font-weight: 500; font-size: 0.85rem; color: #555;">
                            <i class="material-icons tiny left">supervisor_account</i>Teaching Lecturers:
                        </p>
                        <?php if (!empty($lecturers)): ?>
                            <ul style="margin: 5px 0 0 0; padding-left: 20px; font-size: 0.85rem;">
                                <?php foreach ($lecturers as $lec): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($lec['first_name'] . ' ' . $lec['surname']); ?></strong>
                                        <?php if ($lec['is_primary']): ?>
                                            <span class="badge new green" data-badge-caption="Primary" style="float:none; display:inline-block; margin-left: 5px;"></span>
                                        <?php else: ?>
                                            <span class="grey-text">(Co-Lecturer)</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="grey-text" style="font-size: 0.85rem;">Faculty assignments pending.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col s12">
            <div class="card-panel grey lighten-4 center-align">
                <p class="grey-text">No curriculum modules currently mapped for your enrolled course.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

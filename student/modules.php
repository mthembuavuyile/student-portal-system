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

<?php if (!empty($courseModules)): ?>
    <div class="modules-grid-container">
        <?php foreach ($courseModules as $mData): ?>
            <?php 
                $modObj = new Module($mData);
                $lecturers = $modObj->getLecturers($pdo);
            ?>
            <div class="module-card">
                <div>
                    <!-- Code & Credits Badges -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <span style="background: #e0f2fe; color: #0284c7; font-weight: 700; font-size: 0.8rem; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.3px;">
                            <?php echo htmlspecialchars($modObj->getModuleCode()); ?>
                        </span>
                        <span style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                            <?php echo htmlspecialchars($modObj->getCredits()); ?> Credits
                        </span>
                    </div>

                    <!-- Module Title -->
                    <h5 style="margin: 14px 0 6px 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; line-height: 1.35;">
                        <?php echo htmlspecialchars($modObj->getModuleName()); ?>
                    </h5>

                    <!-- Semester -->
                    <div style="display: flex; align-items: center; gap: 5px; color: #64748b; font-size: 0.84rem; font-weight: 500;">
                        <i class="material-icons" style="font-size: 16px; color: #94a3b8;">schedule</i>
                        <?php echo htmlspecialchars($mData['semester'] ?? 'Semester 1'); ?>
                    </div>
                </div>

                <!-- Teaching Lecturers Section Pinned to Bottom -->
                <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <div style="font-size: 0.76rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <i class="material-icons" style="font-size: 16px; color: #94a3b8;">supervisor_account</i>Teaching Lecturers
                    </div>
                    <?php if (!empty($lecturers)): ?>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach ($lecturers as $lec): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                                    <span style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                        <i class="material-icons" style="font-size: 15px; color: #64748b;">person</i>
                                        <?php echo htmlspecialchars($lec['first_name'] . ' ' . $lec['surname']); ?>
                                    </span>
                                    <?php if ($lec['is_primary']): ?>
                                        <span style="font-size: 0.7rem; font-weight: 700; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 12px;">Primary</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.7rem; font-weight: 500; color: #64748b;">Co-Lecturer</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 0.82rem; color: #94a3b8; font-style: italic; text-align: center;">
                            Faculty assignments pending
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col s12">
            <div class="card-panel grey lighten-4 center-align" style="border-radius: 12px; padding: 30px;">
                <i class="material-icons large grey-text text-lighten-1">book</i>
                <h6 style="font-weight: 600; color: #475569; margin-top: 10px;">No Modules Found</h6>
                <p class="grey-text" style="margin: 5px 0 0 0;">No curriculum modules currently mapped for your enrolled course.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

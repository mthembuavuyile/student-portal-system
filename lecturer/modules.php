<?php
$page_title = "My Teaching Modules";
$allowed_roles = ['lecturer'];
include '../includes/header.php';

// Fetch lecturer details
$lecturerId = $_SESSION['lecturer_id'] ?? null;
$lecturer = null;
if ($lecturerId) {
    $lecturer = Lecturer::findById($pdo, (int)$lecturerId);
}
if (!$lecturer) {
    $user = User::findById($pdo, (int)$_SESSION['user_id']);
    if ($user && $user->getLecturerId()) {
        $lecturer = Lecturer::findById($pdo, $user->getLecturerId());
    }
}
if (!$lecturer) {
    echo '<div class="card-panel red white-text">Error: Lecturer profile not linked.</div>';
    include '../includes/footer.php';
    exit();
}

$modules = $lecturer->getModules($pdo);
?>

<div class="row" style="margin-top: 20px;">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="material-icons left medium indigo-text text-darken-3">book</i>My Teaching Modules
                </h4>
                <p class="grey-text" style="margin: 5px 0 0 0;">
                    Curriculum modules assigned to <strong><?php echo htmlspecialchars($lecturer->getFullName()); ?></strong>.
                </p>
            </div>
            <div>
                <a href="results.php" class="btn waves-effect waves-light amber darken-3">
                    <i class="material-icons left">edit_note</i>Assign Marks
                </a>
            </div>
        </div>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<div class="row">
    <?php if (!empty($modules)): ?>
        <?php foreach ($modules as $m): ?>
            <?php
                // Fetch student count and results summary for this module
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT s.id) AS student_count
                    FROM students s
                    JOIN enrollments e ON e.student_id = s.id AND e.status = 'Active'
                    JOIN course_modules cm ON cm.course_id = e.course_id
                    WHERE cm.module_id = :mid
                ");
                $stmt->execute([':mid' => $m['module_id']]);
                $stCount = $stmt->fetchColumn() ?: 0;

                // Also fetch courses offering this module
                $cStmt = $pdo->prepare("
                    SELECT c.course_code, c.course_name, cm.semester
                    FROM course_modules cm
                    JOIN courses c ON c.course_id = cm.course_id
                    WHERE cm.module_id = :mid
                ");
                $cStmt->execute([':mid' => $m['module_id']]);
                $offeringCourses = $cStmt->fetchAll();
            ?>
            <div class="col s12 m6">
                <div class="card hoverable z-depth-1" style="border-radius: 8px; border-top: 4px solid #283593;">
                    <div class="card-content">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <span class="chip indigo lighten-5 indigo-text text-darken-3 font-weight-bold">
                                    <?php echo htmlspecialchars($m['module_code']); ?>
                                </span>
                                <h5 style="margin: 8px 0; font-weight: 600;"><?php echo htmlspecialchars($m['module_name']); ?></h5>
                            </div>
                            <div>
                                <?php if ($m['is_primary']): ?>
                                    <span class="chip blue white-text tiny">Primary</span>
                                <?php else: ?>
                                    <span class="chip grey lighten-2 grey-text text-darken-3 tiny">Co-Lecturer</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="divider" style="margin: 12px 0;"></div>

                        <p class="grey-text text-darken-2" style="margin-bottom: 10px;">
                            <strong>Academic Weight:</strong> <?php echo htmlspecialchars($m['credits']); ?> Credits<br>
                            <strong>Active Enrolled Students:</strong> <?php echo $stCount; ?> students
                        </p>

                        <div style="margin-bottom: 15px;">
                            <strong class="grey-text text-darken-3" style="font-size: 0.85rem;">OFFERED IN PROGRAMMES:</strong>
                            <div style="margin-top: 6px;">
                                <?php if (!empty($offeringCourses)): ?>
                                    <?php foreach ($offeringCourses as $oc): ?>
                                        <div style="margin-bottom: 4px;">
                                            <span class="chip tiny teal lighten-5 teal-text font-weight-bold">
                                                <?php echo htmlspecialchars($oc['course_code']); ?>
                                            </span>
                                            <span style="font-size: 0.85rem;"><?php echo htmlspecialchars($oc['course_name']); ?> (<?php echo htmlspecialchars($oc['semester']); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="grey-text" style="font-size: 0.85rem;">Not currently mapped to an active qualification.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-action" style="padding: 12px 0 0 0; border-top: 1px solid #f0f0f0;">
                            <a href="results.php?module_id=<?php echo $m['module_id']; ?>" class="btn waves-effect waves-light indigo darken-3" style="width: 100%;">
                                <i class="material-icons left tiny">edit_note</i>Record Marks for <?php echo htmlspecialchars($m['module_code']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col s12">
            <div class="card-panel grey lighten-4 center-align" style="padding: 40px; border-radius: 8px;">
                <i class="material-icons large grey-text">menu_book</i>
                <h5>No Modules Assigned</h5>
                <p class="grey-text">You are not currently assigned to teach any modules. Please contact system administration to be assigned curriculum modules.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

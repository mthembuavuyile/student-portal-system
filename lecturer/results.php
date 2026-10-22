<?php
$page_title = "Assign Marks & Academic Outcomes";
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

$errors = [];

// Handle Record / Update Quarterly Marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    if (!validate_csrf_token('lecturer/results.php')) { exit(); }
    $studentId    = (int)($_POST['student_id'] ?? 0);
    $moduleId     = (int)($_POST['module_id'] ?? 0);
    $academicYear = (int)($_POST['academic_year'] ?? date('Y'));
    $q1           = ($_POST['quarter1'] !== '' && $_POST['quarter1'] !== null) ? (float)$_POST['quarter1'] : null;
    $q2           = ($_POST['quarter2'] !== '' && $_POST['quarter2'] !== null) ? (float)$_POST['quarter2'] : null;
    $q3           = ($_POST['quarter3'] !== '' && $_POST['quarter3'] !== null) ? (float)$_POST['quarter3'] : null;
    $q4           = ($_POST['quarter4'] !== '' && $_POST['quarter4'] !== null) ? (float)$_POST['quarter4'] : null;

    if ($studentId <= 0 || $moduleId <= 0) {
        $errors[] = "Please select both a student and a module.";
    } else {
        // Verify lecturer actually teaches this module or is authorized
        $assignedModules = $lecturer->getModules($pdo);
        $assignedIds = array_column($assignedModules, 'module_id');
        if (!in_array($moduleId, $assignedIds)) {
            $errors[] = "Security check: You can only assign marks for modules assigned to your teaching profile.";
        } else {
            try {
                $moduleResult = new ModuleResult([
                    'student_id'    => $studentId,
                    'module_id'     => $moduleId,
                    'academic_year' => $academicYear,
                    'quarter1'      => $q1,
                    'quarter2'      => $q2,
                    'quarter3'      => $q3,
                    'quarter4'      => $q4
                ]);

                $moduleResult->save($pdo);
                $_SESSION['success_message'] = "Academic results recorded successfully! Calculated Average: " . number_format($moduleResult->getAverage(), 2) . "%";
                redirect('lecturer/results.php' . (isset($_GET['module_id']) ? '?module_id=' . (int)$_GET['module_id'] : ''));

            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get filter
$filterModuleId = isset($_GET['module_id']) && (int)$_GET['module_id'] > 0 ? (int)$_GET['module_id'] : null;
$filterStudentId = isset($_GET['student_id']) && (int)$_GET['student_id'] > 0 ? (int)$_GET['student_id'] : null;

// Lecturer's modules
$modules = $lecturer->getModules($pdo);
$moduleResults = $lecturer->getModuleResults($pdo, $filterModuleId);

// All active students eligible for this lecturer's modules
$eligibleStudents = $lecturer->getStudents($pdo);

// If both student and module are specified, check for existing marks to pre-populate modal
$existingMarks = null;
if ($filterStudentId && $filterModuleId) {
    $existingMarks = ModuleResult::find($pdo, $filterStudentId, $filterModuleId, (int)date('Y'));
}
?>

<div class="row" style="margin-top: 20px;">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="material-icons left medium indigo-text text-darken-3">edit_note</i>Assign & Record Marks
                </h4>
                <p class="grey-text" style="margin: 5px 0 0 0;">
                    Educator Marksheet for <strong><?php echo htmlspecialchars($lecturer->getFullName()); ?></strong>. Enter quarterly scores (0 - 100).
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="students.php" class="btn-large waves-effect waves-light indigo darken-3 z-depth-2" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left">people</i>My Students
                </a>
                <a href="#captureMarksModal" class="btn-large waves-effect waves-light amber darken-3 modal-trigger z-depth-2" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left">add_circle</i>Record New Marks
                </a>
            </div>
        </div>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>

        <?php if (!empty($errors)): ?>
            <div class="card-panel red lighten-4 red-text text-darken-4" style="margin-top: 15px;">
                <ul style="margin: 0;">
                    <?php foreach ($errors as $err): ?>
                        <li><i class="material-icons tiny">error_outline</i> <?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Bar -->
<div class="row">
    <div class="col s12">
        <div class="card-panel white z-depth-1" style="padding: 15px 20px; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap;">
                <div class="col s12 m6 l4">
                    <span class="grey-text text-darken-2" style="font-weight: 500;">
                        <i class="material-icons tiny">filter_list</i> Filter By Assigned Module:
                    </span>
                </div>
                <div class="col s12 m6 l8">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="results.php" class="chip <?php echo empty($filterModuleId) ? 'indigo darken-2 white-text' : 'grey lighten-3'; ?>">
                            All Teaching Modules (<?php echo count($modules); ?>)
                        </a>
                        <?php foreach ($modules as $m): ?>
                            <a href="results.php?module_id=<?php echo $m['module_id']; ?>" 
                               class="chip <?php echo $filterModuleId == $m['module_id'] ? 'indigo darken-2 white-text' : 'grey lighten-3'; ?>">
                                <?php echo htmlspecialchars($m['module_code']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Marksheet Table -->
<div class="row">
    <div class="col s12">
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span class="card-title" style="font-weight: 600; margin: 0;">
                        Official Marksheet (<?php echo count($moduleResults); ?> Entries)
                    </span>
                    <button class="btn-flat waves-effect" onclick="window.print();">
                        <i class="material-icons left tiny">print</i>Print Marksheet
                    </button>
                </div>

                <table class="highlight responsive-table" id="educatorResultsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Module</th>
                            <th>Year</th>
                            <th>Q1 (25%)</th>
                            <th>Q2 (25%)</th>
                            <th>Q3 (25%)</th>
                            <th>Q4 (25%)</th>
                            <th>Calculated Average</th>
                            <th>Outcome</th>
                            <th class="center-align">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($moduleResults)): ?>
                            <?php foreach ($moduleResults as $res): ?>
                                <?php 
                                    $modRes = new ModuleResult($res);
                                    $grade = $modRes->getGradeStatus();
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['full_name']); ?></strong></td>
                                    <td><span class="chip tiny grey lighten-3"><?php echo htmlspecialchars($res['student_number'] ?: $res['student_id_number']); ?></span></td>
                                    <td>
                                        <span class="chip tiny teal lighten-5 teal-text font-weight-bold">
                                            <?php echo htmlspecialchars($res['module_code']); ?>
                                        </span>
                                        <small class="grey-text d-block"><?php echo htmlspecialchars($res['module_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($res['academic_year']); ?></td>
                                    <td><?php echo $res['quarter1'] !== null ? number_format($res['quarter1'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $res['quarter2'] !== null ? number_format($res['quarter2'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $res['quarter3'] !== null ? number_format($res['quarter3'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $res['quarter4'] !== null ? number_format($res['quarter4'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td>
                                        <strong style="font-size: 1.1rem; color: #1565c0;">
                                            <?php echo $res['average'] !== null ? number_format($res['average'], 2) . '%' : 'Pending'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="chip <?php echo $grade['color']; ?> white-text">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                    <td class="center-align" style="white-space: nowrap;">
                                        <button type="button" 
                                                class="btn-small waves-effect waves-light amber darken-3 edit-marks-btn"
                                                data-student="<?php echo $res['student_id']; ?>"
                                                data-module="<?php echo $res['module_id']; ?>"
                                                data-year="<?php echo $res['academic_year']; ?>"
                                                data-q1="<?php echo $res['quarter1'] ?? ''; ?>"
                                                data-q2="<?php echo $res['quarter2'] ?? ''; ?>"
                                                data-q3="<?php echo $res['quarter3'] ?? ''; ?>"
                                                data-q4="<?php echo $res['quarter4'] ?? ''; ?>"
                                                title="Edit Marks">
                                            <i class="material-icons tiny left">edit</i>Edit
                                        </button>
                                        <a href="student_profile.php?id=<?php echo $res['student_id']; ?>" 
                                           class="btn-small waves-effect waves-light indigo darken-3" 
                                           title="View Student Profile">
                                            <i class="material-icons tiny">person</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="center-align grey-text" style="padding: 30px;">
                                    <i class="material-icons large grey-text text-lighten-2">assignment_late</i><br>
                                    No marks recorded for this selection yet. Click <strong>"Record New Marks"</strong> above to assign grades.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Capture / Update Marks Modal -->
<div id="captureMarksModal" class="modal" style="max-width: 650px; border-radius: 8px;">
    <form method="POST" action="results.php" id="educatorMarksForm">
        <input type="hidden" name="action" value="save_marks">
        <?php csrf_token_field(); ?>
        <div class="modal-content" style="padding: 24px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <i class="material-icons medium indigo-text text-darken-3" style="margin-right: 12px;">fact_check</i>
                <div>
                    <h5 style="margin: 0; font-weight: 600;">Capture / Update Student Marks</h5>
                    <span class="grey-text">Educator grading panel. Permitted mark range: 0.00% - 100.00%.</span>
                </div>
            </div>

            <div class="divider" style="margin-bottom: 20px;"></div>

            <div class="row">
                <div class="input-field col s12 m6">
                    <select name="module_id" id="modal_module_id" required>
                        <option value="" disabled selected>-- Select Teaching Module --</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?php echo $m['module_id']; ?>" <?php echo ($filterModuleId == $m['module_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['module_code'] . ' - ' . $m['module_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Module *</label>
                </div>

                <div class="input-field col s12 m6">
                    <select name="student_id" id="modal_student_id" required>
                        <option value="" disabled <?php echo empty($filterStudentId) ? 'selected' : ''; ?>>-- Select Enrolled Student --</option>
                        <?php foreach ($eligibleStudents as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php echo ($filterStudentId == $st['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['full_name'] . ' (' . ($st['student_number'] ?: $st['student_id_number']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Student *</label>
                </div>
            </div>

            <div class="row">
                <div class="input-field col s12 m4">
                    <input id="modal_academic_year" type="number" name="academic_year" value="<?php echo date('Y'); ?>" min="2020" max="2035" required>
                    <label for="modal_academic_year" class="active">Academic Year *</label>
                </div>
            </div>

            <div class="card-panel grey lighten-4" style="border-radius: 6px; padding: 15px;">
                <h6 style="margin-top: 0; font-weight: 600; color: #283593;">Quarterly Assessments (0 - 100)</h6>
                <div class="row" style="margin-bottom: 0;">
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter1" type="number" step="0.01" min="0" max="100" name="quarter1" placeholder="e.g. 75">
                        <label for="modal_quarter1" class="active">Q1 (%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter2" type="number" step="0.01" min="0" max="100" name="quarter2" placeholder="e.g. 80">
                        <label for="modal_quarter2" class="active">Q2 (%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter3" type="number" step="0.01" min="0" max="100" name="quarter3" placeholder="e.g. 70">
                        <label for="modal_quarter3" class="active">Q3 (%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter4" type="number" step="0.01" min="0" max="100" name="quarter4" placeholder="e.g. 85">
                        <label for="modal_quarter4" class="active">Q4 (%)</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="padding: 10px 24px;">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Cancel</a>
            <button type="submit" class="btn waves-effect waves-light amber darken-3 font-weight-bold">
                <i class="material-icons left">save</i>Save Marks
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Edit Marks Click Handler
    document.querySelectorAll('.edit-marks-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var studentId = this.getAttribute('data-student');
            var moduleId  = this.getAttribute('data-module');
            var year      = this.getAttribute('data-year');
            var q1        = this.getAttribute('data-q1');
            var q2        = this.getAttribute('data-q2');
            var q3        = this.getAttribute('data-q3');
            var q4        = this.getAttribute('data-q4');

            document.getElementById('modal_student_id').value = studentId;
            document.getElementById('modal_module_id').value  = moduleId;
            document.getElementById('modal_academic_year').value = year;
            document.getElementById('modal_quarter1').value = q1;
            document.getElementById('modal_quarter2').value = q2;
            document.getElementById('modal_quarter3').value = q3;
            document.getElementById('modal_quarter4').value = q4;

            M.FormSelect.init(document.querySelectorAll('select'));
            M.updateTextFields();

            var instance = M.Modal.getInstance(document.getElementById('captureMarksModal'));
            instance.open();
        });
    });

    <?php if ($filterStudentId || isset($_GET['open_modal'])): ?>
    var autoModal = M.Modal.getInstance(document.getElementById('captureMarksModal'));
    if (autoModal) {
        <?php if ($existingMarks): ?>
        document.getElementById('modal_quarter1').value = '<?php echo $existingMarks->getQuarter1() ?? ''; ?>';
        document.getElementById('modal_quarter2').value = '<?php echo $existingMarks->getQuarter2() ?? ''; ?>';
        document.getElementById('modal_quarter3').value = '<?php echo $existingMarks->getQuarter3() ?? ''; ?>';
        document.getElementById('modal_quarter4').value = '<?php echo $existingMarks->getQuarter4() ?? ''; ?>';
        M.updateTextFields();
        <?php endif; ?>
        autoModal.open();
    }
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>

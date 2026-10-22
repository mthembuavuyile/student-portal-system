<?php
/**
 * Lecturer Portal - My Students Management
 * 
 * Department of Forestry, Fisheries and the Environment (DFFE)
 * 
 * Business Rule:
 * - Lecturers can view, manage, and audit all students enrolled in modules
 *   assigned to their teaching profile.
 * - Marks management is exclusive to educators.
 */
$page_title = "My Students - Lecturer Portal";
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
    echo '<div class="card-panel red white-text">Error: Lecturer profile not linked to this user account.</div>';
    include '../includes/footer.php';
    exit();
}

$errors = [];

// Handle Record / Update Quarterly Marks directly from students page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    if (!validate_csrf_token('lecturer/students.php')) { exit(); }
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
        $assignedModules = $lecturer->getModules($pdo);
        $assignedIds = array_column($assignedModules, 'module_id');
        if (!in_array($moduleId, $assignedIds)) {
            $errors[] = "Security check: You can only record marks for modules assigned to your teaching profile.";
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
                $_SESSION['success_message'] = "Academic marks updated successfully! Calculated Average: " . number_format($moduleResult->getAverage(), 2) . "%";
                redirect('lecturer/students.php' . (isset($_GET['module_id']) ? '?module_id=' . (int)$_GET['module_id'] : ''));

            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Module filter
$filterModuleId = isset($_GET['module_id']) && (int)$_GET['module_id'] > 0 ? (int)$_GET['module_id'] : null;

// Modules taught by this lecturer
$modules = $lecturer->getModules($pdo);

// Detailed student list
$studentRows = $lecturer->getStudentsDetailed($pdo, $filterModuleId);

// Calculate metrics
$uniqueStudentIds = array_unique(array_column($studentRows, 'student_id'));
$totalUniqueStudents = count($uniqueStudentIds);
$totalEnrollmentEntries = count($studentRows);
$marksRecordedCount = 0;
$totalAverageSum = 0;

foreach ($studentRows as $row) {
    if ($row['average'] !== null) {
        $marksRecordedCount++;
        $totalAverageSum += (float)$row['average'];
    }
}
$classAverage = $marksRecordedCount > 0 ? round($totalAverageSum / $marksRecordedCount, 1) : 0;
?>

<style>
.students-table-wrapper {
    overflow-x: auto;
    width: 100%;
    margin-top: 10px;
    -webkit-overflow-scrolling: touch;
}

.modern-students-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-students-table th {
    padding: 12px 10px;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #475569;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.modern-students-table td {
    padding: 12px 10px;
    font-size: 0.9rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.student-info-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 170px;
    max-width: 210px;
}

.student-email-link {
    display: block;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.78rem;
}

.mark-col {
    width: 52px;
    text-align: center;
    padding: 8px 4px !important;
    font-size: 0.86rem;
}

.actions-cell-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    white-space: nowrap;
}

.btn-action-marks {
    height: 32px !important;
    line-height: 32px !important;
    padding: 0 10px !important;
    font-weight: 600 !important;
    font-size: 0.8rem !important;
    border-radius: 6px !important;
    text-transform: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
}

.btn-action-profile {
    height: 32px !important;
    line-height: 32px !important;
    width: 34px !important;
    padding: 0 !important;
    border-radius: 6px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
</style>

<div class="row" style="margin-top: 20px;">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="material-icons left medium indigo-text text-darken-3">people</i>My Students
                </h4>
                <p class="grey-text" style="margin: 5px 0 0 0;">
                    Student roster instructed by <strong><?php echo htmlspecialchars($lecturer->getFullName()); ?></strong>. Manage students and capture continuous assessment marks.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="results.php" class="btn waves-effect waves-light amber darken-3 z-depth-1" style="font-weight: 600;">
                    <i class="material-icons left">edit_note</i>Assign Marks
                </a>
                <a href="modules.php" class="btn waves-effect waves-light indigo darken-3 z-depth-1" style="font-weight: 600;">
                    <i class="material-icons left">book</i>My Modules
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

<!-- Quick Stat Cards -->
<div class="row">
    <div class="col s12 m6 l3">
        <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #283593; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium indigo-text text-darken-3">groups</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #283593;"><?php echo $totalUniqueStudents; ?></h4>
                    <span class="grey-text text-darken-1">Enrolled Students</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #00897b; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium teal-text text-darken-1">school</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #00897b;"><?php echo count($modules); ?></h4>
                    <span class="grey-text text-darken-1">Teaching Modules</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #ef6c00; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium orange-text text-darken-3">assessment</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #ef6c00;"><?php echo $marksRecordedCount; ?> / <?php echo $totalEnrollmentEntries; ?></h4>
                    <span class="grey-text text-darken-1">Marks Recorded</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #2e7d32; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium green-text text-darken-3">trending_up</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #2e7d32;"><?php echo $classAverage; ?>%</h4>
                    <span class="grey-text text-darken-1">Class Average</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search Bar -->
<div class="row">
    <div class="col s12">
        <div class="card-panel white z-depth-1" style="padding: 16px 20px; border-radius: 8px;">
            <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap; gap: 15px;">
                <div class="col s12 m6 l7">
                    <span class="grey-text text-darken-2" style="font-weight: 600; display: block; margin-bottom: 6px;">
                        <i class="material-icons tiny left" style="margin-top: 3px;">filter_list</i> Filter By Module:
                    </span>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="students.php" class="chip <?php echo empty($filterModuleId) ? 'indigo darken-2 white-text' : 'grey lighten-3'; ?>">
                            All Teaching Modules (<?php echo $totalEnrollmentEntries; ?>)
                        </a>
                        <?php foreach ($modules as $m): ?>
                            <a href="students.php?module_id=<?php echo $m['module_id']; ?>" 
                               class="chip <?php echo $filterModuleId == $m['module_id'] ? 'indigo darken-2 white-text' : 'grey lighten-3'; ?>">
                                <?php echo htmlspecialchars($m['module_code']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col s12 m6 l5">
                    <div class="input-field" style="margin: 0;">
                        <i class="material-icons prefix">search</i>
                        <input type="text" id="studentSearchInput" onkeyup="filterStudents()" placeholder="Search student name, number, module...">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<div class="row">
    <div class="col s12">
        <div class="card z-depth-1" style="border-radius: 10px; overflow: hidden;">
            <div class="card-content" style="padding: 20px 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <span class="card-title" style="font-weight: 600; margin: 0;">
                        <i class="material-icons left indigo-text">badge</i>Enrolled Students Directory (<?php echo count($studentRows); ?> records)
                    </span>
                    <button class="btn-flat waves-effect" onclick="window.print();">
                        <i class="material-icons left tiny">print</i>Print Roster
                    </button>
                </div>

                <div class="students-table-wrapper">
                    <table class="highlight modern-students-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th style="min-width: 170px;">Student</th>
                                <th style="min-width: 105px;">Student No</th>
                                <th style="min-width: 130px;">Enrolled Course</th>
                                <th style="min-width: 100px;">Module</th>
                                <th class="mark-col">Q1</th>
                                <th class="mark-col">Q2</th>
                                <th class="mark-col">Q3</th>
                                <th class="mark-col">Q4</th>
                                <th class="center-align" style="min-width: 85px;">Calculated Avg</th>
                                <th class="center-align" style="min-width: 105px;">Status</th>
                                <th class="right-align" style="min-width: 135px; padding-right: 14px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($studentRows)): ?>
                                <?php foreach ($studentRows as $st): ?>
                                    <?php 
                                        $hasResult = ($st['mark_id'] !== null);
                                        $modRes = $hasResult ? new ModuleResult($st) : null;
                                        $grade = $modRes ? $modRes->getGradeStatus() : ['grade' => 'Pending', 'status' => 'Pending', 'color' => 'grey'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="student-info-cell">
                                                <div class="circle indigo darken-3 white-text center-align" style="width: 34px; height: 34px; line-height: 34px; font-weight: 600; font-size: 0.82rem; flex-shrink: 0;">
                                                    <?php 
                                                        $parts = explode(' ', trim($st['full_name']));
                                                        $initials = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                                                        echo htmlspecialchars($initials ?: 'ST');
                                                    ?>
                                                </div>
                                                <div style="overflow: hidden;">
                                                    <strong style="color: #0f172a;"><?php echo htmlspecialchars($st['full_name']); ?></strong>
                                                    <small class="grey-text student-email-link"><a href="mailto:<?php echo htmlspecialchars($st['email']); ?>" class="grey-text text-darken-1"><?php echo htmlspecialchars($st['email']); ?></a></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="chip tiny grey lighten-3" style="margin: 0; font-family: monospace;">
                                                <?php echo htmlspecialchars($st['student_number'] ?: $st['student_id_number']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="grey-text text-darken-2 font-weight-bold"><?php echo htmlspecialchars($st['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="chip tiny teal lighten-5 teal-text text-darken-3 font-weight-bold" title="<?php echo htmlspecialchars($st['module_name']); ?>" style="margin: 0;">
                                                <?php echo htmlspecialchars($st['module_code']); ?>
                                            </span>
                                            <?php if ($st['is_primary']): ?>
                                                <span class="badge blue white-text tiny" style="float:none; padding: 2px 6px; border-radius: 4px; font-size: 0.68rem;">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="mark-col"><?php echo $st['quarter1'] !== null ? number_format($st['quarter1'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                        <td class="mark-col"><?php echo $st['quarter2'] !== null ? number_format($st['quarter2'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                        <td class="mark-col"><?php echo $st['quarter3'] !== null ? number_format($st['quarter3'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                        <td class="mark-col"><?php echo $st['quarter4'] !== null ? number_format($st['quarter4'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                        <td class="center-align">
                                            <strong style="color: #1565c0; font-size: 1rem;">
                                                <?php echo $st['average'] !== null ? number_format($st['average'], 2) . '%' : '<span class="grey-text">Pending</span>'; ?>
                                            </strong>
                                        </td>
                                        <td class="center-align">
                                            <span class="chip <?php echo $grade['color']; ?> white-text tiny" style="margin: 0;">
                                                <?php echo $grade['grade']; ?>
                                            </span>
                                        </td>
                                        <td class="right-align" style="padding-right: 14px;">
                                            <div class="actions-cell-wrapper">
                                                <!-- Quick Record Marks Button -->
                                                <button type="button" 
                                                        class="btn-small waves-effect waves-light amber darken-3 edit-marks-modal-btn tooltipped btn-action-marks"
                                                        data-position="top"
                                                        data-tooltip="<?php echo $hasResult ? 'Update Marks' : 'Record Marks'; ?>"
                                                        data-student-id="<?php echo $st['student_id']; ?>"
                                                        data-student-name="<?php echo htmlspecialchars($st['full_name']); ?>"
                                                        data-module-id="<?php echo $st['module_id']; ?>"
                                                        data-module-code="<?php echo htmlspecialchars($st['module_code']); ?>"
                                                        data-year="<?php echo $st['academic_year'] ?? date('Y'); ?>"
                                                        data-q1="<?php echo $st['quarter1'] ?? ''; ?>"
                                                        data-q2="<?php echo $st['quarter2'] ?? ''; ?>"
                                                        data-q3="<?php echo $st['quarter3'] ?? ''; ?>"
                                                        data-q4="<?php echo $st['quarter4'] ?? ''; ?>">
                                                    <i class="material-icons tiny">edit</i>Marks
                                                </button>

                                                <!-- View Student Profile -->
                                                <a href="student_profile.php?id=<?php echo $st['student_id']; ?>" 
                                                   class="btn-small waves-effect waves-light indigo darken-3 tooltipped btn-action-profile" 
                                                   data-position="top" 
                                                   data-tooltip="View Student Details">
                                                    <i class="material-icons tiny">person</i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="center-align grey-text" style="padding: 40px;">
                                        <i class="material-icons large grey-text text-lighten-2">people_outline</i><br>
                                        No students found for this selection. When students enroll in courses offering your assigned modules, they will appear here.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Capture / Update Marks Modal -->
<div id="quickMarksModal" class="modal" style="max-width: 620px; border-radius: 8px;">
    <form method="POST" action="students.php" id="quickMarksForm">
        <input type="hidden" name="action" value="save_marks">
        <input type="hidden" name="student_id" id="modal_student_id">
        <input type="hidden" name="module_id" id="modal_module_id">
        <?php csrf_token_field(); ?>

        <div class="modal-content" style="padding: 24px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <i class="material-icons medium indigo-text text-darken-3" style="margin-right: 12px;">edit_note</i>
                <div>
                    <h5 style="margin: 0; font-weight: 600;" id="modalTitle">Record Student Marks</h5>
                    <span class="grey-text" id="modalSubtitle">Module Continuous Assessment (0.00% - 100.00%)</span>
                </div>
            </div>

            <div class="divider" style="margin-bottom: 18px;"></div>

            <div class="row">
                <div class="col s12 m6">
                    <p style="margin: 0 0 5px 0;"><strong>Student:</strong> <span id="display_student_name" class="indigo-text font-weight-bold">-</span></p>
                </div>
                <div class="col s12 m6">
                    <p style="margin: 0 0 5px 0;"><strong>Module:</strong> <span id="display_module_code" class="teal-text text-darken-2 font-weight-bold">-</span></p>
                </div>
            </div>

            <div class="row" style="margin-top: 10px;">
                <div class="input-field col s12 m6">
                    <input id="modal_academic_year" type="number" name="academic_year" value="<?php echo date('Y'); ?>" min="2020" max="2035" required>
                    <label for="modal_academic_year" class="active">Academic Year *</label>
                </div>
            </div>

            <div class="card-panel grey lighten-4" style="border-radius: 6px; padding: 15px; margin-top: 0;">
                <h6 style="margin-top: 0; font-weight: 600; color: #283593;">Quarterly Marks Breakdown</h6>
                <div class="row" style="margin-bottom: 0;">
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter1" type="number" step="0.01" min="0" max="100" name="quarter1" class="mark-field" placeholder="0 - 100">
                        <label for="modal_quarter1" class="active">Q1 (25%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter2" type="number" step="0.01" min="0" max="100" name="quarter2" class="mark-field" placeholder="0 - 100">
                        <label for="modal_quarter2" class="active">Q2 (25%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter3" type="number" step="0.01" min="0" max="100" name="quarter3" class="mark-field" placeholder="0 - 100">
                        <label for="modal_quarter3" class="active">Q3 (25%)</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input id="modal_quarter4" type="number" step="0.01" min="0" max="100" name="quarter4" class="mark-field" placeholder="0 - 100">
                        <label for="modal_quarter4" class="active">Q4 (25%)</label>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 0; margin-top: 10px;">
                    <div class="col s12 center-align">
                        <span style="font-weight: 500;">Live Calculated Average: </span>
                        <span id="quickLiveAverage" class="badge grey white-text" style="float:none; font-size: 1.05rem; padding: 4px 12px; border-radius: 6px;">Pending</span>
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

    var tooltips = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(tooltips);

    // Live Average calculation
    const markInputs = document.querySelectorAll('.mark-field');
    const liveBadge = document.getElementById('quickLiveAverage');

    function calculateAverage() {
        let sum = 0;
        let count = 0;
        markInputs.forEach(inp => {
            const val = parseFloat(inp.value);
            if (!isNaN(val) && val >= 0 && val <= 100) {
                sum += val;
                count++;
            }
        });
        if (count > 0) {
            const avg = (sum / count).toFixed(2);
            liveBadge.textContent = avg + '%';
            if (avg >= 75) {
                liveBadge.className = 'badge green white-text';
            } else if (avg >= 50) {
                liveBadge.className = 'badge blue white-text';
            } else {
                liveBadge.className = 'badge red white-text';
            }
        } else {
            liveBadge.textContent = 'Pending';
            liveBadge.className = 'badge grey white-text';
        }
    }

    markInputs.forEach(inp => {
        inp.addEventListener('input', calculateAverage);
    });

    // Hook up modal trigger buttons
    document.querySelectorAll('.edit-marks-modal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            var studentId   = this.getAttribute('data-student-id');
            var studentName = this.getAttribute('data-student-name');
            var moduleId    = this.getAttribute('data-module-id');
            var moduleCode  = this.getAttribute('data-module-code');
            var year        = this.getAttribute('data-year');
            var q1          = this.getAttribute('data-q1');
            var q2          = this.getAttribute('data-q2');
            var q3          = this.getAttribute('data-q3');
            var q4          = this.getAttribute('data-q4');

            document.getElementById('modal_student_id').value = studentId;
            document.getElementById('modal_module_id').value  = moduleId;
            document.getElementById('modal_academic_year').value = year;
            document.getElementById('display_student_name').textContent = studentName;
            document.getElementById('display_module_code').textContent = moduleCode;

            document.getElementById('modal_quarter1').value = q1;
            document.getElementById('modal_quarter2').value = q2;
            document.getElementById('modal_quarter3').value = q3;
            document.getElementById('modal_quarter4').value = q4;

            M.updateTextFields();
            calculateAverage();

            var instance = M.Modal.getInstance(document.getElementById('quickMarksModal'));
            instance.open();
        });
    });
});

function filterStudents() {
    var input = document.getElementById("studentSearchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("studentsTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var tdStudent = tr[i].getElementsByTagName("td")[0];
        var tdNumber  = tr[i].getElementsByTagName("td")[1];
        var tdCourse  = tr[i].getElementsByTagName("td")[2];
        var tdModule  = tr[i].getElementsByTagName("td")[3];

        if (tdStudent || tdNumber || tdCourse || tdModule) {
            var txtStudent = tdStudent ? (tdStudent.textContent || tdStudent.innerText) : '';
            var txtNumber  = tdNumber  ? (tdNumber.textContent  || tdNumber.innerText)  : '';
            var txtCourse  = tdCourse  ? (tdCourse.textContent  || tdCourse.innerText)  : '';
            var txtModule  = tdModule  ? (tdModule.textContent  || tdModule.innerText)  : '';

            if (txtStudent.toUpperCase().indexOf(filter) > -1 || 
                txtNumber.toUpperCase().indexOf(filter) > -1 || 
                txtCourse.toUpperCase().indexOf(filter) > -1 || 
                txtModule.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>

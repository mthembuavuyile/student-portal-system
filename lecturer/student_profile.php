<?php
/**
 * Lecturer Portal - Student Academic Profile View
 * 
 * Department of Forestry, Fisheries and the Environment (DFFE)
 * 
 * Business Rule:
 * - Lecturers can view the academic profile and module performance
 *   for students enrolled in their assigned modules.
 * - Lecturers can record or update quarterly continuous assessment marks.
 */
$page_title = "Student Profile - Lecturer Portal";
$allowed_roles = ['lecturer'];
include '../includes/header.php';

// Fetch lecturer details
$lecturerId = $_SESSION['lecturer_id'] ?? null;
$lecturer = null;
try {
    if ($lecturerId) {
        $lecturer = Lecturer::findById($pdo, (int)$lecturerId);
    }
    if (!$lecturer && isset($_SESSION['user_id'])) {
        $user = User::findById($pdo, (int)$_SESSION['user_id']);
        if ($user && $user->getLecturerId()) {
            $lecturer = Lecturer::findById($pdo, $user->getLecturerId());
        }
    }
    if (!$lecturer) {
        echo '<div class="card-panel orange darken-2 white-text" style="border-radius: 8px; margin-top: 20px;">
                <h5><i class="material-icons left">account_circle</i>Lecturer Profile Unlinked</h5>
                <p>Error: Lecturer profile not linked.</p>
              </div>';
        include '../includes/footer.php';
        exit();
    }
} catch (Throwable $e) {
    echo '<div class="card-panel red white-text" style="border-radius: 8px; margin-top: 20px;">
            <h5><i class="material-icons left">error</i>Database Configuration Error</h5>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
    include '../includes/footer.php';
    exit();
}

// Validate student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid student ID.";
    redirect('lecturer/students.php');
}

$studentId = (int)$_GET['id'];
$student = Student::findById($pdo, $studentId);

if (!$student) {
    $_SESSION['error_message'] = "No student found with that ID.";
    redirect('lecturer/students.php');
}

// Security check: verify this student is enrolled in at least one module taught by this lecturer
if (!$lecturer->hasStudent($pdo, $studentId)) {
    $_SESSION['error_message'] = "Access Restricted: This student is not enrolled in any modules taught by your profile.";
    redirect('lecturer/students.php');
}

$errors = [];

// Handle Record / Update Quarterly Marks directly from profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    if (!validate_csrf_token('lecturer/student_profile.php')) { exit(); }
    $moduleId     = (int)($_POST['module_id'] ?? 0);
    $academicYear = (int)($_POST['academic_year'] ?? date('Y'));
    $q1           = ($_POST['quarter1'] !== '' && $_POST['quarter1'] !== null) ? (float)$_POST['quarter1'] : null;
    $q2           = ($_POST['quarter2'] !== '' && $_POST['quarter2'] !== null) ? (float)$_POST['quarter2'] : null;
    $q3           = ($_POST['quarter3'] !== '' && $_POST['quarter3'] !== null) ? (float)$_POST['quarter3'] : null;
    $q4           = ($_POST['quarter4'] !== '' && $_POST['quarter4'] !== null) ? (float)$_POST['quarter4'] : null;

    if ($moduleId <= 0) {
        $errors[] = "Please select a valid module.";
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
                redirect('lecturer/student_profile.php?id=' . $studentId);

            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Student details
$activeEnrollment = $student->getActiveEnrollment($pdo);
$studentModules = $lecturer->getStudentModules($pdo, $studentId);

$statusClass = 'grey';
if ($student->getAcademicStatus() === STATUS_ACTIVE) {
    $statusClass = 'green';
} elseif ($student->getAcademicStatus() === STATUS_PENDING) {
    $statusClass = 'orange';
} elseif ($student->getAcademicStatus() === STATUS_GRADUATED) {
    $statusClass = 'blue';
}
?>

<div class="row" style="margin-top: 20px;">
    <div class="col s12 m10 offset-m1 l10 offset-l1">
        <a href="students.php" class="btn-flat waves-effect" style="margin-bottom: 10px;">
            <i class="material-icons left">arrow_back</i>Back to My Students
        </a>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>

        <?php if (!empty($errors)): ?>
            <div class="card-panel red lighten-4 red-text text-darken-4">
                <ul style="margin: 0;">
                    <?php foreach ($errors as $err): ?>
                        <li><i class="material-icons tiny">error_outline</i> <?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Student Header Card -->
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div class="circle indigo darken-3 white-text center-align" style="width: 56px; height: 56px; line-height: 56px; font-size: 1.4rem; font-weight: 700; flex-shrink: 0;">
                            <?php 
                                $parts = explode(' ', trim($student->getFullName()));
                                echo htmlspecialchars(strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1)));
                            ?>
                        </div>
                        <div>
                            <span class="card-title bold" style="font-size: 1.6rem; font-weight: 700; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($student->getFullName()); ?>
                            </span>
                            <div>
                                <span class="chip indigo darken-2 white-text tiny font-weight-bold">
                                    Student No: <?php echo htmlspecialchars($student->getStudentNumber()); ?>
                                </span>
                                <?php if ($student->getIdNumber()): ?>
                                    <span class="chip grey lighten-3 tiny">
                                        National ID: <?php echo htmlspecialchars($student->getIdNumber()); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="chip <?php echo $statusClass; ?> white-text" style="font-size: 0.95rem; padding: 4px 14px;">
                            <?php echo htmlspecialchars($student->getAcademicStatus()); ?>
                        </span>
                    </div>
                </div>

                <div class="divider" style="margin: 20px 0;"></div>

                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m6">
                        <p style="margin-bottom: 10px;">
                            <i class="material-icons tiny left indigo-text">email</i>
                            <strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($student->getEmail()); ?>"><?php echo htmlspecialchars($student->getEmail()); ?></a>
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="material-icons tiny left indigo-text">school</i>
                            <strong>Active Course:</strong> <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?>
                        </p>
                    </div>
                    <div class="col s12 m6">
                        <p style="margin-bottom: 10px;">
                            <i class="material-icons tiny left indigo-text">cake</i>
                            <strong>Date of Birth:</strong> <?php echo date("F j, Y", strtotime($student->getDateOfBirth())); ?>
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="material-icons tiny left indigo-text">schedule</i>
                            <strong>Course Duration:</strong> <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Taught By This Lecturer -->
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <span class="card-title" style="font-weight: 600; margin: 0;">
                            <i class="material-icons left amber-text text-darken-3">assessment</i>Assigned Modules & Continuous Assessment
                        </span>
                        <p class="grey-text" style="margin: 4px 0 0 0;">Quarterly marks (Q1 - Q4) for modules instructed by your faculty profile.</p>
                    </div>
                </div>

                <table class="highlight responsive-table" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Module Code</th>
                            <th>Module Name</th>
                            <th>Credits</th>
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
                        <?php if (!empty($studentModules)): ?>
                            <?php foreach ($studentModules as $mod): ?>
                                <?php 
                                    $hasRes = ($mod['mark_id'] !== null);
                                    $modRes = $hasRes ? new ModuleResult($mod) : null;
                                    $grade = $modRes ? $modRes->getGradeStatus() : ['grade' => 'Pending', 'status' => 'Pending', 'color' => 'grey'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="chip tiny teal lighten-5 teal-text text-darken-3 font-weight-bold">
                                            <?php echo htmlspecialchars($mod['module_code']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mod['module_name']); ?></strong><br>
                                        <?php if ($mod['is_primary']): ?>
                                            <span class="badge blue white-text tiny" style="float:none; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">Primary Educator</span>
                                        <?php else: ?>
                                            <span class="badge grey white-text tiny" style="float:none; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">Co-Lecturer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mod['credits']); ?> Cr</td>
                                    <td><?php echo $mod['quarter1'] !== null ? number_format($mod['quarter1'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $mod['quarter2'] !== null ? number_format($mod['quarter2'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $mod['quarter3'] !== null ? number_format($mod['quarter3'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $mod['quarter4'] !== null ? number_format($mod['quarter4'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td>
                                        <strong style="color: #1565c0; font-size: 1.1rem;">
                                            <?php echo $mod['average'] !== null ? number_format($mod['average'], 2) . '%' : '<span class="grey-text">Pending</span>'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="chip <?php echo $grade['color']; ?> white-text tiny">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                    <td class="center-align">
                                        <button type="button" 
                                                class="btn-small waves-effect waves-light amber darken-3 edit-marks-btn"
                                                data-module-id="<?php echo $mod['module_id']; ?>"
                                                data-module-code="<?php echo htmlspecialchars($mod['module_code']); ?>"
                                                data-year="<?php echo $mod['academic_year'] ?? date('Y'); ?>"
                                                data-q1="<?php echo $mod['quarter1'] ?? ''; ?>"
                                                data-q2="<?php echo $mod['quarter2'] ?? ''; ?>"
                                                data-q3="<?php echo $mod['quarter3'] ?? ''; ?>"
                                                data-q4="<?php echo $mod['quarter4'] ?? ''; ?>">
                                            <i class="material-icons left tiny">edit</i><?php echo $hasRes ? 'Edit Marks' : 'Record Marks'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="center-align grey-text" style="padding: 30px;">
                                    No shared curriculum modules found between your profile and this student.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Recording/Editing Marks -->
<div id="profileMarksModal" class="modal modal-flex" style="max-width: 650px; border-radius: 12px;">
    <form method="POST" action="student_profile.php?id=<?php echo $studentId; ?>" id="profileMarksForm">
        <input type="hidden" name="action" value="save_marks">
        <input type="hidden" name="module_id" id="modal_module_id">
        <?php csrf_token_field(); ?>

        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="circle indigo lighten-5 indigo-text text-darken-3 center-align" style="width: 42px; height: 42px; line-height: 42px; flex-shrink: 0;">
                    <i class="material-icons" style="font-size: 24px; line-height: 42px;">fact_check</i>
                </div>
                <div>
                    <h5 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Capture / Update Marks</h5>
                    <span class="grey-text text-darken-1" style="font-size: 0.85rem;">Student: <strong><?php echo htmlspecialchars($student->getFullName()); ?></strong></span>
                </div>
            </div>
            <a href="#!" class="modal-close waves-effect waves-circle grey-text text-darken-2" style="padding: 4px; border-radius: 50%;">
                <i class="material-icons">close</i>
            </a>
        </div>

        <div class="modal-content">
            <div class="card-panel white z-depth-0" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin: 0 0 14px 0; background: #f8fafc !important;">
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m6">
                        <small class="grey-text text-darken-1" style="display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Module Code</small>
                        <strong id="display_module_code" class="teal-text text-darken-2" style="font-size: 1.05rem;">-</strong>
                    </div>
                    <div class="col s12 m6">
                        <small class="grey-text text-darken-1" style="display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Student ID Number</small>
                        <span class="grey-text text-darken-3 font-weight-bold" style="font-size: 0.95rem; font-family: monospace;">
                            <?php echo htmlspecialchars($student->getStudentNumber() ?: $student->getIdNumber()); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 12px;">
                <div class="input-field col s12 m6" style="margin-top: 5px;">
                    <input id="modal_academic_year" type="number" name="academic_year" value="<?php echo date('Y'); ?>" min="2020" max="2035" required>
                    <label for="modal_academic_year" class="active">Academic Year *</label>
                </div>
            </div>

            <div class="card-panel grey lighten-4" style="border-radius: 8px; padding: 14px 16px; margin: 0; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 700; color: #283593; font-size: 0.95rem;">
                        <i class="material-icons tiny left" style="margin-top: 3px;">grade</i>Quarterly Continuous Assessments
                    </span>
                    <span class="grey-text" style="font-size: 0.8rem;">Weight: 25% each</span>
                </div>
                
                <div class="row" style="margin-bottom: 0;">
                    <div class="input-field col s6 m3" style="margin: 4px 0;">
                        <input id="modal_quarter1" type="number" step="0.01" min="0" max="100" name="quarter1" class="profile-mark-field" placeholder="0 - 100">
                        <label for="modal_quarter1" class="active">Q1 (%)</label>
                    </div>
                    <div class="input-field col s6 m3" style="margin: 4px 0;">
                        <input id="modal_quarter2" type="number" step="0.01" min="0" max="100" name="quarter2" class="profile-mark-field" placeholder="0 - 100">
                        <label for="modal_quarter2" class="active">Q2 (%)</label>
                    </div>
                    <div class="input-field col s6 m3" style="margin: 4px 0;">
                        <input id="modal_quarter3" type="number" step="0.01" min="0" max="100" name="quarter3" class="profile-mark-field" placeholder="0 - 100">
                        <label for="modal_quarter3" class="active">Q3 (%)</label>
                    </div>
                    <div class="input-field col s6 m3" style="margin: 4px 0;">
                        <input id="modal_quarter4" type="number" step="0.01" min="0" max="100" name="quarter4" class="profile-mark-field" placeholder="0 - 100">
                        <label for="modal_quarter4" class="active">Q4 (%)</label>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 0; margin-top: 8px;">
                    <div class="col s12 center-align">
                        <span style="font-weight: 600; color: #475569; font-size: 0.88rem;">Live Calculated Average: </span>
                        <span id="profileLiveAverage" class="badge grey white-text" style="float:none; font-size: 1rem; padding: 4px 14px; border-radius: 6px; font-weight: 700;">Pending</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat" style="font-weight: 600; color: #64748b;">Cancel</a>
            <button type="submit" class="btn waves-effect waves-light amber darken-3 font-weight-bold" style="border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="material-icons left">save</i>Save Marks
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    const markInputs = document.querySelectorAll('.profile-mark-field');
    const liveBadge = document.getElementById('profileLiveAverage');

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

    document.querySelectorAll('.edit-marks-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            var moduleId   = this.getAttribute('data-module-id');
            var moduleCode = this.getAttribute('data-module-code');
            var year       = this.getAttribute('data-year');
            var q1         = this.getAttribute('data-q1');
            var q2         = this.getAttribute('data-q2');
            var q3         = this.getAttribute('data-q3');
            var q4         = this.getAttribute('data-q4');

            document.getElementById('modal_module_id').value = moduleId;
            document.getElementById('display_module_code').textContent = moduleCode;
            document.getElementById('modal_academic_year').value = year;

            document.getElementById('modal_quarter1').value = q1;
            document.getElementById('modal_quarter2').value = q2;
            document.getElementById('modal_quarter3').value = q3;
            document.getElementById('modal_quarter4').value = q4;

            M.updateTextFields();
            calculateAverage();

            var instance = M.Modal.getInstance(document.getElementById('profileMarksModal'));
            instance.open();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>

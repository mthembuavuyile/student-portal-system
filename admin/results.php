<?php
$page_title = "Quarterly Marks & Academic Results";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];

// Handle Record / Update Quarterly Marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    if (!validate_csrf_token('admin/results.php')) { exit(); }
    $studentId    = (int)($_POST['student_id'] ?? 0);
    $moduleId     = (int)($_POST['module_id'] ?? 0);
    $academicYear = (int)($_POST['academic_year'] ?? date('Y'));
    $q1           = $_POST['quarter1'] !== '' ? (float)$_POST['quarter1'] : null;
    $q2           = $_POST['quarter2'] !== '' ? (float)$_POST['quarter2'] : null;
    $q3           = $_POST['quarter3'] !== '' ? (float)$_POST['quarter3'] : null;
    $q4           = $_POST['quarter4'] !== '' ? (float)$_POST['quarter4'] : null;

    if ($studentId <= 0 || $moduleId <= 0) {
        $errors[] = "Please select both a student and a module.";
    } else {
        try {
            // Instantiate ModuleResult domain object (which validates marks 0-100 and runs calculateAverage)
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
            $_SESSION['success_message'] = "Academic results recorded! Calculated Average: " . number_format($moduleResult->getAverage(), 2) . "%";
            redirect('admin/results.php');

        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all students and modules for the capture modal
$allStudents = get_all_students($pdo);
$allModules  = Module::getAll($pdo);

// Query all results with student and module details
$resultsStmt = $pdo->query("
    SELECT r.*, s.full_name, s.student_number, s.student_id_number,
           m.module_code, m.module_name, m.credits,
           GROUP_CONCAT(DISTINCT CONCAT(l.first_name, ' ', l.surname) SEPARATOR ', ') AS lecturers
    FROM module_results r
    JOIN students s ON s.id = r.student_id
    JOIN modules m ON m.module_id = r.module_id
    LEFT JOIN module_lecturers ml ON ml.module_id = m.module_id
    LEFT JOIN lecturers l ON l.lecturer_id = ml.lecturer_id
    GROUP BY r.mark_id
    ORDER BY r.academic_year DESC, s.full_name ASC, m.module_code ASC
");
$results = $resultsStmt->fetchAll();
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
            <h4><i class="material-icons left medium orange-text text-darken-3">assessment</i>Academic Results & Quarterly Marks</h4>
            <a href="#captureMarksModal" class="btn waves-effect waves-light orange darken-3 modal-trigger">
                <i class="material-icons left">edit</i>Record Marks
            </a>
        </div>
        <p class="grey-text">ModuleResult stores four quarterly marks for one student, one module, and one academic year. The calculateAverage() method calculates the average of the four marks within permitted institutional range (0-100).</p>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>

        <?php if (!empty($errors)): ?>
            <div class="card-panel red lighten-4 red-text text-darken-4">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo $err; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Institutional Marksheet & Performance Overview</span>
                <table class="highlight responsive-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student Number</th>
                            <th>Module</th>
                            <th>Assigned Educator</th>
                            <th>Year</th>
                            <th>Q1 (25%)</th>
                            <th>Q2 (25%)</th>
                            <th>Q3 (25%)</th>
                            <th>Q4 (25%)</th>
                            <th>Calculated Average</th>
                            <th>Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($results)): ?>
                            <?php foreach ($results as $res): ?>
                                <?php 
                                    $modRes = new ModuleResult($res);
                                    $grade = $modRes->getGradeStatus();
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['full_name']); ?></strong></td>
                                    <td><span class="chip tiny grey lighten-3"><?php echo htmlspecialchars($res['student_number'] ?: $res['student_id_number']); ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['module_code']); ?></strong><br>
                                        <small class="grey-text"><?php echo htmlspecialchars($res['module_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($res['lecturers'])): ?>
                                            <span class="chip tiny indigo lighten-5 indigo-text font-weight-bold">
                                                <i class="material-icons left tiny">person</i><?php echo htmlspecialchars($res['lecturers']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="grey-text">Unassigned</span>
                                        <?php endif; ?>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="center-align">No academic marks recorded yet. Click "Record Marks" to capture student quarterly results.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Capture Marks Modal -->
<div id="captureMarksModal" class="modal">
    <form method="POST" action="results.php" id="marksForm">
        <input type="hidden" name="action" value="save_marks">
        <?php csrf_token_field(); ?>
        <div class="modal-content">
            <h5>Capture / Update Quarterly Marks</h5>
            <p class="grey-text">Enter marks between 0.00 and 100.00. The average is automatically calculated and stored.</p>
            
            <div class="row">
                <div class="input-field col s12 m6">
                    <select name="student_id" required>
                        <option value="" disabled selected>Select Student</option>
                        <?php foreach ($allStudents as $st): ?>
                            <option value="<?php echo $st['id']; ?>">
                                <?php echo htmlspecialchars($st['full_name'] . ' (' . ($st['student_number'] ?: $st['student_id_number']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Student</label>
                </div>
                <div class="input-field col s12 m4">
                    <select name="module_id" required>
                        <option value="" disabled selected>Select Module</option>
                        <?php foreach ($allModules as $m): ?>
                            <option value="<?php echo $m['module_id']; ?>">
                                <?php echo htmlspecialchars($m['module_code'] . ' - ' . $m['module_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Module</label>
                </div>
                <div class="input-field col s12 m2">
                    <input type="number" id="academic_year" name="academic_year" value="<?php echo date('Y'); ?>" min="2020" max="2035" required>
                    <label for="academic_year">Academic Year</label>
                </div>
            </div>

            <div class="card-panel grey lighten-4" style="padding: 15px;">
                <h6 style="margin-top: 0; font-weight: 600;">Quarterly Marks Entry (0 - 100%)</h6>
                <div class="row" style="margin-bottom: 0;">
                    <div class="input-field col s6 m3">
                        <input type="number" id="q1" name="quarter1" step="0.01" min="0" max="100" class="mark-input" placeholder="0 - 100">
                        <label for="q1" class="active">Quarter 1 Mark</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input type="number" id="q2" name="quarter2" step="0.01" min="0" max="100" class="mark-input" placeholder="0 - 100">
                        <label for="q2" class="active">Quarter 2 Mark</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input type="number" id="q3" name="quarter3" step="0.01" min="0" max="100" class="mark-input" placeholder="0 - 100">
                        <label for="q3" class="active">Quarter 3 Mark</label>
                    </div>
                    <div class="input-field col s6 m3">
                        <input type="number" id="q4" name="quarter4" step="0.01" min="0" max="100" class="mark-input" placeholder="0 - 100">
                        <label for="q4" class="active">Quarter 4 Mark</label>
                    </div>
                </div>

                <div class="row" style="margin-bottom: 0; margin-top: 10px;">
                    <div class="col s12 center-align">
                        <span style="font-size: 1rem; font-weight: 500;">Live Calculated Average: </span>
                        <span id="liveAverage" class="badge green white-text" style="float:none; font-size: 1.1rem; padding: 4px 12px; border-radius: 8px;">0.00%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Cancel</a>
            <button type="submit" class="btn orange darken-3 waves-effect waves-light">Save Results</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Live calculate average in front-end matching calculateAverage() method
    const markInputs = document.querySelectorAll('.mark-input');
    const liveAvgBadge = document.getElementById('liveAverage');

    function updateLiveAverage() {
        let sum = 0;
        let count = 0;
        markInputs.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val >= 0 && val <= 100) {
                sum += val;
                count++;
            }
        });

        if (count > 0) {
            const avg = (sum / count).toFixed(2);
            liveAvgBadge.textContent = avg + '%';
            if (avg >= 75) {
                liveAvgBadge.className = 'badge green white-text';
            } else if (avg >= 50) {
                liveAvgBadge.className = 'badge blue white-text';
            } else {
                liveAvgBadge.className = 'badge red white-text';
            }
        } else {
            liveAvgBadge.textContent = '0.00%';
            liveAvgBadge.className = 'badge grey white-text';
        }
    }

    markInputs.forEach(input => {
        input.addEventListener('input', updateLiveAverage);
    });
});
</script>

<?php include '../includes/footer.php'; ?>

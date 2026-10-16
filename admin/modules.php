<?php
$page_title = "Module Management";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];

// Handle Add Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_module') {
    $moduleName = sanitize_input($_POST['module_name'] ?? '');
    $moduleCode = strtoupper(sanitize_input($_POST['module_code'] ?? ''));
    $credits    = (int)($_POST['credits'] ?? 15);

    if (empty($moduleName) || empty($moduleCode)) {
        $errors[] = "Module name and code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name, credits) VALUES (:code, :name, :credits)");
            $stmt->execute([':code' => $moduleCode, ':name' => $moduleName, ':credits' => $credits]);
            $_SESSION['success_message'] = "Module '{$moduleName}' successfully created!";
            redirect('admin/modules.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Assign Lecturer to Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_lecturer') {
    $moduleId   = (int)($_POST['module_id'] ?? 0);
    $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
    $isPrimary  = isset($_POST['is_primary']) ? 1 : 0;

    if ($moduleId > 0 && $lecturerId > 0) {
        try {
            $modObj = Module::findById($pdo, $moduleId);
            if ($modObj) {
                $modObj->assignLecturer($pdo, $lecturerId, $isPrimary);
                $_SESSION['success_message'] = "Lecturer successfully assigned to module!";
                redirect('admin/modules.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Error assigning lecturer: " . $e->getMessage();
        }
    } else {
        $errors[] = "Please select both a module and a lecturer.";
    }
}

$modules = Module::getAll($pdo);
$allLecturers = Lecturer::getAll($pdo);
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
            <h4><i class="material-icons left medium teal-text">book</i>Module Management</h4>
            <a href="#addModuleModal" class="btn waves-effect waves-light teal darken-2 modal-trigger">
                <i class="material-icons left">add</i>New Module
            </a>
        </div>
        <p class="grey-text">Modules may be shared across courses and taught by multiple lecturers, supporting many-to-many relationships.</p>

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
    <?php foreach ($modules as $mData): ?>
        <?php 
            $moduleObj = new Module($mData);
            $lecturers = $moduleObj->getLecturers($pdo);
            $courses = $moduleObj->getCourses($pdo);
        ?>
        <div class="col s12 m6 l4">
            <div class="card hoverable">
                <div class="card-content">
                    <span class="chip teal lighten-4 teal-text text-darken-3 bold">
                        <?php echo htmlspecialchars($moduleObj->getModuleCode()); ?>
                    </span>
                    <span class="chip grey lighten-3 right">
                        <?php echo htmlspecialchars($moduleObj->getCredits()); ?> Credits
                    </span>
                    <h5 style="margin-top: 10px; font-size: 1.15rem; font-weight: 600;">
                        <?php echo htmlspecialchars($moduleObj->getModuleName()); ?>
                    </h5>

                    <div class="divider" style="margin: 12px 0;"></div>

                    <!-- Lecturers teaching this module (Many-to-many) -->
                    <p style="font-weight: 500; font-size: 0.85rem; color: #555;">
                        <i class="material-icons tiny left">supervisor_account</i>Assigned Lecturers:
                    </p>
                    <?php if (!empty($lecturers)): ?>
                        <ul style="margin: 5px 0 10px 0; padding-left: 20px; font-size: 0.85rem;">
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
                        <p class="grey-text" style="font-size: 0.85rem; margin-bottom: 10px;">No lecturers assigned.</p>
                    <?php endif; ?>

                    <!-- Courses offering this module (Many-to-many) -->
                    <p style="font-weight: 500; font-size: 0.85rem; color: #555;">
                        <i class="material-icons tiny left">school</i>Offered In Courses:
                    </p>
                    <?php if (!empty($courses)): ?>
                        <div style="margin-top: 5px;">
                            <?php foreach ($courses as $c): ?>
                                <span class="chip tiny blue lighten-5 blue-text" style="margin: 2px;">
                                    <?php echo htmlspecialchars($c['course_code']); ?>: <?php echo htmlspecialchars($c['semester'] ?? ''); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="grey-text" style="font-size: 0.85rem;">Not assigned to any course.</p>
                    <?php endif; ?>
                </div>

                <div class="card-action">
                    <a href="#assignLecModal<?php echo $moduleObj->getModuleId(); ?>" class="teal-text text-darken-2 modal-trigger">
                        <i class="material-icons tiny left">person_add</i>Assign Lecturer
                    </a>
                </div>
            </div>
        </div>

        <!-- Assign Lecturer Modal for this Module -->
        <div id="assignLecModal<?php echo $moduleObj->getModuleId(); ?>" class="modal">
            <form method="POST" action="modules.php">
                <input type="hidden" name="action" value="assign_lecturer">
                <input type="hidden" name="module_id" value="<?php echo $moduleObj->getModuleId(); ?>">
                <div class="modal-content">
                    <h5>Assign Lecturer to <?php echo htmlspecialchars($moduleObj->getModuleCode() . ' - ' . $moduleObj->getModuleName()); ?></h5>
                    <p class="grey-text">Modules may be taught by multiple lecturers supporting co-teaching and subject matter specialists.</p>
                    
                    <div class="row">
                        <div class="input-field col s12">
                            <select name="lecturer_id" required>
                                <option value="" disabled selected>Select Lecturer</option>
                                <?php foreach ($allLecturers as $l): ?>
                                    <option value="<?php echo $l['lecturer_id']; ?>">
                                        <?php echo htmlspecialchars($l['first_name'] . ' ' . $l['surname'] . ' (' . $l['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Lecturer</label>
                        </div>
                        <div class="col s12">
                            <p>
                                <label>
                                    <input type="checkbox" name="is_primary" value="1" checked />
                                    <span>Primary Instructor / Module Coordinator</span>
                                </label>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close btn-flat">Cancel</a>
                    <button type="submit" class="btn teal darken-2 waves-effect waves-light">Assign Lecturer</button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Module Modal -->
<div id="addModuleModal" class="modal">
    <form method="POST" action="modules.php">
        <input type="hidden" name="action" value="add_module">
        <div class="modal-content">
            <h5>Create New Module</h5>
            <div class="row">
                <div class="input-field col s12 m8">
                    <input type="text" id="module_name" name="module_name" required>
                    <label for="module_name">Module Name (e.g. Cloud Computing & Virtualization)</label>
                </div>
                <div class="input-field col s12 m4">
                    <input type="text" id="module_code" name="module_code" required>
                    <label for="module_code">Module Code (e.g. CSC301)</label>
                </div>
                <div class="input-field col s12">
                    <input type="number" id="credits" name="credits" value="15" min="1" max="120" required>
                    <label for="credits">Credits (e.g. 15)</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Cancel</a>
            <button type="submit" class="btn teal darken-2 waves-effect waves-light">Create Module</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
});
</script>

<?php include '../includes/footer.php'; ?>

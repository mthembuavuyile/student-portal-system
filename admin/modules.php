<?php
$page_title = "Module Management";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];

// Handle Add Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_module') {
    if (!validate_csrf_token('admin/modules.php')) { exit(); }
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
    if (!validate_csrf_token('admin/modules.php')) { exit(); }
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

// Summary metrics
$totalModules = count($modules);
$staffedCount = 0;
$totalCredits = 0;
foreach ($modules as $m) {
    if (!empty($m['lecturer_count'])) {
        $staffedCount++;
    }
    $totalCredits += (int)($m['credits'] ?? 15);
}
?>

<style>
/* Modern Scoped Grid & Card Architecture */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
    gap: 22px;
    align-items: stretch;
    margin-top: 15px;
    margin-bottom: 40px;
}

@media (max-width: 600px) {
    .modules-grid {
        grid-template-columns: 1fr;
    }
}

.module-card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease;
    overflow: hidden;
}

.module-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
}

.module-card-header {
    padding: 18px 20px 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}

.module-card-body {
    padding: 16px 20px;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.module-card-footer {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    margin-top: auto;
}

.module-code-badge {
    background: #e6fffa;
    color: #0f766e;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #99f6e4;
    letter-spacing: 0.4px;
    display: inline-block;
}

.module-credits-badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    font-size: 0.78rem;
    padding: 4px 9px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    display: inline-block;
}

.module-title {
    margin: 10px 0 0 0;
    font-size: 1.12rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    min-height: 2.7rem;
    display: flex;
    align-items: center;
}

.meta-section-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.76rem;
    text-transform: uppercase;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.4px;
    margin-bottom: 6px;
}

.lecturer-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-bottom: 5px;
    font-size: 0.85rem;
}

.chip-course {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    margin: 2px 4px 2px 0;
}
</style>

<div class="row" style="margin-top: 15px;">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h4 style="margin: 0; font-weight: 600;">
                    <i class="material-icons left medium teal-text">book</i>Module Management
                </h4>
                <p class="grey-text" style="margin: 5px 0 0 0;">
                    Institutional curriculum registry. Modules may be shared across qualifications and co-taught by multiple lecturers.
                </p>
            </div>
            <div>
                <a href="#addModuleModal" class="btn waves-effect waves-light teal darken-2 modal-trigger z-depth-1" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left">add_circle</i>New Module
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

<!-- Metric Stat Cards -->
<div class="row" style="margin-bottom: 10px;">
    <div class="col s12 m4">
        <div class="card-panel white z-depth-1" style="border-left: 5px solid #00897b; border-radius: 6px; padding: 15px 20px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium teal-text text-darken-2">menu_book</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #00897b;"><?php echo $totalModules; ?></h4>
                    <span class="grey-text text-darken-1">Curriculum Modules</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12 m4">
        <div class="card-panel white z-depth-1" style="border-left: 5px solid #283593; border-radius: 6px; padding: 15px 20px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium indigo-text text-darken-3">supervisor_account</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #283593;"><?php echo $staffedCount; ?> / <?php echo $totalModules; ?></h4>
                    <span class="grey-text text-darken-1">Staffed Modules</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col s12 m4">
        <div class="card-panel white z-depth-1" style="border-left: 5px solid #f59e0b; border-radius: 6px; padding: 15px 20px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium amber-text text-darken-3">stars</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #d97706;"><?php echo $totalCredits; ?></h4>
                    <span class="grey-text text-darken-1">Total Credit Value</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="row">
    <div class="col s12">
        <div class="card-panel white z-depth-1" style="padding: 12px 20px; border-radius: 8px;">
            <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap;">
                <div class="col s12 m6 l4">
                    <div class="input-field" style="margin: 0;">
                        <i class="material-icons prefix">search</i>
                        <input type="text" id="moduleSearchInput" onkeyup="filterModules()" placeholder="Search code, module name, or educator...">
                    </div>
                </div>
                <div class="col s12 m6 l8 right-align">
                    <span class="grey-text text-darken-1" id="moduleCountNotice">
                        Showing all <strong><?php echo count($modules); ?></strong> modules
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equal Height Responsive CSS Grid -->
<div class="modules-grid" id="modulesGrid">
    <?php foreach ($modules as $mData): ?>
        <?php 
            $moduleObj = new Module($mData);
            $lecturers = $moduleObj->getLecturers($pdo);
            $courses = $moduleObj->getCourses($pdo);

            $lecturerNames = [];
            foreach ($lecturers as $l) {
                $lecturerNames[] = $l['first_name'] . ' ' . $l['surname'];
            }
            $courseCodes = array_column($courses, 'course_code');
        ?>
        <div class="module-card" 
             data-code="<?php echo htmlspecialchars(strtolower($moduleObj->getModuleCode())); ?>"
             data-name="<?php echo htmlspecialchars(strtolower($moduleObj->getModuleName())); ?>"
             data-lecturers="<?php echo htmlspecialchars(strtolower(implode(' ', $lecturerNames))); ?>"
             data-courses="<?php echo htmlspecialchars(strtolower(implode(' ', $courseCodes))); ?>">
            
            <!-- Card Header -->
            <div class="module-card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="module-code-badge">
                        <?php echo htmlspecialchars($moduleObj->getModuleCode()); ?>
                    </span>
                    <span class="module-credits-badge">
                        <?php echo htmlspecialchars($moduleObj->getCredits()); ?> Credits
                    </span>
                </div>
                <h5 class="module-title">
                    <?php echo htmlspecialchars($moduleObj->getModuleName()); ?>
                </h5>
            </div>

            <!-- Card Body -->
            <div class="module-card-body">
                <!-- Assigned Lecturers -->
                <div>
                    <div class="meta-section-title">
                        <i class="material-icons tiny teal-text text-darken-2">supervisor_account</i>Assigned Lecturers:
                    </div>
                    <?php if (!empty($lecturers)): ?>
                        <?php foreach ($lecturers as $lec): ?>
                            <div class="lecturer-item">
                                <span style="font-weight: 600; color: #1e293b;">
                                    <?php echo htmlspecialchars($lec['first_name'] . ' ' . $lec['surname']); ?>
                                </span>
                                <?php if ($lec['is_primary']): ?>
                                    <span class="badge green white-text" style="float:none; padding: 2px 7px; border-radius: 4px; font-size: 0.72rem; font-weight: 600;">Primary</span>
                                <?php else: ?>
                                    <span class="grey-text text-darken-1" style="font-size: 0.75rem;">(Co-Lecturer)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; color: #94a3b8; font-style: italic; padding: 6px 0;">
                            No lecturers assigned yet.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Courses Offering This Module -->
                <div>
                    <div class="meta-section-title">
                        <i class="material-icons tiny teal-text text-darken-2">school</i>Offered In Programmes:
                    </div>
                    <?php if (!empty($courses)): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php foreach ($courses as $c): ?>
                                <span class="chip-course">
                                    <span><?php echo htmlspecialchars($c['course_code']); ?></span>: 
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($c['semester'] ?? 'Semester'); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; color: #94a3b8; font-style: italic; padding: 6px 0;">
                            Not assigned to any qualification.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Footer / Actions -->
            <div class="module-card-footer">
                <a href="#assignLecModal<?php echo $moduleObj->getModuleId(); ?>" 
                   class="btn waves-effect waves-light teal darken-2 modal-trigger" 
                   style="width: 100%; border-radius: 6px; font-weight: 600; text-transform: none; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="material-icons left tiny">person_add</i>Assign Lecturer
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="noModulesMatch" class="card-panel grey lighten-4 center-align" style="display: none; padding: 40px; border-radius: 8px;">
    <i class="material-icons large grey-text text-lighten-1">search_off</i>
    <h5 class="grey-text text-darken-2" style="font-weight: 600;">No Matching Modules</h5>
    <p class="grey-text">Try adjusting your search query or clear the filter to view all modules.</p>
</div>

<!-- ========================================== -->
<!-- Dedicated Modals Container (Safe from Grid) -->
<!-- ========================================== -->
<div class="modals-container">
    <?php foreach ($modules as $mData): ?>
        <?php $mId = $mData['module_id']; ?>
        <div id="assignLecModal<?php echo $mId; ?>" class="modal" style="max-width: 600px; border-radius: 8px;">
            <form method="POST" action="modules.php">
                <input type="hidden" name="action" value="assign_lecturer">
                <?php csrf_token_field(); ?>
                <input type="hidden" name="module_id" value="<?php echo $mId; ?>">
                
                <div class="modal-content" style="padding: 24px;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <i class="material-icons medium teal-text text-darken-2" style="margin-right: 12px;">person_add</i>
                        <div>
                            <h5 style="margin: 0; font-weight: 600;">Assign Lecturer</h5>
                            <span class="grey-text"><?php echo htmlspecialchars($mData['module_code'] . ' - ' . $mData['module_name']); ?></span>
                        </div>
                    </div>

                    <div class="divider" style="margin-bottom: 20px;"></div>
                    
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
                            <label>Teaching Staff Member *</label>
                        </div>
                        <div class="col s12" style="margin-top: 10px;">
                            <p>
                                <label>
                                    <input type="checkbox" name="is_primary" value="1" checked />
                                    <span>Primary Instructor / Module Coordinator</span>
                                </label>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 10px 24px;">
                    <a href="#!" class="modal-close btn-flat">Cancel</a>
                    <button type="submit" class="btn teal darken-2 waves-effect waves-light font-weight-bold">
                        <i class="material-icons left">save</i>Save Assignment
                    </button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>

    <!-- Add Module Modal -->
    <div id="addModuleModal" class="modal" style="max-width: 600px; border-radius: 8px;">
        <form method="POST" action="modules.php">
            <input type="hidden" name="action" value="add_module">
            <?php csrf_token_field(); ?>
            <div class="modal-content" style="padding: 24px;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <i class="material-icons medium teal-text text-darken-2" style="margin-right: 12px;">add_circle</i>
                    <div>
                        <h5 style="margin: 0; font-weight: 600;">Create Curriculum Module</h5>
                        <span class="grey-text">Define code, title, and institutional credits.</span>
                    </div>
                </div>

                <div class="divider" style="margin-bottom: 20px;"></div>

                <div class="row">
                    <div class="input-field col s12 m8">
                        <input type="text" id="module_name" name="module_name" required placeholder="e.g. Cloud Computing & Virtualization">
                        <label for="module_name" class="active">Module Name *</label>
                    </div>
                    <div class="input-field col s12 m4">
                        <input type="text" id="module_code" name="module_code" required placeholder="e.g. CSC301">
                        <label for="module_code" class="active">Module Code *</label>
                    </div>
                    <div class="input-field col s12">
                        <input type="number" id="credits" name="credits" value="15" min="1" max="120" required>
                        <label for="credits" class="active">Credit Weight *</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 10px 24px;">
                <a href="#!" class="modal-close btn-flat">Cancel</a>
                <button type="submit" class="btn teal darken-2 waves-effect waves-light font-weight-bold">
                    <i class="material-icons left">save</i>Create Module
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
});

function filterModules() {
    var input = document.getElementById('moduleSearchInput');
    var query = input.value.trim().toLowerCase();
    var cards = document.querySelectorAll('.module-card');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var code = card.getAttribute('data-code') || '';
        var name = card.getAttribute('data-name') || '';
        var lecturers = card.getAttribute('data-lecturers') || '';
        var courses = card.getAttribute('data-courses') || '';

        if (code.indexOf(query) > -1 || name.indexOf(query) > -1 || lecturers.indexOf(query) > -1 || courses.indexOf(query) > -1) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    var countNotice = document.getElementById('moduleCountNotice');
    if (countNotice) {
        countNotice.innerHTML = 'Showing <strong>' + visibleCount + '</strong> of ' + cards.length + ' modules';
    }

    var noMatch = document.getElementById('noModulesMatch');
    if (noMatch) {
        noMatch.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>

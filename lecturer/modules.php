<?php
$page_title = "My Teaching Modules";
$allowed_roles = ['lecturer'];
include '../includes/header.php';

// Fetch lecturer details
$lecturerId = $_SESSION['lecturer_id'] ?? null;
$lecturer = null;
$modules = [];

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
    $modules = $lecturer->getModules($pdo);
} catch (Throwable $e) {
    echo '<div class="card-panel red white-text" style="border-radius: 8px; margin-top: 20px;">
            <h5><i class="material-icons left">error</i>Database Configuration Error</h5>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
    include '../includes/footer.php';
    exit();
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
    background: #eef2ff;
    color: #3730a3;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #c7d2fe;
    letter-spacing: 0.4px;
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
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="students.php" class="btn waves-effect waves-light indigo darken-3" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left">people</i>My Students
                </a>
                <a href="results.php" class="btn waves-effect waves-light amber darken-3" style="border-radius: 6px; font-weight: 600;">
                    <i class="material-icons left">edit_note</i>Assign Marks
                </a>
            </div>
        </div>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<?php if (!empty($modules)): ?>
    <!-- Search Bar -->
    <div class="row" style="margin-bottom: 10px;">
        <div class="col s12">
            <div class="card-panel white z-depth-1" style="padding: 12px 20px; border-radius: 8px;">
                <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap;">
                    <div class="col s12 m6 l5">
                        <div class="input-field" style="margin: 0;">
                            <i class="material-icons prefix">search</i>
                            <input type="text" id="lecturerModuleSearch" onkeyup="filterLecturerModules()" placeholder="Search your module code or title...">
                        </div>
                    </div>
                    <div class="col s12 m6 l7 right-align">
                        <span class="grey-text text-darken-1" id="lecturerModuleCountNotice">
                            Assigned to <strong><?php echo count($modules); ?></strong> curriculum modules
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equal Height Responsive CSS Grid -->
    <div class="modules-grid" id="lecturerModulesGrid">
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
            <div class="module-card"
                 data-code="<?php echo htmlspecialchars(strtolower($m['module_code'])); ?>"
                 data-name="<?php echo htmlspecialchars(strtolower($m['module_name'])); ?>">
                
                <!-- Card Header -->
                <div class="module-card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="module-code-badge">
                            <?php echo htmlspecialchars($m['module_code']); ?>
                        </span>
                        <div>
                            <?php if ($m['is_primary']): ?>
                                <span class="badge blue white-text" style="float:none; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Primary</span>
                            <?php else: ?>
                                <span class="badge grey lighten-1 grey-text text-darken-4" style="float:none; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Co-Lecturer</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h5 class="module-title">
                        <?php echo htmlspecialchars($m['module_name']); ?>
                    </h5>
                </div>

                <!-- Card Body -->
                <div class="module-card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div>
                            <span class="grey-text" style="font-size: 0.75rem; display: block; text-transform: uppercase; font-weight: 700;">CREDITS</span>
                            <strong style="color: #1e293b;"><?php echo htmlspecialchars($m['credits']); ?> Credits</strong>
                        </div>
                        <div class="right-align">
                            <span class="grey-text" style="font-size: 0.75rem; display: block; text-transform: uppercase; font-weight: 700;">ENROLLED STUDENTS</span>
                            <strong style="color: #00897b; font-size: 1.05rem;"><?php echo $stCount; ?> Students</strong>
                        </div>
                    </div>

                    <!-- Programmes Offering This Module -->
                    <div>
                        <div class="meta-section-title">
                            <i class="material-icons tiny indigo-text">school</i>Programmes & Semesters:
                        </div>
                        <?php if (!empty($offeringCourses)): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                <?php foreach ($offeringCourses as $oc): ?>
                                    <span class="chip-course">
                                        <span><?php echo htmlspecialchars($oc['course_code']); ?></span>: 
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($oc['semester'] ?? 'Semester'); ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">
                                Not currently mapped to an active qualification.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="module-card-footer" style="display: flex; gap: 8px;">
                    <a href="students.php?module_id=<?php echo $m['module_id']; ?>" 
                       class="btn-small waves-effect waves-light indigo darken-3" 
                       style="flex: 1; border-radius: 6px; font-weight: 600; text-transform: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="material-icons left tiny">people</i>Students (<?php echo $stCount; ?>)
                    </a>
                    <a href="results.php?module_id=<?php echo $m['module_id']; ?>" 
                       class="btn-small waves-effect waves-light amber darken-3" 
                       style="flex: 1; border-radius: 6px; font-weight: 600; text-transform: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="material-icons left tiny">edit_note</i>Marks
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="noLecturerModulesMatch" class="card-panel grey lighten-4 center-align" style="display: none; padding: 40px; border-radius: 8px;">
        <i class="material-icons large grey-text text-lighten-1">search_off</i>
        <h5 class="grey-text text-darken-2" style="font-weight: 600;">No Matching Modules</h5>
        <p class="grey-text">Try adjusting your search query to find assigned modules.</p>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col s12">
            <div class="card-panel grey lighten-4 center-align" style="padding: 40px; border-radius: 8px;">
                <i class="material-icons large grey-text">menu_book</i>
                <h5>No Modules Assigned</h5>
                <p class="grey-text">You are not currently assigned to teach any modules. Please contact system administration to be assigned curriculum modules.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function filterLecturerModules() {
    var input = document.getElementById('lecturerModuleSearch');
    var query = input.value.trim().toLowerCase();
    var cards = document.querySelectorAll('#lecturerModulesGrid .module-card');
    var visibleCount = 0;

    cards.forEach(function(card) {
        var code = card.getAttribute('data-code') || '';
        var name = card.getAttribute('data-name') || '';

        if (code.indexOf(query) > -1 || name.indexOf(query) > -1) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    var countNotice = document.getElementById('lecturerModuleCountNotice');
    if (countNotice) {
        countNotice.innerHTML = 'Showing <strong>' + visibleCount + '</strong> of ' + cards.length + ' modules';
    }

    var noMatch = document.getElementById('noLecturerModulesMatch');
    if (noMatch) {
        noMatch.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>

<?php
$page_title = "Course Management";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];
$success_message = '';

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    if (!validate_csrf_token('admin/courses.php')) { exit(); }
    $courseName = sanitize_input($_POST['course_name'] ?? '');
    $courseCode = strtoupper(sanitize_input($_POST['course_code'] ?? ''));
    $duration   = sanitize_input($_POST['duration'] ?? '3 Years');

    if (empty($courseName) || empty($courseCode)) {
        $errors[] = "Course name and course code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, duration) VALUES (:code, :name, :duration)");
            $stmt->execute([':code' => $courseCode, ':name' => $courseName, ':duration' => $duration]);
            $_SESSION['success_message'] = "Course '{$courseName}' successfully added!";
            redirect('admin/courses.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Assign Module to Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_module') {
    if (!validate_csrf_token('admin/courses.php')) { exit(); }
    $courseId = (int)($_POST['course_id'] ?? 0);
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $semester = sanitize_input($_POST['semester'] ?? 'Semester 1');

    if ($courseId > 0 && $moduleId > 0) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO course_modules (course_id, module_id, semester) 
                VALUES (:cid, :mid, :sem) 
                ON DUPLICATE KEY UPDATE semester = VALUES(semester)
            ");
            $stmt->execute([':cid' => $courseId, ':mid' => $moduleId, ':sem' => $semester]);
            $_SESSION['success_message'] = "Module successfully assigned to course!";
            redirect('admin/courses.php');
        } catch (PDOException $e) {
            $errors[] = "Error linking module to course: " . $e->getMessage();
        }
    } else {
        $errors[] = "Please select both a course and a module.";
    }
}

$courses = Course::getAll($pdo);
$allModules = Module::getAll($pdo);
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
            <h4><i class="material-icons left medium blue-text">school</i>Course Management</h4>
            <a href="#addCourseModal" class="btn waves-effect waves-light blue darken-2 modal-trigger">
                <i class="material-icons left">add</i>New Course
            </a>
        </div>
        <p class="grey-text">Manage academic programmes, program durations, and curricula. Modules may be shared across courses supporting many-to-many relationships.</p>

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
    <?php foreach ($courses as $cData): ?>
        <?php 
            $courseObj = new Course($cData);
            $courseModules = $courseObj->getModules($pdo);
            $enrolledStudents = $courseObj->getStudents($pdo);
        ?>
        <div class="col s12 l6">
            <div class="card hoverable">
                <div class="card-content">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                        <div>
                            <span class="card-title bold" style="font-weight: 600; margin-bottom: 8px; display: block;">
                                <?php echo htmlspecialchars($courseObj->getCourseName()); ?>
                            </span>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span class="chip blue lighten-4 blue-text text-darken-3">Code: <?php echo htmlspecialchars($courseObj->getCourseCode()); ?></span>
                                <span class="chip grey lighten-3"><i class="material-icons">schedule</i><?php echo htmlspecialchars($courseObj->getDuration()); ?></span>
                            </div>
                        </div>
                        <span class="badge green white-text active-students-badge">
                            <?php echo count($enrolledStudents); ?> Active Students
                        </span>
                    </div>

                    <div class="divider" style="margin: 15px 0;"></div>

                    <h6 class="blue-text text-darken-2" style="font-weight: 500;">
                        <i class="material-icons tiny left">book</i>Curriculum Modules (<?php echo count($courseModules); ?>)
                    </h6>

                    <?php if (!empty($courseModules)): ?>
                        <table class="striped dense-table" style="font-size: 0.9rem; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Module Name</th>
                                    <th>Credits</th>
                                    <th>Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseModules as $mod): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($mod['module_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mod['module_name']); ?></td>
                                        <td><?php echo htmlspecialchars($mod['credits']); ?></td>
                                        <td><span class="chip tiny grey lighten-3"><?php echo htmlspecialchars($mod['semester'] ?? 'Semester 1'); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="grey-text">No modules currently attached to this course.</p>
                    <?php endif; ?>
                </div>

                <div class="card-action">
                    <a href="#assignModal<?php echo $courseObj->getCourseId(); ?>" class="btn-flat blue-text modal-trigger">
                        <i class="material-icons left">add_circle</i>Assign Module
                    </a>
                </div>
            </div>
        </div>

        <!-- Assign Module Modal for this Course -->
        <div id="assignModal<?php echo $courseObj->getCourseId(); ?>" class="modal">
            <form method="POST" action="courses.php">
                <input type="hidden" name="action" value="assign_module">
                <?php csrf_token_field(); ?>
                <input type="hidden" name="course_id" value="<?php echo $courseObj->getCourseId(); ?>">
                <div class="modal-content">
                    <h5>Assign Module to <?php echo htmlspecialchars($courseObj->getCourseName()); ?></h5>
                    <p class="grey-text">Modules may be shared across courses. Select an institutional module and semester.</p>
                    
                    <div class="row">
                        <div class="input-field col s12 m8">
                            <select name="module_id" required>
                                <option value="" disabled selected>Select Module</option>
                                <?php foreach ($allModules as $m): ?>
                                    <option value="<?php echo $m['module_id']; ?>">
                                        <?php echo htmlspecialchars($m['module_code'] . ' - ' . $m['module_name'] . ' (' . $m['credits'] . ' credits)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Module</label>
                        </div>
                        <div class="input-field col s12 m4">
                            <select name="semester" required>
                                <option value="Semester 1" selected>Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                                <option value="Year Module">Year Module</option>
                            </select>
                            <label>Semester</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close btn-flat">Cancel</a>
                    <button type="submit" class="btn blue darken-2 waves-effect waves-light">Assign to Course</button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal">
    <form method="POST" action="courses.php">
        <input type="hidden" name="action" value="add_course">
        <?php csrf_token_field(); ?>
        <div class="modal-content">
            <h5>Create New Course</h5>
            <div class="row">
                <div class="input-field col s12 m8">
                    <input type="text" id="course_name" name="course_name" required>
                    <label for="course_name">Course Name (e.g. Systems Development)</label>
                </div>
                <div class="input-field col s12 m4">
                    <input type="text" id="course_code" name="course_code" required>
                    <label for="course_code">Course Code (e.g. SD101)</label>
                </div>
                <div class="input-field col s12">
                    <select name="duration" id="duration" required>
                        <option value="1 Year">1 Year (Higher Certificate)</option>
                        <option value="2 Years">2 Years (Diploma)</option>
                        <option value="3 Years" selected>3 Years (Degree / National Diploma)</option>
                        <option value="4 Years">4 Years (Professional Degree / Honours)</option>
                    </select>
                    <label>Duration</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Cancel</a>
            <button type="submit" class="btn blue darken-2 waves-effect waves-light">Create Course</button>
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

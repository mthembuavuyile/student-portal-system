<?php
$page_title = "Register Student";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];
$generatedNumberPreview = RegistrationService::generateStudentNumber($pdo);
$courses = Course::getAll($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin/register_student.php')) {
        exit();
    }
    // Gather and sanitize input
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $surname    = sanitize_input($_POST['surname'] ?? '');
    $id_number  = sanitize_input($_POST['id_number'] ?? '');
    $email      = filter_var(sanitize_input($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $dob        = sanitize_input($_POST['dob'] ?? '');
    $course_id  = (int)($_POST['course_id'] ?? 0);
    $enroll_date = sanitize_input($_POST['enroll_date'] ?? date('Y-m-d'));
    $auto_generate = isset($_POST['auto_generate']);
    $custom_student_id = sanitize_input($_POST['student_id'] ?? '');

    // Fallback if full_name was posted (legacy compatibility)
    if (empty($first_name) && !empty($_POST['full_name'])) {
        $parts = explode(' ', trim($_POST['full_name']), 2);
        $first_name = $parts[0] ?? '';
        $surname = $parts[1] ?? 'Student';
    }

    // Validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($surname)) $errors[] = "Surname is required.";
    if (empty($id_number)) $errors[] = "National ID Number is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (empty($dob)) $errors[] = "Date of birth is required.";
    if ($course_id <= 0) $errors[] = "Course selection is required.";
    if (!$auto_generate && empty($custom_student_id)) $errors[] = "Please provide a Student ID Number or enable Auto-generation.";

    if (empty($errors)) {
        try {
            // Use RegistrationService to register student, enforcing:
            // 1. Automatic unique student number generation
            // 2. Database uniqueness
            // 3. One active course enrollment rule
            // 4. Backward compatibility
            $registrationResult = RegistrationService::registerStudent($pdo, [
                'first_name' => $first_name,
                'surname' => $surname,
                'id_number' => $id_number,
                'email' => $email,
                'dob' => $dob,
                'course_id' => $course_id,
                'enroll_date' => $enroll_date,
                'customStudentNumber' => !$auto_generate ? $custom_student_id : null,
                'password' => 'password123'
            ]);

            $_SESSION['success_message'] = "Student registered successfully! Student Number: " . $registrationResult['studentNumber'] . " (Default Password: 'password123')";
            redirect('admin/student_list.php');

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
        <div class="card">
            <div class="card-content">
                <span class="card-title center-align bold" style="font-weight: 600;">
                    <i class="material-icons left medium blue-text">person_add</i>
                    Register New Student
                </span>
                <p class="center-align grey-text" style="margin-bottom: 25px;">
                    Student Portal System Registration Service &bull; Department of Forestry, Fisheries & the Environment
                </p>
                
                <?php if (!empty($errors)): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo $err; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register_student.php" id="studentRegForm">
                    <?php csrf_token_field(); ?>
                    <!-- Name & ID Details -->
                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">person</i>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">person_outline</i>
                            <input type="text" id="surname" name="surname" required value="<?php echo htmlspecialchars($_POST['surname'] ?? ''); ?>">
                            <label for="surname">Surname</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">fingerprint</i>
                            <input type="text" id="id_number" name="id_number" required value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>" placeholder="e.g. 0504205812083">
                            <label for="id_number">National ID Number</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">email</i>
                            <input type="email" id="email" name="email" class="validate" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <label for="email">Email Address</label>
                            <span class="helper-text" data-error="Invalid email format"></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">cake</i>
                            <input type="text" id="dob" name="dob" class="datepicker" required value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                            <label for="dob">Date of Birth</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">event</i>
                            <input type="text" id="enroll_date" name="enroll_date" class="datepicker" required value="<?php echo htmlspecialchars($_POST['enroll_date'] ?? date('Y-m-d')); ?>">
                            <label for="enroll_date">Enrollment Date</label>
                        </div>
                    </div>

                    <!-- Course Selection & Active Enrollment Rule -->
                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">school</i>
                            <select name="course_id" id="course_id" required>
                                <option value="" disabled selected>Select an Academic Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $c['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ') - ' . $c['duration']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Course of Study (Active Enrollment)</label>
                            <span class="helper-text">Business Rule: Student will have this as their single active course enrollment.</span>
                        </div>
                    </div>

                    <!-- Automatic Student Number Generation Section -->
                    <div class="card-panel grey lighten-4" style="margin-top: 15px; border-radius: 8px;">
                        <span style="font-weight: 600; font-size: 1rem;">
                            <i class="material-icons tiny left">auto_mode</i>Automatic Student Number (RegistrationService)
                        </span>
                        <p class="grey-text" style="font-size: 0.85rem; margin: 5px 0 15px 0;">
                            The RegistrationService automatically generates a unique institutional student number enforcing database uniqueness. You may uncheck to manually specify a legacy student ID.
                        </p>
                        
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s12 m6">
                                <label>
                                    <input type="checkbox" id="auto_generate" name="auto_generate" value="1" checked onchange="toggleStudentIdInput()" />
                                    <span>Auto-generate unique Student Number</span>
                                </label>
                                <div id="autoPreview" style="margin-top: 8px; font-weight: 500; color: #1565c0;">
                                    Next Available Number: <code><?php echo htmlspecialchars($generatedNumberPreview); ?></code>
                                </div>
                            </div>
                            <div class="input-field col s12 m6" id="manualIdContainer" style="display: none; margin-top: 0;">
                                <input type="text" id="student_id" name="student_id" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" placeholder="Enter custom / legacy student ID">
                                <label for="student_id">Manual Student ID</label>
                            </div>
                        </div>
                    </div>

                    <div class="row center-align" style="margin-top: 30px;">
                        <button class="btn-large waves-effect waves-light blue darken-2" type="submit" name="action">
                            Complete Student Registration <i class="material-icons right">how_to_reg</i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStudentIdInput() {
    const isAuto = document.getElementById('auto_generate').checked;
    const manualContainer = document.getElementById('manualIdContainer');
    const autoPreview = document.getElementById('autoPreview');
    const manualInput = document.getElementById('student_id');

    if (isAuto) {
        manualContainer.style.display = 'none';
        autoPreview.style.display = 'block';
        manualInput.removeAttribute('required');
    } else {
        manualContainer.style.display = 'block';
        autoPreview.style.display = 'none';
        manualInput.setAttribute('required', 'required');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var datepickers = document.querySelectorAll('.datepicker');
    M.Datepicker.init(datepickers, {
        format: 'yyyy-mm-dd',
        yearRange: [1970, 2030]
    });
    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);
});
</script>

<?php include '../includes/footer.php'; ?>
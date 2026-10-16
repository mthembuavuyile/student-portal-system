<?php
require_once '../config.php';
require_once '../functions.php';
require_login(['admin']);

$errors = [];

// 1. Check for a valid ID in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid request. No student ID provided.";
    redirect('admin/student_list.php');
}
$student_id = intval($_GET['id']);

// Fetch courses
$courses = Course::getAll($pdo);

// 2. Handle the form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('admin/update_student.php?id=' . $student_id)) {
        exit();
    }
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $surname    = sanitize_input($_POST['surname'] ?? '');
    $id_number  = sanitize_input($_POST['id_number'] ?? '');
    $email      = filter_var(sanitize_input($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $dob        = sanitize_input($_POST['dob'] ?? '');
    $course_name = sanitize_input($_POST['course'] ?? '');
    $enroll_date = sanitize_input($_POST['enroll_date'] ?? '');
    $status      = sanitize_input($_POST['academic_status'] ?? 'Active');

    if (empty($first_name)) $errors[] = "First Name cannot be empty.";
    if (empty($surname)) $errors[] = "Surname cannot be empty.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }
    }

    if (empty($errors)) {
        try {
            $full_name = trim($first_name . ' ' . $surname);

            $sql = "UPDATE students SET 
                        first_name = :fname,
                        surname = :sname,
                        id_number = :idnum,
                        full_name = :full_name, 
                        email = :email, 
                        date_of_birth = :dob, 
                        course_of_study = :course, 
                        enrollment_date = :enroll_date, 
                        academic_status = :status 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fname' => $first_name,
                ':sname' => $surname,
                ':idnum' => $id_number,
                ':full_name' => $full_name,
                ':email' => $email,
                ':dob' => $dob,
                ':course' => $course_name,
                ':enroll_date' => $enroll_date,
                ':status' => $status,
                ':id' => $student_id
            ]);

            // Update matching course enrollment if needed
            $courseObj = Course::findByCode($pdo, $course_name);
            if (!$courseObj) {
                $cStmt = $pdo->prepare("SELECT * FROM courses WHERE course_name = :cname LIMIT 1");
                $cStmt->execute([':cname' => $course_name]);
                $cRow = $cStmt->fetch();
                if ($cRow) {
                    $pdo->prepare("UPDATE enrollments SET course_id = :cid, status = :st WHERE student_id = :sid AND status = 'Active'")
                        ->execute([':cid' => $cRow['course_id'], ':st' => $status, ':sid' => $student_id]);
                }
            }

            // Update user password if provided
            if (!empty($new_password)) {
                $currentStudent = get_student_by_id($pdo, $student_id);
                $studentNumber = $currentStudent['student_number'] ?? $currentStudent['student_id_number'];
                $pwdResult = User::adminResetPassword($pdo, $studentNumber, $new_password);
                if (!$pwdResult['success']) {
                    $_SESSION['error_message'] = "Student details updated, but password failed: " . $pwdResult['message'];
                } else {
                    $log_msg = "Admin '" . ($_SESSION['username'] ?? 'admin') . "' updated portal password for student {$studentNumber}.";
                    log_action($log_msg, __DIR__ . '/deleted_logs.txt');
                }
            }

            $_SESSION['success_message'] = "Student record " . (!empty($new_password) ? "and password " : "") . "updated successfully!";
            redirect('admin/student_profile.php?id=' . $student_id);

        } catch (PDOException $e) {
            $errors[] = "Database error: Could not update record. " . $e->getMessage();
        }
    }
}

// 3. Fetch current student data to pre-fill the form
$student = get_student_by_id($pdo, $student_id);
if (!$student) {
    $_SESSION['error_message'] = "Could not find student to update.";
    redirect('admin/student_list.php');
}

// Ensure first_name and surname are populated from full_name if empty
$firstNameVal = $student['first_name'] ?? '';
$surnameVal = $student['surname'] ?? '';
if (empty($firstNameVal) && !empty($student['full_name'])) {
    $parts = explode(' ', trim($student['full_name']), 2);
    $firstNameVal = $parts[0] ?? '';
    $surnameVal = $parts[1] ?? 'Student';
}

$page_title = "Update Student Information";
$allowed_roles = ['admin'];
include '../includes/header.php';
?>

<div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
        <a href="student_profile.php?id=<?php echo $student_id; ?>" class="btn-flat waves-effect"><i class="material-icons left">arrow_back</i>Cancel Update</a>
        <div class="card" style="margin-top: 15px;">
            <div class="card-content">
                <span class="card-title center-align">Editing Profile for: <?php echo htmlspecialchars($student['full_name']); ?></span>
                <p class="center-align grey-text">Student Number: <code><?php echo htmlspecialchars($student['student_number'] ?: $student['student_id_number']); ?></code> (Cannot be changed)</p>
                <div class="divider" style="margin: 20px 0;"></div>

                <?php if (!empty($errors)): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo $err; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="update_student.php?id=<?php echo $student_id; ?>">
                    <?php csrf_token_field(); ?>
                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">person</i>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($firstNameVal); ?>">
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">person_outline</i>
                            <input type="text" id="surname" name="surname" required value="<?php echo htmlspecialchars($surnameVal); ?>">
                            <label for="surname">Surname</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">fingerprint</i>
                            <input type="text" id="id_number" name="id_number" value="<?php echo htmlspecialchars($student['id_number'] ?? ''); ?>">
                            <label for="id_number">National ID Number</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">email</i>
                            <input type="email" id="email" name="email" class="validate" required value="<?php echo htmlspecialchars($student['email']); ?>">
                            <label for="email">Email Address</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">cake</i>
                            <input type="text" id="dob" name="dob" class="datepicker" required value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                            <label for="dob">Date of Birth</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">event</i>
                            <input type="text" id="enroll_date" name="enroll_date" class="datepicker" required value="<?php echo htmlspecialchars($student['enrollment_date']); ?>">
                            <label for="enroll_date">Enrollment Date</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">school</i>
                            <select name="course" id="course" required>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['course_name']); ?>" <?php echo ($student['course_of_study'] == $c['course_name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Course of Study</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">info</i>
                            <select name="academic_status" id="academic_status" required>
                                <option value="<?php echo STATUS_ACTIVE; ?>" <?php echo ($student['academic_status'] == STATUS_ACTIVE) ? 'selected' : ''; ?>><?php echo STATUS_ACTIVE; ?></option>
                                <option value="<?php echo STATUS_PENDING; ?>" <?php echo ($student['academic_status'] == STATUS_PENDING) ? 'selected' : ''; ?>><?php echo STATUS_PENDING; ?></option>
                                <option value="<?php echo STATUS_GRADUATED; ?>" <?php echo ($student['academic_status'] == STATUS_GRADUATED) ? 'selected' : ''; ?>><?php echo STATUS_GRADUATED; ?></option>
                                <option value="<?php echo STATUS_DROPPED; ?>" <?php echo ($student['academic_status'] == STATUS_DROPPED) ? 'selected' : ''; ?>><?php echo STATUS_DROPPED; ?></option>
                            </select>
                            <label>Academic Status</label>
                        </div>
                    </div>

                    <div class="divider" style="margin: 25px 0 15px 0;"></div>
                    <h6 class="blue-text text-darken-3" style="font-weight: 600; margin-bottom: 5px;">
                        <i class="material-icons tiny left">lock</i>Portal Login & Password Reset
                    </h6>
                    <p class="grey-text" style="font-size: 0.85rem; margin-top: 0; margin-bottom: 15px;">
                        Leave blank to keep the student's existing password unchanged.
                    </p>

                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">vpn_key</i>
                            <input type="password" id="new_password" name="new_password" minlength="6">
                            <label for="new_password">New Password (optional)</label>
                            <span class="helper-text">Minimum 6 characters</span>
                        </div>
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">check_circle_outline</i>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                            <label for="confirm_password">Confirm New Password</label>
                        </div>
                    </div>

                    <div class="row center-align">
                        <button class="btn waves-effect waves-light green darken-2" type="submit">
                            Save Changes <i class="material-icons right">save</i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
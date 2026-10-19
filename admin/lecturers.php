<?php
$page_title = "Lecturer Directory & Management";
$allowed_roles = ['admin'];
include '../includes/header.php';

$errors = [];

// Handle Add Lecturer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lecturer') {
    if (!validate_csrf_token('admin/lecturers.php')) {
        exit();
    }
    $firstName = sanitize_input($_POST['first_name'] ?? '');
    $surname = sanitize_input($_POST['surname'] ?? '');
    $email = filter_var(sanitize_input($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($firstName) || empty($surname) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "First name, surname, and a valid email address are required.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO lecturers (first_name, surname, email) VALUES (:fn, :sn, :em)");
            $stmt->execute([':fn' => $firstName, ':sn' => $surname, ':em' => $email]);
            $newLecturerId = (int)$pdo->lastInsertId();

            // Auto-provision lecturer user login
            $usernameBase = explode('@', $email)[0];
            $username = $usernameBase;
            $counter = 1;
            while (User::findByUsername($pdo, $username)) {
                $username = $usernameBase . $counter;
                $counter++;
            }
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $userStmt = $pdo->prepare("INSERT INTO users (username, password, role, lecturer_id) VALUES (:u, :pwd, 'lecturer', :lid)");
            $userStmt->execute([':u' => $username, ':pwd' => $hash, ':lid' => $newLecturerId]);

            $pdo->commit();
            $_SESSION['success_message'] = "Lecturer '{$firstName} {$surname}' registered successfully! Educator login created: Username '{$username}' | Default Password 'password123'.";
            redirect('admin/lecturers.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$lecturers = Lecturer::getAll($pdo);
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
            <h4><i class="material-icons left medium indigo-text">supervisor_account</i>Lecturer Directory</h4>
            <a href="#addLecturerModal" class="btn waves-effect waves-light indigo darken-2 modal-trigger">
                <i class="material-icons left">person_add</i>New Lecturer
            </a>
        </div>
        <p class="grey-text">Lecturers: lecturerId, firstName, surname, email. Modules may be taught by multiple
            lecturers, supporting many-to-many relationships.</p>

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
                <table class="highlight responsive-table">
                    <thead>
                        <tr>
                            <th>Lecturer ID</th>
                            <th>Full Name</th>
                            <th>Login Account</th>
                            <th>Email Address</th>
                            <th>Modules Taught</th>
                            <th>Assigned Module Codes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lecturers)): ?>
                            <?php foreach ($lecturers as $lec): 
                                $userAcc = User::findByLecturerId($pdo, (int)$lec['lecturer_id']);
                            ?>
                                <tr>
                                    <td><span
                                            class="chip grey lighten-3">LEC-<?php echo str_pad($lec['lecturer_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($lec['first_name'] . ' ' . $lec['surname']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($userAcc): ?>
                                            <span class="chip indigo lighten-5 indigo-text font-weight-bold">
                                                <i class="material-icons left tiny">account_circle</i><?php echo htmlspecialchars($userAcc->getUsername()); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="grey-text" style="font-size: 0.85rem;">Not provisioned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><a
                                            href="mailto:<?php echo htmlspecialchars($lec['email']); ?>"><?php echo htmlspecialchars($lec['email']); ?></a>
                                    </td>
                                    <td><span class="badge blue white-text"
                                            style="float:none;"><?php echo htmlspecialchars($lec['module_count']); ?>
                                            Modules</span></td>
                                    <td>
                                        <?php if (!empty($lec['module_codes'])): ?>
                                            <?php foreach (explode(',', $lec['module_codes']) as $code): ?>
                                                <span class="chip tiny teal lighten-5 teal-text"><?php echo trim($code); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="grey-text">No active assignments</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="center-align">No lecturers registered in system.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Lecturer Modal -->
<div id="addLecturerModal" class="modal">
    <form method="POST" action="lecturers.php">
        <input type="hidden" name="action" value="add_lecturer">
        <?php csrf_token_field(); ?>
        <div class="modal-content">
            <h5>Register Academic Staff / Lecturer</h5>
            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="text" id="first_name" name="first_name" required>
                    <label for="first_name">First Name</label>
                </div>
                <div class="input-field col s12 m6">
                    <input type="text" id="surname" name="surname" required>
                    <label for="surname">Surname</label>
                </div>
                <div class="input-field col s12">
                    <input type="email" id="email" name="email" class="validate" required>
                    <label for="email">Institutional Email Address</label>
                    <span class="helper-text" data-error="Valid email required"></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Cancel</a>
            <button type="submit" class="btn indigo darken-2 waves-effect waves-light">Register Lecturer</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);
    });
</script>

<?php include '../includes/footer.php'; ?>
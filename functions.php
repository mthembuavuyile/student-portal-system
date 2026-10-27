<?php
/*
 * Reusable PHP Functions
 */

/**
 * Redirects the user to a specified URL.
 *
 * @param string $url The relative URL to redirect to.
 * @return void
 */
function redirect($url) {
    $target = BASE_URL . $url;
    if (!headers_sent()) {
        header("Location: " . $target);
        exit();
    } else {
        echo '<script type="text/javascript">window.location.href="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit();
    }
}

/**
 * Checks if a user is logged in. If not, redirects to the login page.
 *
 * @param array|null $allowed_roles An array of roles allowed to access the page. If null, any logged-in user is allowed.
 * @return void
 */
function require_login($allowed_roles = null) {
    if (!isset($_SESSION['user_id'])) {
        redirect('index.php');
    }

    if ($allowed_roles !== null && !in_array($_SESSION['role'], $allowed_roles)) {
        $_SESSION['error_message'] = "You are not authorized to view that page.";
        if ($_SESSION['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } elseif ($_SESSION['role'] === 'lecturer') {
            redirect('lecturer/dashboard.php');
        } else {
            redirect('student/dashboard.php');
        }
    }
}


/**
 * Sanitizes input data to prevent XSS attacks.
 *
 * @param string $data The input data to sanitize.
 * @return string The sanitized data.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validates that a South African 13-digit National ID matches the Date of Birth.
 * The first 6 digits of the ID must be YYMMDD corresponding to the birth date.
 *
 * @param string $idNumber 13-digit ID number
 * @param string $dob Date of Birth string (YYYY-MM-DD)
 * @return array [bool $isValid, string $errorMessage]
 */
function validate_sa_id_and_dob($idNumber, $dob) {
    $cleanId = preg_replace('/\D/', '', (string)$idNumber);
    if (strlen($cleanId) !== 13) {
        return [false, "National ID number must be exactly 13 digits."];
    }

    $time = strtotime($dob);
    if (!$time) {
        return [false, "A valid Date of Birth is required."];
    }

    $expectedYymmdd = date('ymd', $time);
    $actualYymmdd = substr($cleanId, 0, 6);

    if ($actualYymmdd !== $expectedYymmdd) {
        $formattedDob = date('Y-m-d', $time);
        return [false, "ID Number first 6 digits ({$actualYymmdd}) must match Date of Birth {$formattedDob} ({$expectedYymmdd})."];
    }

    return [true, ''];
}

/**
 * Generates or retrieves a CSRF token stored in the session.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden input field containing the CSRF token.
 *
 * @return void
 */
function csrf_token_field() {
    echo '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

/**
 * Validates a submitted CSRF token against the session token.
 * On failure, sets an error message and redirects to the referrer.
 *
 * @param string|null $redirectUrl Optional URL to redirect to on failure.
 * @return bool True if valid.
 */
function validate_csrf_token($redirectUrl = null) {
    $submitted = $_POST['csrf_token'] ?? '';
    if (empty($submitted) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        $_SESSION['error_message'] = 'Invalid or expired form submission. Please try again.';
        if ($redirectUrl) {
            redirect($redirectUrl);
        }
        return false;
    }
    // Regenerate token after successful validation to prevent reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

/**
 * Fetches all student records from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of all student records.
 */
function get_all_students($pdo) {
    try {
        $sql = "SELECT * FROM students ORDER BY full_name ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("ERROR: Could not execute query. " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches a single student's record by their ID.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $id The ID of the student to fetch.
 * @return mixed The student record as an associative array, or false if not found.
 */
function get_student_by_id($pdo, $id) {
    try {
        $sql = "SELECT * FROM students WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        die("ERROR: Could not fetch student data. " . $e->getMessage());
    }
}

/**
 * Logs a message to a specified file.
 *
 * @param string $log_message The message to be logged.
 * @param string $log_file The path to the log file.
 * @return void
 */
function log_action($log_message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] - " . $log_message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Displays a session-based message and then clears it.
 *
 * @param string $key The session key for the message (e.g., 'success_message').
 * @param string $alert_class The CSS class for styling the message (e.g., 'green', 'red').
 * @return void
 */
function display_session_message($key, $alert_class = 'green') {
    if (isset($_SESSION[$key])) {
        echo '<div class="card-panel ' . $alert_class . ' white-text" style="margin-top: 20px;">' . htmlspecialchars($_SESSION[$key]) . '</div>';
        unset($_SESSION[$key]);
    }
}

// -----------------------------------------------------------------------------
// Auto-loader for Domain Classes
// -----------------------------------------------------------------------------
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Fetches a student by their unique student number / student ID number.
 */
function get_student_by_student_number($pdo, $student_number) {
    try {
        $sql = "SELECT * FROM students WHERE student_number = :num OR student_id_number = :num2 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num' => $student_number, ':num2' => $student_number]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Fetches all courses with module count.
 */
function get_all_courses($pdo) {
    try {
        $sql = "
            SELECT c.*, COUNT(cm.module_id) AS module_count 
            FROM courses c 
            LEFT JOIN course_modules cm ON cm.course_id = c.course_id 
            GROUP BY c.course_id 
            ORDER BY c.course_name ASC
        ";
        return $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetches all modules with course and lecturer count.
 */
function get_all_modules($pdo) {
    try {
        return Module::getAll($pdo);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetches all lecturers with teaching module count.
 */
function get_all_lecturers($pdo) {
    try {
        return Lecturer::getAll($pdo);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetches quarterly module results for a student.
 */
function get_student_results($pdo, $student_id, $academic_year = null) {
    try {
        return ModuleResult::getResultsForStudent($pdo, $student_id, $academic_year);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetches active course enrollment for a student.
 */
function get_student_active_enrollment($pdo, $student_id) {
    try {
        return Enrollment::getActiveForStudent($pdo, $student_id);
    } catch (PDOException $e) {
        return null;
    }
}
?>
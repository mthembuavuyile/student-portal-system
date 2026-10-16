<?php
// This is a handler script, it does not output HTML.
// It requires a POST request with the student's ID to proceed.

require_once '../config.php';
require_once '../functions.php';

// Enforce admin-only access
require_login(['admin']);

// Define the log file
$log_file = 'deleted_logs.txt';

// Check if it's a POST request and if 'id' is set.
// Using POST prevents deletion via a simple link click (CSRF protection).
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error_message'] = "Invalid delete request.";
    redirect('student_list.php');
}

$student_id = intval($_POST['id']);

// 1. Fetch the student's data BEFORE deleting, for logging purposes.
$student_to_delete = get_student_by_id($pdo, $student_id);

if ($student_to_delete) {
    try {
        // 2. Log the details of the record to be deleted.
        $log_message = "DELETED STUDENT RECORD | Admin User: {$_SESSION['username']} | Student ID: {$student_to_delete['student_id_number']} | Name: {$student_to_delete['full_name']} | Email: {$student_to_delete['email']}";
        log_action($log_message, $log_file);

        // 3. Prepare and execute the DELETE statement.
        $sql = "DELETE FROM students WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the deletion was successful
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Student record for '{$student_to_delete['full_name']}' was successfully deleted.";
        } else {
            $_SESSION['error_message'] = "Error: Could not delete the record. It may have already been removed.";
        }

    } catch (PDOException $e) {
        // Log the database error and inform the user
        log_action("DATABASE ERROR during deletion: " . $e->getMessage(), $log_file);
        $_SESSION['error_message'] = "A database error occurred during deletion. Please check the logs.";
    }
} else {
    // If student wasn't found in the first place
    $_SESSION['error_message'] = "Cannot delete: No student found with the specified ID.";
}

// 4. Redirect back to the student list page.
redirect('admin/student_list.php');

?>
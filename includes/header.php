<?php
// This header should be included at the top of every secure page.
// It ensures config is loaded and a login is required.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Default allowed roles can be overridden by the page including this file.
if (!isset($allowed_roles)) {
    $allowed_roles = ['admin', 'student']; // Default allows both
}
require_login($allowed_roles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - SMS' : 'Student Management System'; ?></title>
    
    <!-- Materialize CSS and Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/dashboard.css">
</head>
<body>
    <header>
        <?php
        // Include the correct navigation bar based on the user's role
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                include 'nav_admin.php';
            } else {
                include 'nav_student.php';
            }
        }
        ?>
    </header>
    <main class="container">
        <!-- Page content starts here -->
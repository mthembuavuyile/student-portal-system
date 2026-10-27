<?php
// This header should be included at the top of every secure page.
// It ensures config is loaded and a login is required.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Default allowed roles can be overridden by the page including this file.
if (!isset($allowed_roles)) {
    $allowed_roles = ['admin', 'student', 'lecturer']; // Default allows all authenticated roles
}
require_login($allowed_roles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1565c0">
    <meta name="description" content="Student Management System - Department of Forestry, Fisheries & the Environment">
    <title><?php echo isset($page_title) ? $page_title . ' - SMS' : 'Student Management System'; ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Materialize CSS and Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/material-design.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/forms.css">
    <?php foreach ($page_stylesheets ?? [] as $stylesheet): ?>
        <?php $stylesheetUrl = strpos($stylesheet, '://') === false ? BASE_URL . ltrim($stylesheet, '/') : $stylesheet; ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheetUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endforeach; ?>
</head>
<body>
    <header>
        <?php
        // Include the correct navigation bar based on the user's role
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                include __DIR__ . '/nav_admin.php';
            } elseif ($_SESSION['role'] === 'lecturer') {
                include __DIR__ . '/nav_lecturer.php';
            } else {
                include __DIR__ . '/nav_student.php';
            }
        }
        ?>
    </header>
    <main class="container">
        <!-- Page content starts here -->
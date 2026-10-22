<?php
/**
 * Admin Results Page - Restricted Access
 * 
 * Business Rule: Quarterly marks entry and academic results management 
 * are restricted exclusively to Academic Lecturers.
 */
require_once '../config.php';
require_once '../functions.php';
require_login(['admin']);

$_SESSION['error_message'] = "Access Restricted: Academic marks capture and grading are managed exclusively by assigned Lecturers.";
redirect('admin/dashboard.php');
exit();

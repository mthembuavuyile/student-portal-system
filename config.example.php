<?php
/*
 * Database Configuration & Core Settings (Template)
 * Instructions: Copy this file to config.php and update your credentials.
 */

// -- Error Reporting --
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// -- Database Credentials --
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'your_db_password_here');
define('DB_NAME', 'university_db');

// -- Application URL --
$fixed_base = '';

if (!empty($fixed_base)) {
    define('BASE_URL', rtrim($fixed_base, '/') . '/');
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/') : '';
    $app_root = rtrim(str_replace('\\', '/', realpath(__DIR__)), '/');

    if (!empty($doc_root) && strpos($app_root, $doc_root) === 0) {
        $relative_path = substr($app_root, strlen($doc_root));
    } else {
        $relative_path = '/' . basename($app_root);
    }

    $clean_path = trim($relative_path, '/');
    define('BASE_URL', $protocol . $host . ($clean_path !== '' ? '/' . $clean_path : '') . '/');
}

// -- Constants --
define('STATUS_ACTIVE', 'Active');
define('STATUS_PENDING', 'Pending');
define('STATUS_GRADUATED', 'Graduated');
define('STATUS_DROPPED', 'Dropped');

// -- Data Source Name (DSN) for PDO --
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// -- Establish Database Connection --
try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
} catch (PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}

// -- Start Session --
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

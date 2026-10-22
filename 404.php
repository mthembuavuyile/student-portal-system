<?php
/**
 * 404 Error Page - Student Management System
 * Department of Forestry, Fisheries & the Environment (DFFE)
 *
 * Material Design compliant 404 error page matching the main application design system.
 */

// 1. Emit official HTTP 404 status header
if (!headers_sent()) {
    http_response_code(404);
}

// 2. Load core configuration and helper functions if available
$app_dir = __DIR__;
if (file_exists($app_dir . '/config.php')) {
    require_once $app_dir . '/config.php';
}
if (file_exists($app_dir . '/functions.php')) {
    require_once $app_dir . '/functions.php';
}

// 3. Resilient BASE_URL calculation
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/') : '';
    $current_dir = rtrim(str_replace('\\', '/', realpath($app_dir)), '/');

    if (!empty($doc_root) && strpos($current_dir, $doc_root) === 0) {
        $rel = substr($current_dir, strlen($doc_root));
    } else {
        $rel = '/' . basename($current_dir);
    }
    $clean = trim($rel, '/');
    define('BASE_URL', $protocol . $host . ($clean !== '' ? '/' . $clean : '') . '/');
}

// 4. Safe session inspection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['username'] ?? null;
$is_logged_in = !empty($_SESSION['user_id']);

// Determine primary recovery destination based on role
$primary_url = BASE_URL . 'index.php';
$primary_label = 'Return to Login';
$primary_icon = 'home';
$role_badge = 'Guest Visitor';
$role_color = 'grey';

if ($is_logged_in) {
    if ($user_role === 'admin') {
        $primary_url = BASE_URL . 'admin/dashboard.php';
        $primary_label = 'Return to Admin Dashboard';
        $primary_icon = 'dashboard';
        $role_badge = 'Administrator (' . htmlspecialchars($user_name ?? 'Admin') . ')';
        $role_color = 'blue darken-2';
    } elseif ($user_role === 'lecturer') {
        $primary_url = BASE_URL . 'lecturer/dashboard.php';
        $primary_label = 'Return to Lecturer Dashboard';
        $primary_icon = 'school';
        $role_badge = 'Lecturer (' . htmlspecialchars($user_name ?? 'Faculty') . ')';
        $role_color = 'teal darken-1';
    } elseif ($user_role === 'student') {
        $primary_url = BASE_URL . 'student/dashboard.php';
        $primary_label = 'Return to Student Dashboard';
        $primary_icon = 'account_circle';
        $role_badge = 'Student (' . htmlspecialchars($user_name ?? 'Student') . ')';
        $role_color = 'green darken-1';
    }
}

// Requested URI and environment diagnostics
$requested_uri = $_SERVER['REQUEST_URI'] ?? '/unknown';
$safe_uri = htmlspecialchars($requested_uri, ENT_QUOTES, 'UTF-8');
$referrer_uri = $_SERVER['HTTP_REFERER'] ?? '';
$safe_referrer = htmlspecialchars($referrer_uri, ENT_QUOTES, 'UTF-8');
$server_time = date('Y-m-d H:i:s');

// Directory of popular destinations for quick navigation
$portal_routes = [
    [
        'title' => 'Portal Login / Home',
        'url' => BASE_URL . 'index.php',
        'icon' => 'home',
        'category' => 'General',
        'desc' => 'Main login portal with role-based authentication',
        'role' => 'all'
    ],
    [
        'title' => 'Project Showcase & Overview',
        'url' => BASE_URL . 'presentation.php',
        'icon' => 'slideshow',
        'category' => 'System',
        'desc' => 'Technical architecture, normalized schema, and features',
        'role' => 'all'
    ],
    [
        'title' => 'Change / Reset Password',
        'url' => BASE_URL . 'change_password.php',
        'icon' => 'lock',
        'category' => 'Account',
        'desc' => 'Update your portal login credentials',
        'role' => 'all'
    ],
    // Student routes
    [
        'title' => 'Student Dashboard',
        'url' => BASE_URL . 'student/dashboard.php',
        'icon' => 'dashboard',
        'category' => 'Student Portal',
        'desc' => 'Academic status, enrolled course, and recent performance',
        'role' => 'student'
    ],
    [
        'title' => 'Academic Transcript & Marks',
        'url' => BASE_URL . 'student/results.php',
        'icon' => 'assessment',
        'category' => 'Student Portal',
        'desc' => 'Quarterly grades (Q1-Q4), GPA metrics & AI Success Coach',
        'role' => 'student'
    ],
    [
        'title' => 'My Enrolled Modules',
        'url' => BASE_URL . 'student/modules.php',
        'icon' => 'book',
        'category' => 'Student Portal',
        'desc' => 'Active course modules and assigned lecturers',
        'role' => 'student'
    ],
    [
        'title' => 'Student Profile',
        'url' => BASE_URL . 'student/profile.php',
        'icon' => 'person',
        'category' => 'Student Portal',
        'desc' => 'Personal identification, address, and contact information',
        'role' => 'student'
    ],
    // Lecturer routes
    [
        'title' => 'Lecturer Dashboard',
        'url' => BASE_URL . 'lecturer/dashboard.php',
        'icon' => 'dashboard',
        'category' => 'Lecturer Portal',
        'desc' => 'Assigned classes, student count, and grading metrics',
        'role' => 'lecturer'
    ],
    [
        'title' => 'Module Class Rosters',
        'url' => BASE_URL . 'lecturer/modules.php',
        'icon' => 'groups',
        'category' => 'Lecturer Portal',
        'desc' => 'View student enrolled rosters per assigned module',
        'role' => 'lecturer'
    ],
    [
        'title' => 'Quarterly Grade Capture',
        'url' => BASE_URL . 'lecturer/results.php',
        'icon' => 'edit_note',
        'category' => 'Lecturer Portal',
        'desc' => 'Record Q1-Q4 marks with automated average calculation',
        'role' => 'lecturer'
    ],
    // Admin routes
    [
        'title' => 'Admin Dashboard',
        'url' => BASE_URL . 'admin/dashboard.php',
        'icon' => 'admin_panel_settings',
        'category' => 'Administration',
        'desc' => 'System-wide metrics, active counts, and management hub',
        'role' => 'admin'
    ],
    [
        'title' => 'Student Directory',
        'url' => BASE_URL . 'admin/student_list.php',
        'icon' => 'people',
        'category' => 'Administration',
        'desc' => 'Browse, filter, and manage registered student records',
        'role' => 'admin'
    ],
    [
        'title' => 'Register New Student',
        'url' => BASE_URL . 'admin/register_student.php',
        'icon' => 'person_add',
        'category' => 'Administration',
        'desc' => 'Automated student numbering and enrollment creation',
        'role' => 'admin'
    ],
    [
        'title' => 'Courses & Qualifications',
        'url' => BASE_URL . 'admin/courses.php',
        'icon' => 'school',
        'category' => 'Administration',
        'desc' => 'Manage academic qualifications, codes, and duration',
        'role' => 'admin'
    ],
    [
        'title' => 'Curriculum Modules',
        'url' => BASE_URL . 'admin/modules.php',
        'icon' => 'book',
        'category' => 'Administration',
        'desc' => 'Manage course modules and lecturer allocations',
        'role' => 'admin'
    ],
    [
        'title' => 'Faculty & Lecturers',
        'url' => BASE_URL . 'admin/lecturers.php',
        'icon' => 'supervisor_account',
        'category' => 'Administration',
        'desc' => 'Directory of academic faculty members and contact data',
        'role' => 'admin'
    ]
];

// Filter routes for display: relevant to current role or all
$filtered_routes = array_filter($portal_routes, function($r) use ($user_role, $is_logged_in) {
    if ($r['role'] === 'all') return true;
    if ($is_logged_in && $r['role'] === $user_role) return true;
    if (!$is_logged_in && $r['role'] === 'all') return true;
    return false;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1565c0">
    <title>404 - Page Not Found | Student Management System</title>
    
    <!-- Google Fonts: Inter & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Materialize CSS & App Custom Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/material-design.css">

    <style>
        .error-hero-card {
            margin-top: 30px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #1565c0;
        }
        .error-icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #e3f2fd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .error-icon-circle i {
            font-size: 46px;
            color: #1565c0;
        }
        .error-code-badge {
            display: inline-block;
            background-color: #ef5350;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 3px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .url-callout {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #1565c0;
            padding: 10px 14px;
            border-radius: 6px;
            margin: 15px 0 20px 0;
            font-size: 0.92rem;
            word-break: break-all;
            text-align: left;
        }
        .search-box-wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 4px 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .route-card {
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .route-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
        }
        .route-card .card-content {
            padding: 16px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="blue darken-3" role="navigation">
            <div class="nav-wrapper container">
                <a href="<?php echo $primary_url; ?>" class="brand-logo" style="font-size: 1.25rem; display: flex; align-items: center;">
                    <i class="material-icons left">account_balance</i>
                    <span class="hide-on-small-only">Student Management System</span>
                    <span class="hide-on-med-and-up">SMS Portal</span>
                </a>
                <ul class="right">
                    <?php if ($is_logged_in): ?>
                        <li>
                            <a href="<?php echo $primary_url; ?>" class="waves-effect">
                                <i class="material-icons left"><?php echo $primary_icon; ?></i>
                                <span class="hide-on-small-only">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="waves-effect">
                                <i class="material-icons left">exit_to_app</i>
                                <span class="hide-on-small-only">Logout</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>index.php" class="waves-effect">
                                <i class="material-icons left">login</i>
                                <span>Portal Login</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container" style="flex: 1 0 auto; padding-top: 10px; padding-bottom: 40px;">
        <!-- Hero 404 Notification Card -->
        <div class="row">
            <div class="col s12 m10 offset-m1 l8 offset-l2">
                <div class="card error-hero-card center-align">
                    <div class="card-content" style="padding: 35px 25px;">
                        <div class="error-icon-circle">
                            <i class="material-icons">sentiment_dissatisfied</i>
                        </div>
                        <div>
                            <span class="error-code-badge">HTTP 404 NOT FOUND</span>
                        </div>
                        <h4 style="margin: 5px 0 10px 0; font-weight: 700; color: #1e293b;">
                            Page Not Found
                        </h4>
                        <p class="grey-text text-darken-1" style="font-size: 1.05rem; max-width: 540px; margin: 0 auto;">
                            The page or resource you requested does not exist on this server, has been relocated, or the address was typed incorrectly.
                        </p>

                        <div class="url-callout">
                            <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 2px;">
                                Requested Address:
                            </div>
                            <strong class="blue-text text-darken-3"><?php echo $safe_uri; ?></strong>
                        </div>

                        <!-- Recovery Action Buttons -->
                        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 25px;">
                            <a href="<?php echo $primary_url; ?>" class="btn blue darken-2 waves-effect waves-light">
                                <i class="material-icons left"><?php echo $primary_icon; ?></i>
                                <?php echo $primary_label; ?>
                            </a>
                            <a href="javascript:history.back()" class="btn-flat grey lighten-3 grey-text text-darken-3 waves-effect">
                                <i class="material-icons left">arrow_back</i>
                                Go Back
                            </a>
                            <a href="<?php echo BASE_URL; ?>presentation.php" class="btn-flat blue-text text-darken-2 waves-effect">
                                <i class="material-icons left">slideshow</i>
                                Project Showcase
                            </a>
                        </div>

                        <?php if ($is_logged_in): ?>
                            <div style="margin-top: 20px;">
                                <span class="chip <?php echo $role_color; ?> white-text">
                                    <i class="material-icons tiny left" style="line-height: inherit;">verified_user</i>
                                    Active Session: <?php echo $role_badge; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation Directory Section -->
        <div class="row" style="margin-top: 20px;">
            <div class="col s12 m10 offset-m1 l10 offset-l1">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h5 style="margin: 0; font-weight: 600; color: #1e293b;">
                            <i class="material-icons left blue-text text-darken-2" style="vertical-align: middle;">explore</i>
                            Quick Navigation Directory
                        </h5>
                        <p class="grey-text" style="margin: 2px 0 0 0; font-size: 0.9rem;">
                            Jump directly to verified sections in the Student Management System
                        </p>
                    </div>
                </div>

                <!-- Live Search Filter Field -->
                <div class="search-box-wrapper">
                    <div class="input-field" style="margin: 0;">
                        <i class="material-icons prefix grey-text">search</i>
                        <input type="text" id="routeFilter" placeholder="Filter pages (e.g. results, modules, dashboard, students)..." style="border-bottom: none; margin-bottom: 0;">
                    </div>
                </div>

                <!-- Routes Grid -->
                <div class="row" id="routesContainer">
                    <?php foreach ($filtered_routes as $route): ?>
                        <div class="col s12 m6 l4 route-item" data-title="<?php echo strtolower($route['title'] . ' ' . $route['category'] . ' ' . $route['desc']); ?>" style="margin-bottom: 16px;">
                            <div class="card route-card hoverable">
                                <div class="card-content">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <div style="width: 38px; height: 38px; border-radius: 8px; background-color: #e3f2fd; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <i class="material-icons blue-text text-darken-2" style="font-size: 20px;"><?php echo $route['icon']; ?></i>
                                        </div>
                                        <div>
                                            <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">
                                                <?php echo htmlspecialchars($route['category']); ?>
                                            </span>
                                            <h6 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b; line-height: 1.25;">
                                                <?php echo htmlspecialchars($route['title']); ?>
                                            </h6>
                                        </div>
                                    </div>
                                    <p class="grey-text text-darken-1" style="font-size: 0.82rem; margin-bottom: 12px; min-height: 38px;">
                                        <?php echo htmlspecialchars($route['desc']); ?>
                                    </p>
                                    <div class="divider" style="margin-bottom: 10px;"></div>
                                    <a href="<?php echo $route['url']; ?>" class="blue-text text-darken-2" style="font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                        Open Page <i class="material-icons tiny">arrow_forward</i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="noResultsMessage" class="center-align grey-text" style="display: none; padding: 30px;">
                    <i class="material-icons medium grey-text text-lighten-1">search_off</i>
                    <p>No matching portal pages found for your search query.</p>
                </div>

                <!-- Collapsible Diagnostic Details -->
                <ul class="collapsible z-depth-0" style="border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 25px; background-color: #ffffff;">
                    <li>
                        <div class="collapsible-header grey-text text-darken-2" style="display: flex; align-items: center; justify-content: space-between; font-size: 0.9rem;">
                            <span><i class="material-icons left grey-text">info_outline</i> Technical Diagnostics &amp; Request Info</span>
                            <i class="material-icons">expand_more</i>
                        </div>
                        <div class="collapsible-body" style="padding: 15px 20px; background-color: #f8fafc; font-size: 0.85rem;">
                            <table class="striped" style="margin: 0;">
                                <tbody>
                                    <tr>
                                        <td style="font-weight: 600; width: 180px; color: #475569;">HTTP Response Code:</td>
                                        <td><code>404 Not Found</code></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #475569;">Requested URI:</td>
                                        <td><code><?php echo $safe_uri; ?></code></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #475569;">HTTP Referrer:</td>
                                        <td><?php echo $safe_referrer !== '' ? '<code>' . $safe_referrer . '</code>' : '<span class="grey-text">None (Direct or bookmark access)</span>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #475569;">Server Timestamp:</td>
                                        <td><?php echo $server_time; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #475569;">Authentication Status:</td>
                                        <td><?php echo $is_logged_in ? 'Logged In (' . htmlspecialchars($user_role ?? 'user') . ')' : 'Guest (Unauthenticated)'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 600; color: #475569;">Base URL:</td>
                                        <td><code><?php echo htmlspecialchars(BASE_URL); ?></code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <footer class="page-footer blue darken-3">
        <div class="footer-copyright">
            <div class="container">
                &copy; <?php echo date("Y"); ?> Student Management System &bull; Department of Forestry, Fisheries &amp; the Environment
                <a class="grey-text text-lighten-4 right" href="<?php echo BASE_URL; ?>index.php">Portal Login</a>
            </div>
        </div>
    </footer>

    <!-- Materialize JS & Passive Listeners -->
    <script src="<?php echo BASE_URL; ?>js/passive-listeners.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Materialize collapsibles
            var collapsibles = document.querySelectorAll('.collapsible');
            M.Collapsible.init(collapsibles);

            // Real-time quick search filter
            var filterInput = document.getElementById('routeFilter');
            var items = document.querySelectorAll('.route-item');
            var noResults = document.getElementById('noResultsMessage');

            if (filterInput) {
                filterInput.addEventListener('input', function() {
                    var query = this.value.trim().toLowerCase();
                    var matchCount = 0;

                    items.forEach(function(item) {
                        var text = item.getAttribute('data-title') || '';
                        if (query === '' || text.indexOf(query) !== -1) {
                            item.style.display = '';
                            matchCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (noResults) {
                        noResults.style.display = (matchCount === 0) ? 'block' : 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>

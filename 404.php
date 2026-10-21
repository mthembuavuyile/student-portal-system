<?php
/**
 * 404 Error Page - Student Management System
 * Department of Forestry, Fisheries & the Environment (DFFE)
 *
 * Provides a responsive, role-aware, and interactive 404 experience
 * with live search navigation, diagnostic details, and smooth recovery actions.
 */

// 1. Emit official HTTP 404 status header
if (!headers_sent()) {
    http_response_code(404);
}

// 2. Resilient configuration loading
$app_dir = __DIR__;
if (file_exists($app_dir . '/config.php')) {
    require_once $app_dir . '/config.php';
}
if (file_exists($app_dir . '/functions.php')) {
    require_once $app_dir . '/functions.php';
}

// 3. Ensure BASE_URL is reliably set
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

// 4. Session inspection (safe check without requiring login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['username'] ?? null;
$is_logged_in = !empty($_SESSION['user_id']);

// Determine optimal recovery URL based on active session role
$home_url = BASE_URL . 'index.php';
$home_label = 'Return to Portal Login';
$home_icon = 'login';
$role_badge = 'Guest Visitor';
$role_badge_class = 'badge-guest';

if ($is_logged_in) {
    if ($user_role === 'admin') {
        $home_url = BASE_URL . 'admin/dashboard.php';
        $home_label = 'Return to Admin Dashboard';
        $home_icon = 'admin_panel_settings';
        $role_badge = 'Administrator: ' . htmlspecialchars($user_name ?? 'Admin');
        $role_badge_class = 'badge-admin';
    } elseif ($user_role === 'lecturer') {
        $home_url = BASE_URL . 'lecturer/dashboard.php';
        $home_label = 'Return to Lecturer Dashboard';
        $home_icon = 'school';
        $role_badge = 'Lecturer: ' . htmlspecialchars($user_name ?? 'Faculty');
        $role_badge_class = 'badge-lecturer';
    } elseif ($user_role === 'student') {
        $home_url = BASE_URL . 'student/dashboard.php';
        $home_label = 'Return to Student Dashboard';
        $home_icon = 'person';
        $role_badge = 'Student: ' . htmlspecialchars($user_name ?? 'Student');
        $role_badge_class = 'badge-student';
    }
}

// Requested URI sanitization
$requested_uri = $_SERVER['REQUEST_URI'] ?? '/unknown';
$safe_uri = htmlspecialchars($requested_uri, ENT_QUOTES, 'UTF-8');
$referrer_uri = $_SERVER['HTTP_REFERER'] ?? '';
$safe_referrer = htmlspecialchars($referrer_uri, ENT_QUOTES, 'UTF-8');
$current_time_sast = date('Y-m-d H:i:s') . ' SAST';

// Directory of popular destinations for quick live search
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
        'title' => 'Executive Presentation System',
        'url' => BASE_URL . 'presentation.php',
        'icon' => 'slideshow',
        'category' => 'System',
        'desc' => 'Technical showcase, architecture, and defense slides',
        'role' => 'all'
    ],
    [
        'title' => 'Change / Reset Password',
        'url' => BASE_URL . 'change_password.php',
        'icon' => 'lock_reset',
        'category' => 'Security',
        'desc' => 'Update or recover credentials securely',
        'role' => 'all'
    ],
    [
        'title' => 'Student Dashboard',
        'url' => BASE_URL . 'student/dashboard.php',
        'icon' => 'dashboard',
        'category' => 'Student Portal',
        'desc' => 'Overview of enrolled courses, academic status and averages',
        'role' => 'student'
    ],
    [
        'title' => 'Academic Transcript & Results',
        'url' => BASE_URL . 'student/results.php',
        'icon' => 'assessment',
        'category' => 'Student Portal',
        'desc' => 'Quarterly marks (Q1-Q4), GPA metrics & AI Success Coach',
        'role' => 'student'
    ],
    [
        'title' => 'Enrolled Modules & Lecturers',
        'url' => BASE_URL . 'student/modules.php',
        'icon' => 'view_module',
        'category' => 'Student Portal',
        'desc' => 'View active modules and assigned faculty details',
        'role' => 'student'
    ],
    [
        'title' => 'Student Profile & Details',
        'url' => BASE_URL . 'student/profile.php',
        'icon' => 'account_circle',
        'category' => 'Student Portal',
        'desc' => 'Personal identification, address, and contact records',
        'role' => 'student'
    ],
    [
        'title' => 'Lecturer Dashboard',
        'url' => BASE_URL . 'lecturer/dashboard.php',
        'icon' => 'dashboard',
        'category' => 'Lecturer Portal',
        'desc' => 'Assigned classes, student count, and grading metrics',
        'role' => 'lecturer'
    ],
    [
        'title' => 'Module Rosters',
        'url' => BASE_URL . 'lecturer/modules.php',
        'icon' => 'groups',
        'category' => 'Lecturer Portal',
        'desc' => 'View class lists and student profiles per module',
        'role' => 'lecturer'
    ],
    [
        'title' => 'Grade & Marks Capture',
        'url' => BASE_URL . 'lecturer/results.php',
        'icon' => 'edit_note',
        'category' => 'Lecturer Portal',
        'desc' => 'Record quarterly marks with instant live average calculation',
        'role' => 'lecturer'
    ],
    [
        'title' => 'Admin Dashboard',
        'url' => BASE_URL . 'admin/dashboard.php',
        'icon' => 'admin_panel_settings',
        'category' => 'Administration',
        'desc' => 'System-wide statistics, active counts, and management hub',
        'role' => 'admin'
    ],
    [
        'title' => 'Student Directory',
        'url' => BASE_URL . 'admin/student_list.php',
        'icon' => 'badge',
        'category' => 'Administration',
        'desc' => 'Browse, filter, and search student records in real time',
        'role' => 'admin'
    ],
    [
        'title' => 'Register New Student',
        'url' => BASE_URL . 'admin/register_student.php',
        'icon' => 'person_add',
        'category' => 'Administration',
        'desc' => 'Automatic student number generator & enrollment creation',
        'role' => 'admin'
    ],
    [
        'title' => 'Curriculum & Courses',
        'url' => BASE_URL . 'admin/courses.php',
        'icon' => 'menu_book',
        'category' => 'Administration',
        'desc' => 'Manage academic qualifications, codes, and durations',
        'role' => 'admin'
    ],
    [
        'title' => 'Academic Modules',
        'url' => BASE_URL . 'admin/modules.php',
        'icon' => 'category',
        'category' => 'Administration',
        'desc' => 'Manage course modules and lecturer assignments',
        'role' => 'admin'
    ],
    [
        'title' => 'Faculty & Lecturers',
        'url' => BASE_URL . 'admin/lecturers.php',
        'icon' => 'co_present',
        'category' => 'Administration',
        'desc' => 'Directory of academic faculty members and contact data',
        'role' => 'admin'
    ]
];

// Filter routes for display: relevant to current role or all
$filtered_routes = array_filter($portal_routes, function($r) use ($user_role, $is_logged_in) {
    if ($r['role'] === 'all') return true;
    if ($is_logged_in && $r['role'] === $user_role) return true;
    if (!$is_logged_in && in_array($r['role'], ['all'])) return true;
    return false;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0a192f">
    <meta name="robots" content="noindex, follow">
    <title>404 - Page Not Found | Student Management System</title>
    
    <!-- Google Fonts: Inter & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        /* ==================================================
           CSS Custom Properties & Design Tokens
           ================================================== */
        :root {
            --bg-deep: #070d18;
            --bg-navy: #0b1528;
            --bg-card: rgba(15, 27, 49, 0.75);
            --bg-glass: rgba(255, 255, 255, 0.05);
            --border-glass: rgba(255, 255, 255, 0.12);
            --border-hover: rgba(56, 189, 248, 0.4);
            --primary-blue: #0284c7;
            --primary-light: #38bdf8;
            --accent-cyan: #00e5ff;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --accent-emerald: #10b981;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-full: 9999px;
            --shadow-subtle: 0 4px 20px rgba(0, 0, 0, 0.25);
            --shadow-glowing: 0 20px 50px -10px rgba(2, 132, 199, 0.35);
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', Consolas, Monaco, monospace;
        }

        /* Reset & Layout */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            min-height: 100vh;
            font-family: var(--font-sans);
            background-color: var(--bg-deep);
            color: var(--text-primary);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background: 
                radial-gradient(circle at 15% 20%, rgba(2, 132, 199, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 85% 30%, rgba(99, 102, 241, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 50% 85%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
                linear-gradient(180deg, #070d18 0%, #0a1424 50%, #060a12 100%);
            background-attachment: fixed;
        }

        /* Ambient floating lights */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.6;
            animation: orbFloat 18s ease-in-out infinite alternate;
        }

        .ambient-orb-1 {
            width: 420px;
            height: 420px;
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.4), rgba(56, 189, 248, 0.1));
            top: -100px;
            left: -100px;
        }

        .ambient-orb-2 {
            width: 480px;
            height: 480px;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.3), rgba(236, 72, 153, 0.1));
            bottom: -120px;
            right: -100px;
            animation-duration: 22s;
            animation-delay: -5s;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, 60px) scale(1.08); }
            100% { transform: translate(-30px, 30px) scale(0.95); }
        }

        /* Subtle grid background pattern */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 1;
            mask-image: radial-gradient(circle at 50% 45%, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at 50% 45%, black 40%, transparent 80%);
        }

        /* Header Navigation Bar */
        .site-header {
            position: relative;
            z-index: 10;
            width: 100%;
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(7, 13, 24, 0.6);
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            padding: 4px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .brand-link:hover .brand-logo {
            transform: scale(1.08) rotate(-2deg);
        }

        .brand-text h1 {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-text span {
            font-size: 0.76rem;
            color: var(--text-secondary);
            font-weight: 500;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .session-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid var(--border-glass);
            background: var(--bg-glass);
            backdrop-filter: blur(8px);
            transition: all 0.25s ease;
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-emerald);
            box-shadow: 0 0 8px var(--accent-emerald);
            animation: pulseDot 2s infinite;
        }

        .badge-admin .badge-dot { background: #818cf8; box-shadow: 0 0 8px #818cf8; }
        .badge-lecturer .badge-dot { background: #38bdf8; box-shadow: 0 0 8px #38bdf8; }
        .badge-student .badge-dot { background: #34d399; box-shadow: 0 0 8px #34d399; }
        .badge-guest .badge-dot { background: #94a3b8; box-shadow: none; }

        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.6; }
        }

        /* Main Container */
        .main-wrapper {
            position: relative;
            z-index: 5;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .content-card {
            width: 100%;
            max-width: 920px;
            background: var(--bg-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-glass);
            box-shadow: 
                0 25px 70px -15px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
            padding: 48px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: cardEntrance 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Top card highlight border */
        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-light), var(--accent-cyan), transparent);
            border-radius: 2px;
        }

        /* Status Badge Pill */
        .status-badge-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fca5a5;
            padding: 6px 18px;
            border-radius: var(--radius-full);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 24px;
            box-shadow: 0 0 20px rgba(244, 63, 94, 0.15);
        }

        .status-badge-wrapper .beacon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-rose);
            box-shadow: 0 0 10px var(--accent-rose);
            animation: beaconGlow 1.8s infinite;
        }

        @keyframes beaconGlow {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.4; }
        }

        /* Giant 404 Visual Art */
        .error-artwork {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 10px auto 20px;
            user-select: none;
        }

        .digit {
            font-size: clamp(5.5rem, 14vw, 9.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.05em;
            font-family: var(--font-sans);
            background: linear-gradient(135deg, #ffffff 20%, #93c5fd 60%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 15px 35px rgba(2, 132, 199, 0.3);
            position: relative;
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .digit:hover {
            transform: translateY(-4px) scale(1.02);
        }

        /* Center Orb Radar */
        .radar-center {
            position: relative;
            width: clamp(75px, 12vw, 115px);
            height: clamp(75px, 12vw, 115px);
            border-radius: 50%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, rgba(2, 132, 199, 0.05) 70%, transparent 100%);
            border: 2px dashed rgba(56, 189, 248, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 8px;
            animation: radarRotate 20s linear infinite;
            box-shadow: 
                0 0 30px rgba(56, 189, 248, 0.25),
                inset 0 0 20px rgba(56, 189, 248, 0.2);
        }

        @keyframes radarRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .radar-inner-icon {
            animation: radarCounterRotate 20s linear infinite;
            color: var(--accent-cyan);
            font-size: clamp(36px, 6vw, 54px);
            filter: drop-shadow(0 0 12px rgba(0, 229, 255, 0.7));
        }

        @keyframes radarCounterRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        /* Typography */
        .error-title {
            font-size: clamp(1.4rem, 3.5vw, 2.2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin-bottom: 12px;
            line-height: 1.25;
        }

        .error-description {
            font-size: clamp(0.92rem, 1.8vw, 1.05rem);
            color: var(--text-secondary);
            max-width: 620px;
            margin: 0 auto 26px;
            line-height: 1.6;
        }

        /* Requested Path Snippet */
        .path-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            padding: 8px 16px;
            max-width: 100%;
            margin-bottom: 32px;
            transition: border-color 0.25s ease;
        }

        .path-pill:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }

        .path-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .path-code {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: var(--primary-light);
            word-break: break-all;
            text-align: left;
        }

        .copy-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .copy-btn:hover {
            color: #ffffff;
            transform: scale(1.1);
        }

        .copy-btn i {
            font-size: 16px;
        }

        /* Action Buttons Grid */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 36px;
        }

        .btn-portal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 26px;
            font-size: 0.94rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
            border: 1px solid transparent;
        }

        .btn-portal i {
            font-size: 19px;
            transition: transform 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            box-shadow: 0 4px 18px rgba(2, 132, 199, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.5);
        }

        .btn-primary:hover i {
            transform: translateX(2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Search & Navigator Section */
        .navigator-section {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 32px;
            margin-top: 12px;
            text-align: left;
        }

        .navigator-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .navigator-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navigator-header h2 i {
            color: var(--primary-light);
            font-size: 20px;
        }

        .search-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 14px 44px 14px 44px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-md);
            color: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: all 0.25s ease;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
            background: rgba(0, 0, 0, 0.5);
        }

        .search-icon-prefix {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            font-size: 20px;
        }

        .search-clear-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
            font-size: 18px;
            padding: 2px;
            border-radius: 50%;
            transition: color 0.2s ease;
        }

        .search-clear-btn:hover {
            color: #ffffff;
        }

        /* Quick Destinations Grid */
        .routes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 6px;
        }

        /* Custom Scrollbar */
        .routes-grid::-webkit-scrollbar {
            width: 6px;
        }

        .routes-grid::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .routes-grid::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 3px;
        }

        .routes-grid::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        .route-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }

        .route-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-2px);
        }

        .route-card-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(2, 132, 199, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .route-card:hover .route-card-icon {
            background: var(--primary-blue);
            color: #ffffff;
        }

        .route-card-info {
            flex: 1;
            min-width: 0;
        }

        .route-card-title {
            font-size: 0.86rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .route-card-desc {
            font-size: 0.74rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .no-results-msg {
            grid-column: 1 / -1;
            padding: 24px 12px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.88rem;
            display: none;
        }

        /* Diagnostic Collapsible */
        .diagnostic-accordion {
            margin-top: 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 20px;
            text-align: left;
        }

        .diagnostic-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .diagnostic-trigger:hover {
            color: var(--primary-light);
        }

        .diagnostic-trigger i {
            font-size: 16px;
            transition: transform 0.25s ease;
        }

        .diagnostic-trigger.active i {
            transform: rotate(180deg);
        }

        .diagnostic-content {
            display: none;
            margin-top: 14px;
            padding: 16px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: var(--radius-sm);
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .diagnostic-content.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .diagnostic-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            padding: 4px 0;
            flex-wrap: wrap;
            gap: 8px;
        }

        .diagnostic-row:last-child {
            border-bottom: none;
        }

        .diag-key {
            color: var(--text-muted);
        }

        .diag-val {
            color: #e2e8f0;
            word-break: break-all;
        }

        /* Toast notification */
        .toast-bubble {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 100;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        .toast-bubble.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Footer */
        .site-footer {
            position: relative;
            z-index: 10;
            width: 100%;
            padding: 18px 32px;
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            background: rgba(7, 13, 24, 0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .site-footer a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .site-footer a:hover {
            color: var(--primary-light);
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .site-header {
                padding: 16px 20px;
            }
            .content-card {
                padding: 32px 20px;
            }
            .action-row {
                flex-direction: column;
                width: 100%;
            }
            .btn-portal {
                width: 100%;
            }
            .routes-grid {
                grid-template-columns: 1fr;
            }
            .site-footer {
                padding: 16px 20px;
                flex-direction: column;
                text-align: center;
            }
            .session-pill {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient background glow orbs -->
    <div class="ambient-orb ambient-orb-1" aria-hidden="true"></div>
    <div class="ambient-orb ambient-orb-2" aria-hidden="true"></div>
    <div class="grid-overlay" aria-hidden="true"></div>

    <!-- Header Navigation -->
    <header class="site-header">
        <a href="<?php echo $home_url; ?>" class="brand-link" title="Student Management System Portal">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="University Logo" class="brand-logo" onerror="this.style.display='none'">
            <div class="brand-text">
                <h1>Student Management System</h1>
                <span>Dept. of Forestry, Fisheries &amp; the Environment</span>
            </div>
        </a>

        <div class="session-pill <?php echo $role_badge_class; ?>" title="Active Portal Session Status">
            <span class="badge-dot"></span>
            <span><?php echo $role_badge; ?></span>
        </div>
    </header>

    <!-- Main 404 Hero Container -->
    <main class="main-wrapper" role="main">
        <div class="content-card">

            <!-- Pulsing Error Badge -->
            <div class="status-badge-wrapper">
                <span class="beacon" aria-hidden="true"></span>
                <span>Error 404 &bull; Resource Unreachable</span>
            </div>

            <!-- Stylized 404 Visual Centerpiece -->
            <div class="error-artwork" aria-label="404 Error Art">
                <span class="digit">4</span>
                <div class="radar-center" title="Campus Locator Radar Sweep">
                    <i class="material-icons radar-inner-icon">explore</i>
                </div>
                <span class="digit">4</span>
            </div>

            <!-- Primary Headline & Message -->
            <h1 class="error-title">Academic Record Not Located</h1>
            <p class="error-description">
                The requested URL could not be located in our university directory. The page may have been moved, renamed, archived, or you may have followed an expired link.
            </p>

            <!-- Requested URL Snippet with Copy Feature -->
            <div class="path-pill" title="Target URI that generated this 404 error">
                <span class="path-label">Target:</span>
                <span class="path-code" id="targetUri"><?php echo $safe_uri; ?></span>
                <button type="button" class="copy-btn" id="copyUriBtn" title="Copy missing path" aria-label="Copy requested URI">
                    <i class="material-icons">content_copy</i>
                </button>
            </div>

            <!-- Smart Action Buttons -->
            <div class="action-row">
                <a href="<?php echo $home_url; ?>" class="btn-portal btn-primary" id="primaryRecoveryBtn">
                    <i class="material-icons"><?php echo $home_icon; ?></i>
                    <span><?php echo $home_label; ?></span>
                </a>

                <button type="button" class="btn-portal btn-secondary" id="goBackBtn" onclick="smartGoBack()">
                    <i class="material-icons">arrow_back</i>
                    <span>Go Back to Previous</span>
                </button>

                <a href="<?php echo BASE_URL; ?>presentation.php" class="btn-portal btn-outline" title="View System Architecture & Slides">
                    <i class="material-icons">slideshow</i>
                    <span>System Presentation</span>
                </a>
            </div>

            <!-- Interactive Directory Navigator & Search -->
            <section class="navigator-section" aria-label="Directory Navigation Search">
                <div class="navigator-header">
                    <h2>
                        <i class="material-icons">travel_explore</i>
                        <span>Where would you like to go?</span>
                    </h2>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">
                        Filter across key portal services
                    </span>
                </div>

                <div class="search-container">
                    <i class="material-icons search-icon-prefix" aria-hidden="true">search</i>
                    <input 
                        type="text" 
                        class="search-input" 
                        id="portalSearchInput" 
                        placeholder="Search for pages, grades, modules, dashboard, login..." 
                        autocomplete="off"
                        aria-label="Search portal navigation destinations"
                    >
                    <button type="button" class="search-clear-btn" id="searchClearBtn" aria-label="Clear search">
                        <i class="material-icons">close</i>
                    </button>
                </div>

                <!-- Grid of Quick Links -->
                <div class="routes-grid" id="routesGrid">
                    <?php foreach ($filtered_routes as $route): ?>
                        <a href="<?php echo $route['url']; ?>" class="route-card" data-title="<?php echo strtolower($route['title'] . ' ' . $route['desc'] . ' ' . $route['category']); ?>">
                            <div class="route-card-icon">
                                <i class="material-icons"><?php echo $route['icon']; ?></i>
                            </div>
                            <div class="route-card-info">
                                <div class="route-card-title"><?php echo htmlspecialchars($route['title']); ?></div>
                                <div class="route-card-desc"><?php echo htmlspecialchars($route['desc']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <div class="no-results-msg" id="noResultsMsg">
                        No matching portal destination found. Try a different keyword or return to the main dashboard.
                    </div>
                </div>
            </section>

            <!-- Technical Diagnostics Accordion -->
            <div class="diagnostic-accordion">
                <button type="button" class="diagnostic-trigger" id="diagTrigger" aria-expanded="false">
                    <i class="material-icons">tune</i>
                    <span>Technical Diagnostics &amp; Telemetry</span>
                </button>

                <div class="diagnostic-content" id="diagContent">
                    <div class="diagnostic-row">
                        <span class="diag-key">HTTP Status:</span>
                        <span class="diag-val" style="color: var(--accent-rose); font-weight: 700;">404 Not Found</span>
                    </div>
                    <div class="diagnostic-row">
                        <span class="diag-key">Timestamp (SAST):</span>
                        <span class="diag-val"><?php echo $current_time_sast; ?></span>
                    </div>
                    <div class="diagnostic-row">
                        <span class="diag-key">Requested Route:</span>
                        <span class="diag-val"><?php echo $safe_uri; ?></span>
                    </div>
                    <?php if (!empty($safe_referrer)): ?>
                    <div class="diagnostic-row">
                        <span class="diag-key">HTTP Referrer:</span>
                        <span class="diag-val"><?php echo $safe_referrer; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="diagnostic-row">
                        <span class="diag-key">User Session:</span>
                        <span class="diag-val"><?php echo $is_logged_in ? 'Authenticated (' . htmlspecialchars($user_role) . ')' : 'Anonymous / Guest'; ?></span>
                    </div>
                    <div class="diagnostic-row">
                        <span class="diag-key">Server Protocol:</span>
                        <span class="diag-val"><?php echo htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'); ?></span>
                    </div>
                    <div class="diagnostic-row">
                        <span class="diag-key">Technical Support:</span>
                        <span class="diag-val">support@dffe-sms.ac.za &bull; DFFE ICT Directorate</span>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Toast message popup -->
    <div class="toast-bubble" id="toastBubble" role="status" aria-live="polite">
        <i class="material-icons" style="font-size: 18px; color: var(--accent-cyan);">check_circle</i>
        <span id="toastText">Path copied to clipboard</span>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div>
            &copy; <?php echo date("Y"); ?> Department of Forestry, Fisheries &amp; the Environment &bull; Student Management System
        </div>
        <div>
            <a href="<?php echo $home_url; ?>">Portal Home</a> &bull;
            <a href="<?php echo BASE_URL; ?>presentation.php">Architecture</a> &bull;
            <a href="mailto:support@dffe-sms.ac.za">IT Helpdesk</a>
        </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
        // 1. Smart History Go Back with fallback
        function smartGoBack() {
            if (window.history.length > 1 && document.referrer && document.referrer.indexOf(window.location.host) !== -1) {
                window.history.back();
            } else {
                window.location.href = <?php echo json_encode($home_url); ?>;
            }
        }

        // 2. Copy URI feature with toast notification
        const copyUriBtn = document.getElementById('copyUriBtn');
        const targetUri = document.getElementById('targetUri');
        const toast = document.getElementById('toastBubble');
        const toastText = document.getElementById('toastText');

        function showToast(message) {
            toastText.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        if (copyUriBtn && targetUri) {
            copyUriBtn.addEventListener('click', function() {
                const textToCopy = targetUri.innerText.trim();
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        showToast('URI copied to clipboard');
                    }).catch(() => {
                        fallbackCopy(textToCopy);
                    });
                } else {
                    fallbackCopy(textToCopy);
                }
            });
        }

        function fallbackCopy(text) {
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            showToast('URI copied to clipboard');
        }

        // 3. Live Navigator Filter & Instant Search
        const searchInput = document.getElementById('portalSearchInput');
        const searchClear = document.getElementById('searchClearBtn');
        const routeCards = document.querySelectorAll('.route-card');
        const noResultsMsg = document.getElementById('noResultsMsg');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                let matchCount = 0;

                if (query.length > 0) {
                    searchClear.style.display = 'block';
                } else {
                    searchClear.style.display = 'none';
                }

                routeCards.forEach(card => {
                    const dataText = card.getAttribute('data-title') || '';
                    if (dataText.includes(query)) {
                        card.style.display = 'flex';
                        matchCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (matchCount === 0) {
                    noResultsMsg.style.display = 'block';
                } else {
                    noResultsMsg.style.display = 'none';
                }
            });

            // Handle Enter key for single match navigation
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const visibleCards = Array.from(routeCards).filter(c => c.style.display !== 'none');
                    if (visibleCards.length > 0) {
                        e.preventDefault();
                        visibleCards[0].click();
                    }
                }
            });
        }

        if (searchClear && searchInput) {
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }

        // 4. Diagnostic Telemetry Accordion
        const diagTrigger = document.getElementById('diagTrigger');
        const diagContent = document.getElementById('diagContent');

        if (diagTrigger && diagContent) {
            diagTrigger.addEventListener('click', function() {
                const isOpen = diagContent.classList.contains('show');
                if (isOpen) {
                    diagContent.classList.remove('show');
                    diagTrigger.classList.remove('active');
                    diagTrigger.setAttribute('aria-expanded', 'false');
                } else {
                    diagContent.classList.add('show');
                    diagTrigger.classList.add('active');
                    diagTrigger.setAttribute('aria-expanded', 'true');
                }
            });
        }

        // 5. Subtle interactive 3D tilt on card for desktop
        const card = document.querySelector('.content-card');
        if (card && window.matchMedia('(min-width: 1024px)').matches) {
            window.addEventListener('mousemove', (e) => {
                const { clientX, clientY } = e;
                const { innerWidth, innerHeight } = window;
                const xOffset = ((clientX / innerWidth) - 0.5) * 8;
                const yOffset = ((clientY / innerHeight) - 0.5) * 8;
                card.style.transform = `perspective(1000px) rotateY(${xOffset}deg) rotateX(${-yOffset}deg)`;
            });

            window.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
            });
        }
    </script>
</body>
</html>

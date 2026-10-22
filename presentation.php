<?php
/**
 * Student Portal System - Technical Project Showcase
 * Department of Forestry, Fisheries & the Environment (DFFE)
 * Directorate: Information Technology Business Solutions (ITBS)
 *
 * Project Lead & System Architect: Mr Avuyile Mthembu
 * Database Administrator: Ms Nomthandazo Khoza
 * Frontend Developer: Ms Qhama Nodada
 *
 * Material Design Project Showcase highlighting genuine system features,
 * 3NF relational architecture, OOP domain services, and role-based workflows.
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');

$app_dir = __DIR__;
if (file_exists($app_dir . '/config.php')) {
    require_once $app_dir . '/config.php';
}
if (file_exists($app_dir . '/functions.php')) {
    require_once $app_dir . '/functions.php';
}

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

// Standardized Environment Endpoints (Localhost AMPPS vs Live InfinityFree Cloud)
if (!defined('LOCAL_URL')) {
    define('LOCAL_URL', 'http://localhost/university_system/index.php');
}
if (!defined('LIVE_URL')) {
    define('LIVE_URL', 'https://student-system.infinityfree.io/index.php');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['username'] ?? null;
$is_logged_in = !empty($_SESSION['user_id']);

// Default metrics with graceful fallback
$stats = [
    'students'    => 12,
    'courses'     => 4,
    'modules'     => 14,
    'lecturers'   => 5,
    'results'     => 48,
    'enrollments' => 12
];
$db_live = false;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $s = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $c = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $m = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
        $l = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
        $r = $pdo->query("SELECT COUNT(*) FROM module_results")->fetchColumn();
        $e = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Active'")->fetchColumn();

        if ($s !== false) $stats['students'] = (int) $s;
        if ($c !== false) $stats['courses'] = (int) $c;
        if ($m !== false) $stats['modules'] = (int) $m;
        if ($l !== false) $stats['lecturers'] = (int) $l;
        if ($r !== false) $stats['results'] = (int) $r;
        if ($e !== false) $stats['enrollments'] = (int) $e;
        $db_live = true;
    } catch (Throwable $ex) {
        $db_live = false;
    }
}

// Determine dashboard return link
$dashboard_url = BASE_URL . 'index.php';
$dashboard_label = 'Portal Login';
if ($is_logged_in) {
    if ($user_role === 'admin') {
        $dashboard_url = BASE_URL . 'admin/dashboard.php';
        $dashboard_label = 'Admin Dashboard';
    } elseif ($user_role === 'lecturer') {
        $dashboard_url = BASE_URL . 'lecturer/dashboard.php';
        $dashboard_label = 'Lecturer Dashboard';
    } elseif ($user_role === 'student') {
        $dashboard_url = BASE_URL . 'student/dashboard.php';
        $dashboard_label = 'Student Dashboard';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1565c0">
    <title>Technical Project Showcase - Student Management System | DFFE</title>

    <!-- Google Fonts: Inter & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Materialize CSS & Portal Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/material-design.css">

    <style>
        body {
            background-color: #f0f2f5;
            color: #1e293b;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 60%, #1976d2 100%);
            color: #ffffff;
            padding: 35px 0 25px 0;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(13, 71, 161, 0.2);
        }
        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .hero-title {
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0 0 8px 0;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            color: #e3f2fd;
            margin: 0 0 16px 0;
            max-width: 820px;
            line-height: 1.5;
        }
        .team-chip {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 0.82rem;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 32px;
            margin-bottom: 4px;
        }
        .stat-card .stat-val {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin: 4px 0;
        }
        .stat-card .stat-lbl {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
        }
        .nav-tabs-wrapper {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .tabs .tab a {
            color: #475569;
            font-weight: 600;
            text-transform: none;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0 16px;
        }
        .tabs .tab a:hover, .tabs .tab a.active {
            color: #1565c0;
        }
        .tabs .indicator {
            background-color: #1565c0;
            height: 3px;
        }
        .content-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .content-card h5 {
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }
        .feature-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background-color: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .feature-icon-box i {
            color: #1565c0;
            font-size: 20px;
        }
        .schema-badge {
            background-color: #0f172a;
            color: #38bdf8;
            font-family: monospace;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 4px;
        }
        .doc-link-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s ease;
            height: 100%;
        }
        .doc-link-card:hover {
            border-color: #1565c0;
            background-color: #f0f7ff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <header>
        <nav class="blue darken-3" role="navigation">
            <div class="nav-wrapper container">
                <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo" style="font-size: 1.25rem; display: flex; align-items: center;">
                    <i class="material-icons left">account_balance</i>
                    <span class="hide-on-small-only">Student Management System</span>
                    <span class="hide-on-med-and-up">SMS Portal</span>
                </a>
                <ul class="right">
                    <li>
                        <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" class="waves-effect tooltipped" data-position="bottom" data-tooltip="Open Live Cloud Deployment" style="display: flex; align-items: center; gap: 4px;">
                            <i class="material-icons left green-text text-accent-3">cloud</i>
                            <span class="hide-on-small-only">Live Cloud Demo</span>
                            <i class="material-icons right tiny hide-on-small-only" style="font-size: 14px; margin-left: -2px;">open_in_new</i>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo LOCAL_URL; ?>" class="waves-effect tooltipped" data-position="bottom" data-tooltip="Open Localhost AMPPS Deployment" style="display: flex; align-items: center; gap: 4px;">
                            <i class="material-icons left blue-text text-lighten-3">computer</i>
                            <span class="hide-on-small-only">Localhost</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $dashboard_url; ?>" class="waves-effect">
                            <i class="material-icons left">dashboard</i>
                            <span><?php echo $dashboard_label; ?></span>
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="waves-effect">
                                <i class="material-icons left">exit_to_app</i>
                                <span class="hide-on-small-only">Logout</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <!-- Hero Header -->
        <section class="hero-banner">
            <div class="container">
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m9">
                        <div class="hero-tag">
                            <i class="material-icons tiny">verified</i> Department of Forestry, Fisheries &amp; the Environment
                        </div>
                        <h1 class="hero-title">Student Portal &amp; Academic Management System</h1>
                        <p class="hero-subtitle">
                            An enterprise-grade academic records and student lifecycle platform engineered with 3NF relational normalization, clean PHP 8 Object-Oriented domain architecture, role-based governance, and an AI Success Advisor.
                        </p>
                        <div style="margin-bottom: 16px;">
                            <span class="team-chip"><i class="material-icons tiny">person</i> Mr Avuyile Mthembu (Full Stack &amp; Architecture)</span>
                            <span class="team-chip"><i class="material-icons tiny">storage</i> Ms Nomthandazo Khoza (DBA &amp; Relational Modeling)</span>
                            <span class="team-chip"><i class="material-icons tiny">palette</i> Ms Qhama Nodada (Frontend &amp; UI/UX)</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" class="btn waves-effect waves-light green darken-1" style="font-weight: 600; text-transform: none; border-radius: 6px; box-shadow: 0 3px 8px rgba(0,0,0,0.25); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="material-icons left tiny" style="margin-right: 2px;">cloud</i> Live InfinityFree Version <i class="material-icons tiny" style="font-size: 14px; opacity: 0.85;">open_in_new</i>
                            </a>
                            <a href="<?php echo LOCAL_URL; ?>" class="btn waves-effect waves-light white blue-text text-darken-3" style="font-weight: 600; text-transform: none; border-radius: 6px; box-shadow: 0 3px 8px rgba(0,0,0,0.2); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="material-icons left tiny" style="margin-right: 2px;">computer</i> Localhost Portal (AMPPS)
                            </a>
                        </div>
                    </div>
                    <div class="col s12 m3 center-align hide-on-small-only" style="margin-top: 15px;">
                        <div style="background: rgba(255,255,255,0.15); border-radius: 12px; padding: 18px 12px; border: 1px solid rgba(255,255,255,0.25);">
                            <i class="material-icons large" style="color: #ffffff;">school</i>
                            <div style="font-weight: 700; font-size: 0.95rem; margin-top: 6px;">IT Business Solutions</div>
                            <div style="font-size: 0.8rem; color: #e3f2fd; margin-bottom: 8px;">Corporate Management</div>
                            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(76, 175, 80, 0.35); border: 1px solid rgba(105, 240, 174, 0.6); padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; color: #ffffff;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #69f0ae; display: inline-block; box-shadow: 0 0 6px #69f0ae;"></span>
                                <span>Cloud &amp; Local Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Live Database Metrics Strip -->
        <div class="container">
            <div class="row">
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card blue darken-1 white-text">
                        <i class="material-icons">people</i>
                        <div class="stat-val"><?php echo $stats['students']; ?></div>
                        <div class="stat-lbl">Students</div>
                    </div>
                </div>
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card green darken-1 white-text">
                        <i class="material-icons">how_to_reg</i>
                        <div class="stat-val"><?php echo $stats['enrollments']; ?></div>
                        <div class="stat-lbl">Active Enrolled</div>
                    </div>
                </div>
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card purple darken-1 white-text">
                        <i class="material-icons">school</i>
                        <div class="stat-val"><?php echo $stats['courses']; ?></div>
                        <div class="stat-lbl">Courses</div>
                    </div>
                </div>
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card teal darken-1 white-text">
                        <i class="material-icons">book</i>
                        <div class="stat-val"><?php echo $stats['modules']; ?></div>
                        <div class="stat-lbl">Modules</div>
                    </div>
                </div>
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card indigo darken-1 white-text">
                        <i class="material-icons">supervisor_account</i>
                        <div class="stat-val"><?php echo $stats['lecturers']; ?></div>
                        <div class="stat-lbl">Lecturers</div>
                    </div>
                </div>
                <div class="col s6 m4 l2" style="margin-bottom: 15px;">
                    <div class="stat-card amber darken-2 white-text">
                        <i class="material-icons">assessment</i>
                        <div class="stat-val"><?php echo $stats['results']; ?></div>
                        <div class="stat-lbl">Results Logged</div>
                    </div>
                </div>
            </div>

            <!-- Materialize Tabs Navigation -->
            <div class="nav-tabs-wrapper">
                <ul class="tabs tabs-fixed-width">
                    <li class="tab"><a class="active" href="#overview"><i class="material-icons tiny">info</i> Overview</a></li>
                    <li class="tab"><a href="#portals"><i class="material-icons tiny">groups</i> Role Portals</a></li>
                    <li class="tab"><a href="#database"><i class="material-icons tiny">storage</i> 3NF Database</a></li>
                    <li class="tab"><a href="#architecture"><i class="material-icons tiny">code</i> Architecture &amp; Security</a></li>
                    <li class="tab"><a href="#documentation"><i class="material-icons tiny">description</i> Documentation &amp; Access</a></li>
                </ul>
            </div>

            <!-- ==========================================
                 TAB 1: SYSTEM OVERVIEW & OBJECTIVES
                 ========================================== -->
            <div id="overview" class="col s12">
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">flag</i> Project Scope &amp; Engineering Mandate</h5>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        The Student Management System (SMS) was engineered for the <strong>Department of Forestry, Fisheries &amp; the Environment (DFFE)</strong> within the Directorate: <strong>Information Technology Business Solutions (ITBS)</strong>. The system modernizes legacy manual and single-table student record tracking into a secure, normalized web platform supporting administrative lifecycle management, continuous quarterly academic grading, student GPA analysis, and automated PDF transcript generation.
                    </p>

                    <div class="row" style="margin-top: 25px;">
                        <div class="col s12 m6">
                            <div class="feature-item">
                                <div class="feature-icon-box"><i class="material-icons">check_circle</i></div>
                                <div>
                                    <strong style="color: #1e293b; display: block; font-size: 0.95rem;">3NF Relational Normalization</strong>
                                    <span style="color: #64748b; font-size: 0.88rem;">Migrated legacy flat records into 8 normalized relational tables, removing insert/update/delete anomalies and establishing foreign key constraints.</span>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon-box"><i class="material-icons">lock</i></div>
                                <div>
                                    <strong style="color: #1e293b; display: block; font-size: 0.95rem;">Role-Based Access Control (RBAC)</strong>
                                    <span style="color: #64748b; font-size: 0.88rem;">Strict separation of responsibilities across Administrators, Faculty Lecturers, and Enrolled Students with secure session validation.</span>
                                </div>
                            </div>
                        </div>

                        <div class="col s12 m6">
                            <div class="feature-item">
                                <div class="feature-icon-box"><i class="material-icons">smart_toy</i></div>
                                <div>
                                    <strong style="color: #1e293b; display: block; font-size: 0.95rem;">AI Success Advisor &amp; Heuristic Fallback</strong>
                                    <span style="color: #64748b; font-size: 0.88rem;">Empowers students with intelligent grade diagnosis, study recommendations, and continuous offline fallback heuristics during API outages.</span>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon-box"><i class="material-icons">picture_as_pdf</i></div>
                                <div>
                                    <strong style="color: #1e293b; display: block; font-size: 0.95rem;">Automated Transcripts &amp; Numbering</strong>
                                    <span style="color: #64748b; font-size: 0.88rem;">Deterministic student number generation (<code>ST2600xxx</code>) and client-side jsPDF transcript generation with zero server strain.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technology Stack Card -->
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">layers</i> Technical Specification &amp; Stack</h5>
                    <div class="row" style="margin-bottom: 0;">
                        <div class="col s12 m4" style="margin-bottom: 12px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 8px 0;"><i class="material-icons tiny blue-text">dns</i> Backend Architecture</h6>
                                <p style="font-size: 0.85rem; color: #475569; margin: 0;">
                                    • PHP 8.2 Object-Oriented Domain Layer<br>
                                    • Prepared Statements with PDO<br>
                                    • Bcrypt Password Cryptography<br>
                                    • Apache HTTP Server &amp; .htaccess Routing
                                </p>
                            </div>
                        </div>
                        <div class="col s12 m4" style="margin-bottom: 12px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 8px 0;"><i class="material-icons tiny green-text">storage</i> Database Engine</h6>
                                <p style="font-size: 0.85rem; color: #475569; margin: 0;">
                                    • MariaDB / MySQL 8.0 Engine<br>
                                    • 8 Relational 3NF Schema Tables<br>
                                    • Referential Integrity &amp; Cascades<br>
                                    • Dual-Column Legacy Compatibility
                                </p>
                            </div>
                        </div>
                        <div class="col s12 m4" style="margin-bottom: 12px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 8px 0;"><i class="material-icons tiny purple-text">brush</i> Frontend &amp; UI</h6>
                                <p style="font-size: 0.85rem; color: #475569; margin: 0;">
                                    • Materialize CSS 1.0.0 (Material Design)<br>
                                    • Google Fonts: Inter &amp; Material Icons<br>
                                    • Real-time JavaScript Search Filters<br>
                                    • jsPDF Client-Side Document Exporter
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deployment Environments & Public Access Card -->
                <div class="content-card">
                    <h5><i class="material-icons green-text text-darken-2">cloud_done</i> Deployment Environments &amp; Public Access</h5>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        The Student Management System is operational across two environments: a live cloud-hosted production instance on InfinityFree and a local AMPPS workstation instance for local evaluation and rapid testing.
                    </p>
                    <div class="row" style="margin-top: 15px; margin-bottom: 0;">
                        <div class="col s12 m6" style="margin-bottom: 15px;">
                            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 18px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                        <h6 style="font-weight: 700; color: #166534; margin: 0; display: flex; align-items: center; gap: 6px;">
                                            <i class="material-icons green-text text-darken-2">cloud</i> Live Production (InfinityFree)
                                        </h6>
                                        <span class="chip tiny green white-text" style="height: 20px; line-height: 20px; font-size: 0.72rem; margin: 0;">ONLINE</span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: #374151; margin-bottom: 8px;">
                                        Public cloud deployment accessible globally without local environment configuration. Features remote MySQL database and full role workflows.
                                    </p>
                                    <div style="word-break: break-all; font-family: monospace; font-size: 0.82rem; background: #ffffff; padding: 6px 10px; border-radius: 4px; border: 1px solid #d1fae5; color: #047857; margin-bottom: 12px;">
                                        <?php echo LIVE_URL; ?>
                                    </div>
                                </div>
                                <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" class="btn green darken-2 waves-effect waves-light" style="width: 100%; text-transform: none; font-weight: 600;">
                                    <i class="material-icons left tiny">open_in_new</i> Open Live InfinityFree Version
                                </a>
                            </div>
                        </div>

                        <div class="col s12 m6" style="margin-bottom: 15px;">
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 18px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                        <h6 style="font-weight: 700; color: #1e40af; margin: 0; display: flex; align-items: center; gap: 6px;">
                                            <i class="material-icons blue-text text-darken-2">computer</i> Localhost Development (AMPPS)
                                        </h6>
                                        <span class="chip tiny blue white-text" style="height: 20px; line-height: 20px; font-size: 0.72rem; margin: 0;">LOCAL</span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: #374151; margin-bottom: 8px;">
                                        Running directly on the local AMPPS software stack (Apache 2.4, PHP 8.2, MariaDB 10.x). Optimized for development, unit testing, and debugging.
                                    </p>
                                    <div style="word-break: break-all; font-family: monospace; font-size: 0.82rem; background: #ffffff; padding: 6px 10px; border-radius: 4px; border: 1px solid #dbeafe; color: #1d4ed8; margin-bottom: 12px;">
                                        <?php echo LOCAL_URL; ?>
                                    </div>
                                </div>
                                <a href="<?php echo LOCAL_URL; ?>" class="btn blue darken-2 waves-effect waves-light" style="width: 100%; text-transform: none; font-weight: 600;">
                                    <i class="material-icons left tiny">launch</i> Open Localhost Portal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 TAB 2: ROLE-BASED PORTALS
                 ========================================== -->
            <div id="portals" class="col s12">
                <div style="background-color: #f0fdf4; border-left: 4px solid #16a34a; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons green-text text-darken-2">cloud_queue</i>
                        <span style="font-size: 0.9rem; color: #166534;">Each portal can be tested locally on <strong>Localhost</strong> or on the live cloud at <strong>InfinityFree</strong>.</span>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" class="btn-small green darken-2 waves-effect waves-light" style="font-size: 0.78rem; text-transform: none;">
                            <i class="material-icons left tiny">cloud</i> Open Live Version
                        </a>
                        <a href="<?php echo LOCAL_URL; ?>" class="btn-small blue darken-2 waves-effect waves-light" style="font-size: 0.78rem; text-transform: none;">
                            <i class="material-icons left tiny">computer</i> Open Localhost
                        </a>
                    </div>
                </div>
                <div class="row">
                    <!-- Administrator Hub -->
                    <div class="col s12 m4" style="margin-bottom: 20px;">
                        <div class="content-card" style="height: 100%;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 8px; background-color: #e3f2fd; display: flex; align-items: center; justify-content: center;">
                                    <i class="material-icons blue-text text-darken-2" style="font-size: 24px;">admin_panel_settings</i>
                                </div>
                                <div>
                                    <h6 style="font-weight: 700; margin: 0; color: #1e293b;">Administrator Hub</h6>
                                    <span style="font-size: 0.78rem; color: #64748b;">Governance &amp; Operations</span>
                                </div>
                            </div>
                            <ul style="font-size: 0.88rem; color: #475569; line-height: 1.8; margin: 0; padding-left: 18px;">
                                <li><strong>Student Registration:</strong> Automated <code>ST2600xxx</code> student number generation and password creation.</li>
                                <li><strong>Curriculum Management:</strong> Create and update academic qualifications, courses, and modules.</li>
                                <li><strong>Faculty Allocations:</strong> Assign lecturers to modules and manage staff credentials.</li>
                                <li><strong>Student Directory:</strong> Live search, status toggling, and profile updates.</li>
                            </ul>
                            <div class="divider" style="margin: 16px 0;"></div>
                            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn blue darken-2 waves-effect waves-light btn-small" style="width: 100%;">
                                <i class="material-icons left tiny">dashboard</i> View Admin Hub
                            </a>
                        </div>
                    </div>

                    <!-- Lecturer Portal -->
                    <div class="col s12 m4" style="margin-bottom: 20px;">
                        <div class="content-card" style="height: 100%;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 8px; background-color: #e0f2f1; display: flex; align-items: center; justify-content: center;">
                                    <i class="material-icons teal-text text-darken-2" style="font-size: 24px;">school</i>
                                </div>
                                <div>
                                    <h6 style="font-weight: 700; margin: 0; color: #1e293b;">Faculty Lecturer Portal</h6>
                                    <span style="font-size: 0.78rem; color: #64748b;">Teaching &amp; Grading</span>
                                </div>
                            </div>
                            <ul style="font-size: 0.88rem; color: #475569; line-height: 1.8; margin: 0; padding-left: 18px;">
                                <li><strong>Teaching Allocations:</strong> Dedicated view of modules specifically assigned to the lecturer.</li>
                                <li><strong>Module Rosters:</strong> Real-time student class enrollment rosters with contact info.</li>
                                <li><strong>Quarterly Marks Capture:</strong> Input Q1, Q2, Q3, and Q4 continuous assessments.</li>
                                <li><strong>Automated Grade Metrics:</strong> Instant calculation of student averages and pass/fail statuses.</li>
                            </ul>
                            <div class="divider" style="margin: 16px 0;"></div>
                            <a href="<?php echo BASE_URL; ?>lecturer/dashboard.php" class="btn teal darken-1 waves-effect waves-light btn-small" style="width: 100%;">
                                <i class="material-icons left tiny">edit_note</i> View Lecturer Portal
                            </a>
                        </div>
                    </div>

                    <!-- Student Portal -->
                    <div class="col s12 m4" style="margin-bottom: 20px;">
                        <div class="content-card" style="height: 100%;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 8px; background-color: #e8f5e9; display: flex; align-items: center; justify-content: center;">
                                    <i class="material-icons green-text text-darken-2" style="font-size: 24px;">account_circle</i>
                                </div>
                                <div>
                                    <h6 style="font-weight: 700; margin: 0; color: #1e293b;">Student Portal</h6>
                                    <span style="font-size: 0.78rem; color: #64748b;">Learner Experience</span>
                                </div>
                            </div>
                            <ul style="font-size: 0.88rem; color: #475569; line-height: 1.8; margin: 0; padding-left: 18px;">
                                <li><strong>Academic Dashboard:</strong> Enrolled qualification overview, progress badges, and status.</li>
                                <li><strong>Official Transcript:</strong> Term-by-term breakdown with cumulative GPA computation.</li>
                                <li><strong>AI Success Coach:</strong> Personalized guidance based on current quarterly subject marks.</li>
                                <li><strong>PDF Transcript Export:</strong> Generate and print verified academic statements.</li>
                            </ul>
                            <div class="divider" style="margin: 16px 0;"></div>
                            <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="btn green darken-2 waves-effect waves-light btn-small" style="width: 100%;">
                                <i class="material-icons left tiny">person</i> View Student Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 TAB 3: RELATIONAL DATABASE & 3NF NORMALIZATION
                 ========================================== -->
            <div id="database" class="col s12">
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">storage</i> 3NF Relational Database Schema</h5>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        The legacy platform utilized an unnormalized single flat table that suffered from partial functional dependencies, transitive dependencies, and severe update/deletion anomalies. Under the guidance of our Database Administrator, the data model was restructured into <strong>Third Normal Form (3NF)</strong> spanning 8 distinct relational entities with strict referential constraints.
                    </p>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">students</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Primary key <code>id</code>, <code>student_number</code> (unique), <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>id_number</code>, legacy <code>full_name</code> sync.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">courses</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Primary key <code>course_id</code>, <code>course_name</code>, <code>course_code</code>, <code>duration</code>, <code>description</code>, <code>created_at</code>.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">modules</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Primary key <code>module_id</code>, <code>module_code</code>, <code>module_name</code>, <code>credits</code>, <code>semester</code>.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">course_modules</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Many-to-many bridge table linking <code>course_id</code> and <code>module_id</code> with cascade deletion rules.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">lecturers</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Primary key <code>lecturer_id</code>, <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>department</code>, <code>office_number</code>.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">module_lecturers</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Allocation table associating <code>module_id</code> to <code>lecturer_id</code> for multi-lecturer support.
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">enrollments</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Tracks <code>student_id</code>, <code>course_id</code>, <code>academic_year</code>, <code>status</code> ('Active', 'Completed', 'Dropped').
                                </p>
                            </div>
                        </div>

                        <div class="col s12 m6 l3" style="margin-bottom: 15px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                                <span class="schema-badge">module_results</span>
                                <p style="font-size: 0.82rem; color: #475569; margin: 4px 0 0 0;">
                                    Quarterly continuous assessments (<code>q1_mark</code>, <code>q2_mark</code>, <code>q3_mark</code>, <code>q4_mark</code>, <code>average</code>, <code>status</code>).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Normalization Strategy -->
                    <div style="background-color: #f0f7ff; border-left: 4px solid #1565c0; padding: 14px 18px; border-radius: 4px; margin-top: 15px;">
                        <h6 style="font-weight: 700; color: #0d47a1; margin: 0 0 6px 0;">Referential Integrity &amp; Dual-Column Backward Compatibility</h6>
                        <p style="font-size: 0.88rem; color: #334155; margin: 0;">
                            To ensure 100% backward compatibility with legacy scripts, the <code>students</code> table maintains both legacy single-string fields (<code>full_name</code>, <code>student_id_number</code>) alongside normalized first/last names. Any updates through domain service classes (such as <code>RegistrationService</code>) automatically synchronize both column sets in real time.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 TAB 4: ARCHITECTURE & SECURITY
                 ========================================== -->
            <div id="architecture" class="col s12">
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">code</i> PHP 8.2 Object-Oriented Domain Architecture</h5>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        Application logic is cleanly structured inside dedicated PHP domain models in the <code>classes/</code> directory. Each business entity encapsulates its own validation, persistence queries, and calculated metrics.
                    </p>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col s12 m6">
                            <ul class="collection with-header" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                                <li class="collection-header" style="background-color: #f8fafc;"><strong style="color: #1e293b;">Domain Classes (<code>classes/</code>)</strong></li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;">Student.php</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Lifecycle management, GPA calculation, active enrollment resolution.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;">Course.php &amp; Module.php</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Curriculum definitions, module credit management, course-module associations.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;">Lecturer.php</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Faculty directory, assigned classes, and module lecturer roster lookups.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;">ModuleResult.php</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Quarterly continuous assessments, automatic weighting, pass/fail thresholds.</p>
                                </li>
                            </ul>
                        </div>

                        <div class="col s12 m6">
                            <ul class="collection with-header" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                                <li class="collection-header" style="background-color: #f8fafc;"><strong style="color: #1e293b;">Core Security Controls</strong></li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;"><i class="material-icons tiny green-text">verified</i> Password Hashing</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Implemented with native PHP <code>PASSWORD_BCRYPT</code> with per-user salt protection.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;"><i class="material-icons tiny green-text">verified</i> SQL Injection Prevention</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">100% Parameterized queries executed through PDO prepared statements.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;"><i class="material-icons tiny green-text">verified</i> Role Session Guards</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Centralized <code>require_login($allowed_roles)</code> guard protecting every internal endpoint.</p>
                                </li>
                                <li class="collection-item">
                                    <span class="title" style="font-weight: 600;"><i class="material-icons tiny green-text">verified</i> Session Fixation Defense</span>
                                    <p style="font-size: 0.82rem; color: #64748b; margin: 2px 0 0 0;">Session identifiers regenerated via <code>session_regenerate_id(true)</code> on login.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==========================================
                 TAB 5: DOCUMENTATION & TEST ACCESS
                 ========================================== -->
            <div id="documentation" class="col s12">
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">description</i> Engineering Documentation Deliverables</h5>
                    <p style="color: #475569; font-size: 0.95rem; line-height: 1.6;">
                        In accordance with formal software engineering standards, the project includes comprehensive technical documentation in the <code>documentation/</code> directory.
                    </p>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col s12 m6 l4" style="margin-bottom: 15px;">
                            <div class="doc-link-card">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">01_PROJECT_MANAGEMENT_PLAN.md</h6>
                                <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px;">Sprint schedules, Work Breakdown Structure (WBS), risk register, and resource allocation matrix.</p>
                                <span class="chip tiny blue lighten-5 blue-text">Project Governance</span>
                            </div>
                        </div>

                        <div class="col s12 m6 l4" style="margin-bottom: 15px;">
                            <div class="doc-link-card">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">02_SYSTEMS_ANALYSIS.md</h6>
                                <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px;">As-Is vs To-Be process workflows, TELOS feasibility evaluation, and Level 0/1 Data Flow Diagrams.</p>
                                <span class="chip tiny teal lighten-5 teal-text">Process Modeling</span>
                            </div>
                        </div>

                        <div class="col s12 m6 l4" style="margin-bottom: 15px;">
                            <div class="doc-link-card">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">03_SOFTWARE_REQUIREMENTS.md</h6>
                                <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px;">Formal IEEE 830 functional specifications (FR-01 to FR-18) and non-functional performance benchmarks.</p>
                                <span class="chip tiny purple lighten-5 purple-text">IEEE 830 SRS</span>
                            </div>
                        </div>

                        <div class="col s12 m6 l4" style="margin-bottom: 15px;">
                            <div class="doc-link-card">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">04_SOFTWARE_ARCHITECTURE.md</h6>
                                <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px;">OOP class hierarchies, domain layer interactions, security implementations, and UML class models.</p>
                                <span class="chip tiny indigo lighten-5 indigo-text">Architecture &amp; Design</span>
                            </div>
                        </div>

                        <div class="col s12 m6 l4" style="margin-bottom: 15px;">
                            <div class="doc-link-card">
                                <h6 style="font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">05_DATABASE_SPECIFICATION.md</h6>
                                <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px;">Relational data dictionary, 3NF normalization proofs, SQL migrations, and ERD diagrams.</p>
                                <span class="chip tiny green lighten-5 green-text">3NF Relational DB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Credentials & System Access Card -->
                <div class="content-card">
                    <h5><i class="material-icons blue-text text-darken-2">vpn_key</i> System Evaluation Credentials &amp; Access</h5>
                    <p style="color: #475569; font-size: 0.92rem;">
                        Use the verified credentials below to evaluate each role across both the <strong>Live InfinityFree Cloud Instance</strong> and the <strong>Localhost AMPPS Deployment</strong>:
                    </p>

                    <!-- Deployment Quick Link Alert -->
                    <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin: 15px 0;">
                        <div class="row" style="margin-bottom: 0;">
                            <div class="col s12 m6" style="margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="material-icons green-text text-darken-2" style="font-size: 26px;">cloud</i>
                                    <div>
                                        <strong style="color: #166534; font-size: 0.88rem; display: block;">Live Production URL (InfinityFree Cloud)</strong>
                                        <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" style="color: #047857; font-family: monospace; font-size: 0.84rem; text-decoration: underline; word-break: break-all;">
                                            <?php echo LIVE_URL; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col s12 m6" style="margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="material-icons blue-text text-darken-2" style="font-size: 26px;">computer</i>
                                    <div>
                                        <strong style="color: #1e40af; font-size: 0.88rem; display: block;">Localhost Development URL (AMPPS)</strong>
                                        <a href="<?php echo LOCAL_URL; ?>" style="color: #1d4ed8; font-family: monospace; font-size: 0.84rem; text-decoration: underline; word-break: break-all;">
                                            <?php echo LOCAL_URL; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="striped responsive-table" style="margin-top: 15px;">
                        <thead>
                            <tr style="background-color: #f8fafc;">
                                <th>Role</th>
                                <th>Username / ID</th>
                                <th>Default Password</th>
                                <th>Environment / Access Rights</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="chip blue white-text" style="height: 24px; line-height: 24px;">Administrator</span></td>
                                <td><code>admin</code></td>
                                <td><code>admin123</code></td>
                                <td><strong>Live &amp; Local:</strong> Full governance, student registration, courses, modules, faculty.</td>
                            </tr>
                            <tr>
                                <td><span class="chip teal white-text" style="height: 24px; line-height: 24px;">Lecturer</span></td>
                                <td><code>jdoe</code> <span style="color: #64748b;">(Local)</span><br><code>lecturer</code> <span style="color: #64748b;">(Live Cloud)</span></td>
                                <td><code>password123</code></td>
                                <td><strong>Live &amp; Local:</strong> Assigned classes, module rosters, quarterly marks capture (Q1–Q4).</td>
                            </tr>
                            <tr>
                                <td><span class="chip green white-text" style="height: 24px; line-height: 24px;">Student</span></td>
                                <td><code>10ha2304014</code> <span style="color: #64748b;">(Live Cloud)</span><br><code>ST20260001</code> <span style="color: #64748b;">(Localhost)</span></td>
                                <td><code>bitcoin</code> <span style="color: #64748b;">(Live)</span><br><code>password123</code> <span style="color: #64748b;">(Local)</span></td>
                                <td><strong>Live &amp; Local:</strong> Academic transcript, GPA metrics, AI Success Advisor, PDF transcript export.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="margin-top: 30px; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                        <a href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" class="btn-large green darken-2 waves-effect waves-light" style="font-weight: 600; text-transform: none; border-radius: 6px; box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);">
                            <i class="material-icons left">cloud</i> Launch Live InfinityFree Version
                        </a>
                        <a href="<?php echo LOCAL_URL; ?>" class="btn-large blue darken-2 waves-effect waves-light" style="font-weight: 600; text-transform: none; border-radius: 6px; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.25);">
                            <i class="material-icons left">computer</i> Launch Localhost Portal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="page-footer blue darken-3" style="margin-top: 40px;">
        <div class="footer-copyright">
            <div class="container">
                &copy; <?php echo date("Y"); ?> Student Management System &bull; Department of Forestry, Fisheries &amp; the Environment
                <div class="right hide-on-small-only" style="display: flex; gap: 20px;">
                    <a class="grey-text text-lighten-4" href="<?php echo LIVE_URL; ?>" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 4px;">
                        <i class="material-icons tiny green-text text-accent-3">cloud</i> Live InfinityFree Version
                    </a>
                    <a class="grey-text text-lighten-4" href="<?php echo LOCAL_URL; ?>" style="display: inline-flex; align-items: center; gap: 4px;">
                        <i class="material-icons tiny">computer</i> Localhost Portal
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Passive Event Listeners & Materialize JavaScript -->
    <script src="<?php echo BASE_URL; ?>js/passive-listeners.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('.tabs');
            M.Tabs.init(tabs, {
                swipeable: false
            });
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
        });
    </script>
</body>
</html>
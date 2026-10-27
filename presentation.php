<?php
/**
 * Student Portal System - Project Presentation
 * Department of Forestry, Fisheries & the Environment (DFFE)
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
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/') : '';
    $current_dir = rtrim(str_replace('\\', '/', realpath($app_dir)), '/');

    if (!empty($doc_root) && strpos($current_dir, $doc_root) === 0) {
        $relative_dir = substr($current_dir, strlen($doc_root));
    } else {
        $relative_dir = '/' . basename($current_dir);
    }

    $clean_dir = trim($relative_dir, '/');
    define('BASE_URL', $protocol . $host . ($clean_dir !== '' ? '/' . $clean_dir : '') . '/');
}

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
$is_logged_in = !empty($_SESSION['user_id']);
$stats = [
    'students' => 12,
    'courses' => 4,
    'modules' => 14,
    'lecturers' => 5,
    'results' => 48,
    'enrollments' => 12,
];
$db_live = false;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $live_stats = [
            'students' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
            'courses' => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
            'modules' => (int) $pdo->query('SELECT COUNT(*) FROM modules')->fetchColumn(),
            'lecturers' => (int) $pdo->query('SELECT COUNT(*) FROM lecturers')->fetchColumn(),
            'results' => (int) $pdo->query('SELECT COUNT(*) FROM module_results')->fetchColumn(),
            'enrollments' => (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Active'")->fetchColumn(),
        ];
        $stats = $live_stats;
        $db_live = true;
    } catch (PDOException $ex) {
        error_log('Presentation metrics could not be loaded: ' . $ex->getMessage());
    }
}

$dashboard_url = BASE_URL . 'index.php';
$dashboard_label = 'Portal login';
if ($is_logged_in) {
    if ($user_role === 'admin') {
        $dashboard_url = BASE_URL . 'admin/dashboard.php';
        $dashboard_label = 'Admin dashboard';
    } elseif ($user_role === 'lecturer') {
        $dashboard_url = BASE_URL . 'lecturer/dashboard.php';
        $dashboard_label = 'Lecturer dashboard';
    } elseif ($user_role === 'student') {
        $dashboard_url = BASE_URL . 'student/dashboard.php';
        $dashboard_label = 'Student dashboard';
    }
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1565c0">
    <meta name="description" content="Project presentation for the DFFE Student Portal and Academic Management System.">
    <title>Student Portal — Project Presentation</title>
    <style>
        :root {
            color-scheme: light;
            --blue: #1565c0;
            --blue-dark: #0d47a1;
            --blue-light: #eaf3fc;
            --teal: #26a69a;
            --paper: #f0f2f5;
            --surface: #fff;
            --ink: #212121;
            --muted: #64707d;
            --line: #d9e0e8;
            --font: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body { width: 100%; height: 100%; margin: 0; }

        body {
            overflow: hidden;
            background: var(--paper);
            color: var(--ink);
            font-family: var(--font);
            -webkit-font-smoothing: antialiased;
        }

        button, a { font: inherit; }
        a { color: var(--blue-dark); }
        a:focus-visible, button:focus-visible { outline: 3px solid var(--teal); outline-offset: 3px; }

        .deck {
            height: 100%;
            display: grid;
            grid-template-rows: 58px minmax(0, 1fr) 64px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 5vw;
            border-bottom: 1px solid var(--line);
            background: var(--surface);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 650;
            text-decoration: none;
        }

        .brand-mark {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 4px;
            background: var(--blue);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .topbar-note { color: var(--muted); font-size: 0.78rem; }

        .stage {
            position: relative;
            min-height: 0;
            overflow: auto;
        }

        .slide {
            display: none;
            width: min(1120px, calc(100% - 64px));
            min-height: 100%;
            margin: 0 auto;
            padding: clamp(36px, 7vh, 76px) 0;
        }

        .slide.active { display: flex; flex-direction: column; justify-content: center; }

        .kicker {
            margin: 0 0 14px;
            color: var(--blue);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        h1, h2, h3, p { margin-top: 0; }

        h1 {
            max-width: 880px;
            margin-bottom: 20px;
            color: var(--blue-dark);
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 700;
            letter-spacing: -0.055em;
            line-height: 1.04;
        }

        h2 {
            max-width: 850px;
            margin-bottom: 24px;
            color: var(--blue-dark);
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 650;
            letter-spacing: -0.045em;
            line-height: 1.12;
        }

        h3 { margin-bottom: 8px; font-size: 1.02rem; font-weight: 650; }
        p, li { color: var(--muted); font-size: clamp(0.94rem, 1.4vw, 1.08rem); line-height: 1.7; }
        p:last-child { margin-bottom: 0; }
        ul { margin: 0; padding-left: 1.2em; }
        li { margin-bottom: 8px; padding-left: 4px; }
        strong { color: var(--ink); }

        .title-slide {
            position: relative;
            overflow: hidden;
            padding: clamp(42px, 8vh, 88px);
            border-left: 7px solid var(--blue);
            background: var(--surface);
            box-shadow: 0 8px 32px rgba(27, 55, 87, 0.08);
        }

        .title-slide::after {
            position: absolute;
            top: -90px;
            right: -80px;
            width: 300px;
            height: 300px;
            border: 42px solid var(--blue-light);
            border-radius: 50%;
            content: "";
            pointer-events: none;
        }

        .title-slide h1 { position: relative; z-index: 1; }
        .subtitle { max-width: 650px; margin-bottom: 30px; color: var(--muted); font-size: clamp(1.05rem, 2vw, 1.35rem); }

        .metadata {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 28px;
            max-width: 720px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
        }

        .metadata span { color: var(--muted); font-size: 0.84rem; }
        .metadata strong { color: var(--ink); }

        .lead { max-width: 780px; margin-bottom: 30px; font-size: clamp(1.08rem, 1.8vw, 1.3rem); }

        .columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: clamp(24px, 5vw, 64px);
        }

        .columns.three { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 28px; }
        .column { padding-top: 16px; border-top: 3px solid var(--blue); }
        .column:nth-child(2) { border-color: var(--teal); }
        .column:nth-child(3) { border-color: #90a4ae; }
        .column p, .column li { font-size: 0.96rem; }

        .flow {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
        }

        .flow-step {
            padding: 11px 15px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .flow-arrow { color: var(--blue); font-weight: 700; }

        .stats {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin: 26px 0 18px;
        }

        .stat { padding: 16px 10px; border-top: 2px solid var(--blue); background: var(--surface); text-align: center; }
        .stat-value { display: block; color: var(--blue-dark); font-size: clamp(1.4rem, 3vw, 2rem); font-weight: 700; line-height: 1.2; }
        .stat-label { display: block; margin-top: 5px; color: var(--muted); font-size: 0.72rem; }

        .schema {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin: 24px 0;
        }

        .schema span {
            padding: 13px 10px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--blue-dark);
            font-family: Consolas, monospace;
            font-size: 0.84rem;
            text-align: center;
        }

        .note { max-width: 760px; padding-left: 14px; border-left: 3px solid var(--teal); }

        .demo-links { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
        .demo-link {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            padding: 9px 16px;
            border: 1px solid var(--blue);
            border-radius: 4px;
            background: var(--blue);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }
        .demo-link.secondary { background: transparent; color: var(--blue-dark); }

        .team-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; margin-top: 30px; }
        .team-member { padding-top: 14px; border-top: 1px solid var(--line); }
        .team-member strong, .team-member span { display: block; }
        .team-member span { margin-top: 4px; color: var(--muted); font-size: 0.86rem; }

        .controls {
            display: grid;
            grid-template-columns: 1fr minmax(100px, 240px) 1fr;
            align-items: center;
            gap: 20px;
            padding: 0 5vw;
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        .counter { color: var(--muted); font-size: 0.82rem; font-variant-numeric: tabular-nums; }
        .progress { height: 3px; overflow: hidden; background: var(--line); }
        .progress-bar { width: 0; height: 100%; background: var(--blue); transition: width 180ms ease; }
        .control-buttons { display: flex; justify-content: flex-end; gap: 8px; }

        .control-button {
            min-width: 76px;
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 3px;
            background: var(--surface);
            color: var(--blue-dark);
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .control-button:hover:not(:disabled) { border-color: var(--blue); background: var(--blue-light); }
        .control-button:disabled { color: #aab2bb; cursor: default; }

        @media (max-width: 760px) {
            .deck { grid-template-rows: 54px minmax(0, 1fr) 60px; }
            .topbar { padding: 0 18px; }
            .topbar-note { display: none; }
            .slide { width: calc(100% - 40px); padding: 38px 0; }
            .title-slide { padding: 34px 26px; }
            .columns.three { grid-template-columns: 1fr; gap: 20px; }
            .stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .controls { grid-template-columns: auto 1fr auto; gap: 12px; padding: 0 16px; }
            .control-button { min-width: 60px; padding: 8px; }
        }

        @media (max-width: 480px) {
            .columns, .metadata { grid-template-columns: 1fr; gap: 18px; }
            .schema { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .team-list { grid-template-columns: 1fr; gap: 14px; }
            .counter { font-size: 0.74rem; }
            .control-button { min-width: auto; font-size: 0.76rem; }
            .progress { min-width: 38px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: 0.01ms !important; }
        }

        @media print {
            html, body { height: auto; overflow: visible; background: #fff; }
            .deck { display: block; height: auto; }
            .topbar, .controls { display: none; }
            .stage { overflow: visible; }
            .slide, .slide.active {
                display: flex !important;
                width: 100%;
                min-height: 100vh;
                padding: 18mm;
                break-after: page;
            }
        }
    </style>
</head>
<body>
    <div class="deck">
        <header class="topbar">
            <a class="brand" href="<?php echo $escape(BASE_URL . 'index.php'); ?>">
                <span class="brand-mark" aria-hidden="true">D</span>
                <span>DFFE · Student Portal</span>
            </a>
            <span class="topbar-note">Project presentation</span>
        </header>

        <main class="stage" id="stage" aria-live="polite">
            <section class="slide active title-slide" aria-labelledby="slide-title">
                <p class="kicker">Department of Forestry, Fisheries &amp; the Environment</p>
                <h1 id="slide-title">Student Portal System</h1>
                <p class="subtitle">Academic management, student records and results in one connected platform.</p>
                <div class="metadata">
                    <span><strong>Prepared by</strong> Avuyile Mthembu</span>
                    <span><strong>Directorate</strong> Information Technology Business Solutions</span>
                    <span><strong>Module</strong> HSYD300-1 · Systems Development</span>
                    <span><strong>Date</strong> October 2026</span>
                </div>
            </section>

            <section class="slide" aria-labelledby="problem-title">
                <p class="kicker">01 · The opportunity</p>
                <h2 id="problem-title">From scattered records to a shared source of truth.</h2>
                <p class="lead">Manual records make it harder to keep enrolments, marks and student details accurate and in sync.</p>
                <div class="columns">
                    <div class="column">
                        <h3>The challenge</h3>
                        <ul>
                            <li>Student information duplicated across files.</li>
                            <li>Results and enrolment changes difficult to track.</li>
                            <li>Manual calculations can lead to inconsistent outcomes.</li>
                            <li>Shared documents offer little separation by role.</li>
                        </ul>
                    </div>
                    <div class="column">
                        <h3>The response</h3>
                        <p>A central web application brings student, course, module and result records together, with role-specific access for administrators, lecturers and students.</p>
                        <p class="note"><strong>Purpose:</strong> make academic administration more consistent, visible and manageable.</p>
                    </div>
                </div>
            </section>

            <section class="slide" aria-labelledby="workflow-title">
                <p class="kicker">02 · How it works</p>
                <h2 id="workflow-title">One system. Three roles.</h2>
                <p class="lead">Each user works with the information and actions relevant to their responsibilities.</p>
                <div class="columns three">
                    <div class="column">
                        <h3>Administrator</h3>
                        <p>Maintains student records, courses, modules, lecturers and enrolments.</p>
                    </div>
                    <div class="column">
                        <h3>Lecturer</h3>
                        <p>Views allocated modules and class lists, then records assessment marks.</p>
                    </div>
                    <div class="column">
                        <h3>Student</h3>
                        <p>Reviews enrolment, academic results and transcript information.</p>
                    </div>
                </div>
                <div class="flow" aria-label="Academic information flow">
                    <span class="flow-step">Student record</span><span class="flow-arrow" aria-hidden="true">→</span>
                    <span class="flow-step">Course enrolment</span><span class="flow-arrow" aria-hidden="true">→</span>
                    <span class="flow-step">Module results</span><span class="flow-arrow" aria-hidden="true">→</span>
                    <span class="flow-step">Academic progress</span>
                </div>
            </section>

            <section class="slide" aria-labelledby="design-title">
                <p class="kicker">03 · The foundation</p>
                <h2 id="design-title">A relational model supports reliable records.</h2>
                <p class="lead">The system separates core academic entities and connects them with keys and relationships instead of repeating the same details in one large record.</p>
                <div class="schema" aria-label="Core database tables">
                    <span>students</span>
                    <span>courses</span>
                    <span>modules</span>
                    <span>lecturers</span>
                    <span>enrollments</span>
                    <span>course_modules</span>
                    <span>module_results</span>
                    <span>users</span>
                </div>
                <div class="columns">
                    <div class="column">
                        <h3>Application</h3>
                        <p>PHP 8 with PDO database access, server-side validation and role-based workflows.</p>
                    </div>
                    <div class="column">
                        <h3>Data</h3>
                        <p>MariaDB/MySQL relational storage with primary keys, foreign keys and normalized records.</p>
                    </div>
                </div>
            </section>

            <section class="slide" aria-labelledby="impact-title">
                <p class="kicker">04 · At a glance</p>
                <h2 id="impact-title">The system brings core academic activity into view.</h2>
                <p class="lead">Current totals from the connected system.</p>
                <div class="stats" aria-label="Current system totals">
                    <div class="stat"><span class="stat-value"><?php echo $stats['students']; ?></span><span class="stat-label">Students</span></div>
                    <div class="stat"><span class="stat-value"><?php echo $stats['enrollments']; ?></span><span class="stat-label">Active enrolments</span></div>
                    <div class="stat"><span class="stat-value"><?php echo $stats['courses']; ?></span><span class="stat-label">Courses</span></div>
                    <div class="stat"><span class="stat-value"><?php echo $stats['modules']; ?></span><span class="stat-label">Modules</span></div>
                    <div class="stat"><span class="stat-value"><?php echo $stats['lecturers']; ?></span><span class="stat-label">Lecturers</span></div>
                    <div class="stat"><span class="stat-value"><?php echo $stats['results']; ?></span><span class="stat-label">Results</span></div>
                </div>
                <p class="note"><?php echo $db_live ? 'Metrics reflect the connected database.' : 'The database is unavailable; sample totals are shown.'; ?></p>
            </section>

            <section class="slide" aria-labelledby="team-title">
                <p class="kicker">05 · Delivery</p>
                <h2 id="team-title">Designed and built as a team.</h2>
                <p class="lead">A full-stack project spanning application architecture, data modelling and interface design.</p>
                <div class="team-list">
                    <div class="team-member"><strong>Avuyile Mthembu</strong><span>Full-stack development &amp; architecture</span></div>
                    <div class="team-member"><strong>Nomthandazo Khoza</strong><span>Database administration &amp; data modelling</span></div>
                    <div class="team-member"><strong>Qhama Nodada</strong><span>Frontend development &amp; UI/UX</span></div>
                </div>
                <div class="demo-links">
                    <a class="demo-link" href="<?php echo $escape(LIVE_URL); ?>" target="_blank" rel="noopener noreferrer">Open live portal</a>
                    <a class="demo-link secondary" href="<?php echo $escape(LOCAL_URL); ?>">Open local portal</a>
                    <a class="demo-link secondary" href="<?php echo $escape($dashboard_url); ?>"><?php echo $escape($dashboard_label); ?></a>
                </div>
            </section>
        </main>

        <footer class="controls" aria-label="Presentation controls">
            <span class="counter" id="counter" aria-live="polite">01 / 06</span>
            <div class="progress" role="progressbar" aria-label="Presentation progress" aria-valuemin="1" aria-valuemax="6" aria-valuenow="1">
                <div class="progress-bar" id="progress"></div>
            </div>
            <div class="control-buttons">
                <button class="control-button" id="previous" type="button" disabled>Previous</button>
                <button class="control-button" id="next" type="button">Next</button>
            </div>
        </footer>
    </div>

    <script>
        (function () {
            var slides = Array.prototype.slice.call(document.querySelectorAll('.slide'));
            var counter = document.getElementById('counter');
            var progress = document.getElementById('progress');
            var progressTrack = document.querySelector('.progress');
            var previous = document.getElementById('previous');
            var next = document.getElementById('next');
            var current = 0;

            function showSlide(index) {
                current = Math.max(0, Math.min(index, slides.length - 1));
                slides.forEach(function (slide, slideIndex) {
                    var active = slideIndex === current;
                    slide.classList.toggle('active', active);
                    slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                });
                counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
                progress.style.width = ((current + 1) / slides.length * 100) + '%';
                progressTrack.setAttribute('aria-valuemax', String(slides.length));
                progressTrack.setAttribute('aria-valuenow', String(current + 1));
                previous.disabled = current === 0;
                next.disabled = current === slides.length - 1;
                document.title = (current + 1) + '. ' + slides[current].querySelector('h1, h2').textContent + ' — Student Portal';
            }

            previous.addEventListener('click', function () { showSlide(current - 1); });
            next.addEventListener('click', function () { showSlide(current + 1); });

            document.addEventListener('keydown', function (event) {
                if (event.altKey || event.ctrlKey || event.metaKey) return;
                if (event.key === 'ArrowRight' || event.key === 'ArrowDown' || event.key === ' ') {
                    event.preventDefault();
                    showSlide(current + 1);
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    showSlide(current - 1);
                } else if (event.key === 'Home') {
                    showSlide(0);
                } else if (event.key === 'End') {
                    showSlide(slides.length - 1);
                }
            });

            showSlide(0);
        }());
    </script>
</body>
</html>

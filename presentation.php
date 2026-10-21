<?php
/**
 * DFFE INTERNSHIP TECHNICAL ASSESSMENT - EXECUTIVE PRESENTATION SYSTEM
 * 
 * Host Department: Department of Forestry, Fisheries and the Environment (DFFE)
 * Branch & Directorate: Corporate Management Services (CMS) — Directorate: IT Business Solutions (ITBS)
 * Project: Student Portal System – Academic Management & Success Platform
 * Lead Developer & System Architect: Mr Avuyile Mthembu
 * Database Administrator: Ms Nomthandazo Khoza
 * Frontend Developer: Ms Qhama Nodada
 * Technical Mentor: Senior Specialist: Software Architecture & Systems Development (ITBS)
 */

// Error handling: Clean display
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

// Safe configuration loading with fallback metrics
$db_connected = false;
$stats = [
    'students'  => 12,
    'courses'   => 4,
    'modules'   => 14,
    'results'   => 48,
    'lecturers' => 5
];

if (file_exists(__DIR__ . '/config.php')) {
    try {
        @include_once __DIR__ . '/config.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $db_connected = true;
            $sCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $cCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
            $mCount = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
            $lCount = $pdo->query("SELECT COUNT(*) FROM lecturers")->fetchColumn();
            $rCount = $pdo->query("SELECT COUNT(*) FROM module_results")->fetchColumn();
            if ($sCount !== false) $stats['students'] = (int)$sCount;
            if ($cCount !== false) $stats['courses'] = (int)$cCount;
            if ($mCount !== false) $stats['modules'] = (int)$mCount;
            if ($lCount !== false) $stats['lecturers'] = (int)$lCount;
            if ($rCount !== false) $stats['results'] = (int)$rCount;
        }
    } catch (Throwable $e) {
        $db_connected = false;
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', './');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#073823">
    <title>Executive Briefing & Technical Defense | Student Portal System - DFFE</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons & FontAwesome for Crisp Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            /* DFFE Identity Colors & Modern Palette */
            --color-dffe-green: #08492e;
            --color-dffe-dark: #042416;
            --color-dffe-emerald: #107c41;
            --color-dffe-mint: #22c55e;
            --color-dffe-gold: #c28816;
            --color-dffe-gold-light: #fef3c7;
            --color-slate-900: #0f172a;
            --color-slate-800: #1e293b;
            --color-slate-700: #334155;
            --color-slate-600: #475569;
            --color-slate-500: #64748b;
            --color-slate-100: #f1f5f9;
            --color-slate-50: #f8fafc;
            --color-card-bg: #ffffff;
            --color-card-border: #e2e8f0;
            --color-primary-text: #0f172a;
            --color-secondary-text: #475569;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background: #f8fafc;
            color: var(--color-primary-text);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Presentation Navigation Bar */
        .pres-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(4, 36, 22, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .pres-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: #ffffff;
        }

        .pres-brand-logo {
            height: 38px;
            width: auto;
            max-width: 140px;
            object-fit: contain;
            border-radius: 4px;
            background: #ffffff;
            padding: 2px 6px;
        }

        .pres-brand-text h1 {
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
            line-height: 1.2;
        }

        .pres-brand-text p {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 400;
        }

        .pres-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pres-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #e2e8f0;
            font-family: var(--font-sans);
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition-smooth);
            text-decoration: none;
        }

        .pres-btn:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .pres-btn.active {
            background: var(--color-dffe-emerald);
            border-color: var(--color-dffe-mint);
            color: #ffffff;
        }

        .pres-btn-gold {
            background: linear-gradient(135deg, #d97706, #b45309);
            border-color: #f59e0b;
            color: #ffffff;
            font-weight: 600;
        }
        .pres-btn-gold:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
        }

        .timer-badge {
            font-family: var(--font-mono);
            font-size: 0.82rem;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            color: #fde047;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Presentation Mode Progress Bar */
        .progress-container {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #107c41, #22c55e, #eab308);
            width: 12.5%;
            transition: width 0.35s ease;
        }

        /* Presentation Main Container */
        .presentation-stage {
            max-width: 1380px;
            margin: 0 auto;
            padding: 1.5rem 1.5rem 5rem 1.5rem;
        }

        /* Slide Deck View Styles */
        .slides-wrapper {
            position: relative;
            min-height: calc(100vh - 140px);
        }

        .slide {
            display: none;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.35s ease, transform 0.35s ease;
            background: var(--color-card-bg);
            border-radius: 20px;
            border: 1px solid var(--color-card-border);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            padding: 2.5rem 3rem;
            margin-bottom: 2rem;
        }

        .slide.active-slide {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Executive Briefing Mode: Displays all slides continuously */
        body.briefing-mode .slide {
            display: block !important;
            opacity: 1 !important;
            transform: none !important;
            margin-bottom: 3.5rem;
            scroll-margin-top: 80px;
        }

        body.briefing-mode .deck-only {
            display: none !important;
        }

        body.briefing-mode .briefing-toc {
            display: block;
        }

        .briefing-toc {
            display: none;
            background: #ffffff;
            border: 1px solid var(--color-card-border);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .briefing-toc h3 {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-dffe-green);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .briefing-toc ul {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.5rem;
            list-style: none;
        }

        .briefing-toc a {
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-slate-700);
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition-smooth);
        }

        .briefing-toc a:hover {
            background: var(--color-slate-100);
            color: var(--color-dffe-emerald);
        }

        /* Slide Header */
        .slide-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 1.25rem;
            margin-bottom: 2rem;
            gap: 1.5rem;
        }

        .slide-tagline {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.3rem 0.75rem;
            border-radius: 9999px;
            background: var(--color-dffe-gold-light);
            color: var(--color-dffe-gold);
            margin-bottom: 0.5rem;
        }

        .slide-tagline.green {
            background: #dcfce7;
            color: #15803d;
        }

        .slide-tagline.blue {
            background: #e0f2fe;
            color: #0369a1;
        }

        .slide-tagline.purple {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .slide-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-slate-900);
            line-height: 1.2;
        }

        .slide-subtitle {
            font-size: 1.05rem;
            color: var(--color-slate-600);
            margin-top: 0.35rem;
        }

        .slide-badge-meta {
            text-align: right;
            flex-shrink: 0;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #0f172a;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-family: var(--font-mono);
        }

        /* Slide Content Cards Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .card-panel {
            background: #ffffff;
            border: 1px solid var(--color-card-border);
            border-radius: 14px;
            padding: 1.5rem;
            transition: var(--transition-smooth);
            position: relative;
        }

        .card-panel:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        .card-panel.highlight {
            background: linear-gradient(145deg, #ffffff, #f0fdf4);
            border-color: #86efac;
        }

        .card-panel.dark {
            background: #0f172a;
            color: #f8fafc;
            border-color: #1e293b;
        }

        .card-panel.dark h3, .card-panel.dark h4 {
            color: #ffffff;
        }

        .card-panel.dark p, .card-panel.dark li {
            color: #cbd5e1;
        }

        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #f1f5f9;
            color: var(--color-dffe-emerald);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }

        .card-icon.gold {
            background: #fef3c7;
            color: #b45309;
        }

        .card-icon.blue {
            background: #e0f2fe;
            color: #0284c7;
        }

        .card-icon.green {
            background: #dcfce7;
            color: #16a34a;
        }

        .card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--color-slate-900);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-desc {
            font-size: 0.9rem;
            color: var(--color-slate-600);
            line-height: 1.55;
        }

        /* Metrics Strip */
        .metric-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .metric-box {
            background: #f8fafc;
            border: 1px solid var(--color-card-border);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-align: center;
            transition: var(--transition-smooth);
        }

        .metric-box:hover {
            transform: translateY(-2px);
            background: #ffffff;
            box-shadow: var(--shadow-sm);
            border-color: var(--color-dffe-emerald);
        }

        .metric-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--color-dffe-green);
            line-height: 1.1;
            font-family: var(--font-sans);
        }

        .metric-value.gold {
            color: #d97706;
        }

        .metric-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-slate-500);
            margin-top: 0.35rem;
        }

        /* Comparison Table & Data Tables */
        .data-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--color-card-border);
            background: #ffffff;
            margin: 1rem 0;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        table.data-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
            padding: 0.75rem 1rem;
            letter-spacing: 0.02em;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        table.data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--color-slate-700);
            vertical-align: middle;
        }

        table.data-table tr:last-child td {
            border-bottom: none;
        }

        table.data-table tr:hover td {
            background: #f8fafc;
        }

        .badge-pill {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-blue  { background: #e0f2fe; color: #0369a1; }
        .badge-gold  { background: #fef3c7; color: #b45309; }
        .badge-purple{ background: #f3e8ff; color: #7e22ce; }
        .badge-slate { background: #f1f5f9; color: #334155; }

        /* Code & Architecture Boxes */
        .code-box {
            background: #0b1120;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem;
            font-family: var(--font-mono);
            font-size: 0.82rem;
            line-height: 1.6;
            overflow-x: auto;
            border: 1px solid #1e293b;
        }

        .code-box .kw  { color: #f43f5e; font-weight: 600; }
        .code-box .fn  { color: #38bdf8; }
        .code-box .str { color: #a3e635; }
        .code-box .cm  { color: #64748b; font-style: italic; }
        .code-box .var { color: #f59e0b; }

        /* Interactive Tabs inside Slides */
        .tab-nav {
            display: flex;
            gap: 0.4rem;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.25rem;
            overflow-x: auto;
        }

        .tab-btn {
            background: none;
            border: none;
            font-family: var(--font-sans);
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--color-slate-600);
            padding: 0.6rem 1rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--color-dffe-green);
        }

        .tab-btn.active {
            color: var(--color-dffe-green);
            border-bottom-color: var(--color-dffe-emerald);
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Timeline Gantt / Day-by-Day Visualizer */
        .timeline-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
            margin: 1.25rem 0;
        }

        .timeline-day {
            background: #ffffff;
            border: 1px solid var(--color-card-border);
            border-radius: 10px;
            padding: 1rem 0.75rem;
            border-top: 4px solid var(--color-dffe-emerald);
            transition: var(--transition-smooth);
        }

        .timeline-day:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .timeline-day.week2 {
            border-top-color: #d97706;
        }

        .timeline-day-header {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--color-slate-800);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
        }

        .timeline-day-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--color-dffe-green);
            margin-bottom: 0.25rem;
        }

        .timeline-day-desc {
            font-size: 0.75rem;
            color: var(--color-slate-600);
            line-height: 1.4;
        }

        /* Slide Bottom Navigation Bar (Deck Mode) */
        .deck-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--color-card-border);
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
        }

        .deck-dots {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dot-btn {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #cbd5e1;
            border: none;
            cursor: pointer;
            transition: var(--transition-smooth);
            padding: 0;
        }

        .dot-btn.active {
            width: 28px;
            background: var(--color-dffe-emerald);
        }

        .slide-counter {
            font-family: var(--font-mono);
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--color-slate-700);
        }

        .deck-nav-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Speaker Talking Points Drawer Modal */
        .notes-drawer {
            position: fixed;
            bottom: 60px;
            right: 20px;
            width: 440px;
            max-width: 90vw;
            background: #0f172a;
            color: #f8fafc;
            border-radius: 14px;
            box-shadow: var(--shadow-xl);
            border: 1px solid #334155;
            padding: 1.5rem;
            z-index: 1001;
            display: none;
            transform: translateY(10px);
            transition: var(--transition-smooth);
        }

        .notes-drawer.open {
            display: block;
            transform: translateY(0);
        }

        .notes-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }

        .notes-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fde047;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notes-content {
            font-size: 0.86rem;
            color: #cbd5e1;
            line-height: 1.6;
            max-height: 300px;
            overflow-y: auto;
        }

        .notes-content ul {
            padding-left: 1.25rem;
            margin-top: 0.5rem;
        }

        .notes-content li {
            margin-bottom: 0.5rem;
        }

        /* Slide Overview Grid Modal */
        .overview-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .overview-modal.open {
            display: flex;
        }

        .overview-container {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 1100px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 2rem;
            box-shadow: var(--shadow-xl);
        }

        .overview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }

        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
        }

        .overview-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            background: #f8fafc;
        }

        .overview-card:hover {
            border-color: var(--color-dffe-emerald);
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .overview-card.current {
            border-color: var(--color-dffe-green);
            background: #f0fdf4;
        }

        .overview-card-num {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-dffe-emerald);
            margin-bottom: 0.25rem;
        }

        .overview-card-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--color-slate-900);
            line-height: 1.3;
        }

        /* Demo Credentials Quick Switcher Box */
        .demo-bar {
            background: #0f172a;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
            border: 1px solid #1e293b;
        }

        .demo-roles {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .demo-role-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }

        .demo-role-pill strong {
            color: #38bdf8;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .timeline-grid { grid-template-columns: repeat(3, 1fr); }
            .slide { padding: 2rem 1.5rem; }
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .timeline-grid { grid-template-columns: 1fr 1fr; }
            .pres-nav { flex-wrap: wrap; gap: 0.5rem; }
            .slide-header { flex-direction: column; }
            .slide-title { font-size: 1.5rem; }
            .deck-footer { padding: 0.5rem 1rem; }
            .deck-dots { display: none; }
        }

        /* Print Media Styles: Formats as clean executive report */
        @media print {
            .pres-nav, .deck-footer, .deck-only, .notes-drawer, .overview-modal {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .slide {
                display: block !important;
                opacity: 1 !important;
                break-inside: avoid;
                page-break-after: always;
                border: 1px solid #cccccc !important;
                box-shadow: none !important;
                margin-bottom: 2rem !important;
                padding: 1.5rem !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOP PRESENTATION NAV -->
    <header class="pres-nav">
        <a href="index.php" class="pres-brand" title="Return to Portal Login">
            <?php if (file_exists(__DIR__ . '/assets/images/logo.png')): ?>
                <img src="assets/images/logo.png" alt="DFFE Crest" class="pres-brand-logo">
            <?php else: ?>
                <div class="pres-brand-logo" style="display:flex;align-items:center;justify-content:center;font-weight:800;color:#08492e;font-size:0.8rem;">DFFE</div>
            <?php endif; ?>
            <div class="pres-brand-text">
                <h1>Student Portal System</h1>
                <p>DFFE ITBS • Technical Assessment Briefing & Defense</p>
            </div>
        </a>

        <div class="pres-controls">
            <!-- Mode Switcher -->
            <button id="modeToggleBtn" class="pres-btn" onclick="togglePresentationMode()" title="Toggle between Slide Deck and Executive Reading Dossier (Key: M)">
                <i class="material-icons-round" style="font-size:16px;">view_carousel</i>
                <span id="modeLabel">Reading Mode</span>
            </button>

            <!-- Rehearsal Timer -->
            <div class="timer-badge" title="Presentation Stopwatch (Key: T to start/pause)">
                <i class="material-icons-round" style="font-size:15px;color:#fde047;">timer</i>
                <span id="timerDisplay">00:00</span>
                <span id="timerToggle" onclick="toggleTimer()" style="cursor:pointer;font-size:11px;opacity:0.85;">▶</span>
            </div>

            <!-- Speaker Talking Points -->
            <button class="pres-btn" onclick="toggleNotes()" title="Presenter Verbal Talking Points (Key: S)">
                <i class="material-icons-round" style="font-size:16px;">mic</i>
                <span>Speaker Notes</span>
            </button>

            <!-- Overview Drawer -->
            <button class="pres-btn deck-only" onclick="toggleOverview()" title="Slide Navigation Grid (Key: O)">
                <i class="material-icons-round" style="font-size:16px;">grid_view</i>
                <span>Agenda</span>
            </button>

            <!-- Fullscreen -->
            <button class="pres-btn deck-only" onclick="toggleFullscreen()" title="Fullscreen Presentation (Key: F)">
                <i class="material-icons-round" style="font-size:16px;">fullscreen</i>
            </button>

            <!-- Jump to Live System -->
            <a href="index.php" target="_blank" class="pres-btn pres-btn-gold" title="Open Live Portal in new tab">
                <i class="material-icons-round" style="font-size:16px;">launch</i>
                <span>Live Portal</span>
            </a>
        </div>
    </header>

    <!-- Slide Deck Progress Bar -->
    <div class="progress-container deck-only">
        <div id="progressBar" class="progress-bar"></div>
    </div>

    <!-- MAIN PRESENTATION STAGE -->
    <main class="presentation-stage">

        <!-- Executive Reading Table of Contents (Shown in Briefing Mode) -->
        <nav class="briefing-toc">
            <h3><i class="material-icons-round" style="font-size:18px;">menu_book</i> Executive Briefing Dossier: Table of Contents</h3>
            <ul>
                <li><a href="#slide-1"><strong>01.</strong> Executive Title & Project Charter</a></li>
                <li><a href="#slide-2"><strong>02.</strong> 01_PMP: 2-Week Sprint & Cost Avoidance</a></li>
                <li><a href="#slide-3"><strong>03.</strong> 02_Systems Analysis: As-Is vs To-Be & DFDs</a></li>
                <li><a href="#slide-4"><strong>04.</strong> 03_SRS: 18 IEEE 830 Functional Rules</a></li>
                <li><a href="#slide-5"><strong>05.</strong> 04_Architecture: OOP Domain & UML Models</a></li>
                <li><a href="#slide-6"><strong>06.</strong> 05_Database: Relational Schema & 3NF Normalization</a></li>
                <li><a href="#slide-7"><strong>07.</strong> Technical Innovations: AI Advisor & Auto-Numbering</a></li>
                <li><a href="#slide-8"><strong>08.</strong> Live System Verification & Assessment Sign-off</a></li>
            </ul>
        </nav>

        <div class="slides-wrapper">

            <!-- ==========================================
                 SLIDE 1: EXECUTIVE OVERVIEW & PROJECT CHARTER
                 ========================================== -->
            <section id="slide-1" class="slide active-slide" data-slide="1" data-notes="Good morning esteemed mentors and technical evaluators. Today we are presenting the Student Portal System, an enterprise-grade academic management platform delivered during our intensive 2-week software engineering assessment at DFFE ITBS. Our core mandate was to apply formal engineering principles across the entire SDLC: 3NF database normalization, clean PHP 8 OOP domain architecture, role-based governance, and generative AI success coaching on a sub-R1,000 budget.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline green"><i class="material-icons-round" style="font-size:14px;">verified</i> DFFE ITBS Technical Competency Assessment</span>
                        <h2 class="slide-title">Academic Management Platform & Student Portal</h2>
                        <p class="slide-subtitle">Full-Stack Object-Oriented Architecture, 3NF Relational Database & AI Pedagogical Success Coach</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">EXECUTIVE BRIEFING</span>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card-panel highlight">
                        <div class="card-icon"><i class="material-icons-round">account_balance</i></div>
                        <h3 class="card-title">Project Charter & Mandate</h3>
                        <p class="card-desc">
                            Commissioned by the <strong>Directorate: IT Business Solutions (ITBS)</strong> within Corporate Management Services (CMS) at the Department of Forestry, Fisheries and the Environment (DFFE).
                        </p>
                        <div style="margin-top:1rem;font-size:0.86rem;color:var(--color-slate-700);">
                            <p style="margin-bottom:0.35rem;">• <strong>Host Unit:</strong> ICT Services: Infrastructure and Security Support</p>
                            <p style="margin-bottom:0.35rem;">• <strong>Assessment Nature:</strong> Practical Software Engineering & Competency Demonstration</p>
                            <p style="margin-bottom:0.35rem;">• <strong>Sprint Duration:</strong> 2 Calendar Weeks (10 Intensive Working Days)</p>
                            <p>• <strong>Technical Mentor:</strong> Senior Specialist: Software Architecture & Systems Development (ITBS)</p>
                        </div>
                    </div>

                    <div class="card-panel">
                        <div class="card-icon gold"><i class="material-icons-round">groups</i></div>
                        <h3 class="card-title">Internship Engineering Team</h3>
                        <div style="font-size:0.88rem;color:var(--color-slate-700);">
                            <div style="padding:0.5rem 0;border-bottom:1px solid #f1f5f9;">
                                <strong>Mr Avuyile Mthembu</strong> — <span class="badge-pill badge-green">Team Lead & Architect</span>
                                <div style="font-size:0.78rem;color:var(--color-slate-500);margin-top:2px;">Full-Stack OOP Domain Models, Services, Bcrypt Security, OpenRouter AI, System Deployment</div>
                            </div>
                            <div style="padding:0.5rem 0;border-bottom:1px solid #f1f5f9;">
                                <strong>Ms Nomthandazo Khoza</strong> — <span class="badge-pill badge-blue">Database Administrator</span>
                                <div style="font-size:0.78rem;color:var(--color-slate-500);margin-top:2px;">3NF Relational Normalization, Schema Provisioning, Migration Scripts, Referential Constraints</div>
                            </div>
                            <div style="padding:0.5rem 0;">
                                <strong>Ms Qhama Nodada</strong> — <span class="badge-pill badge-purple">Frontend Developer</span>
                                <div style="font-size:0.78rem;color:var(--color-slate-500);margin-top:2px;">Material Design UI, Multi-Role Switcher, Real-Time Search Filters, jsPDF Document Engine</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Metrics Strip -->
                <div class="metric-strip">
                    <div class="metric-box">
                        <div class="metric-value">10 Days</div>
                        <div class="metric-label">Rapid Agile Sprint</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value gold">&lt; R 1,000</div>
                        <div class="metric-label">Actual Project Spend</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value">~ R 48.4k</div>
                        <div class="metric-label">FOSS Cost Avoidance</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value">18 FRs</div>
                        <div class="metric-label">IEEE 830 Requirements</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value">8 Tables</div>
                        <div class="metric-label">3NF Normalized Relational DB</div>
                    </div>
                    <div class="metric-box">
                        <div class="metric-value">100%</div>
                        <div class="metric-label">Backward Compatibility</div>
                    </div>
                </div>

                <!-- 5-Step Pipeline Breadcrumb -->
                <div style="background:#0f172a;color:#ffffff;border-radius:12px;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                    <div style="font-size:0.85rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#38bdf8;">
                        <i class="material-icons-round" style="font-size:15px;vertical-align:middle;">account_tree</i> 5-Step Engineering Pipeline:
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;font-size:0.78rem;font-family:var(--font-mono);">
                        <span style="background:rgba(255,255,255,0.1);padding:0.25rem 0.6rem;border-radius:6px;">01. PMP</span> →
                        <span style="background:rgba(255,255,255,0.1);padding:0.25rem 0.6rem;border-radius:6px;">02. Analysis</span> →
                        <span style="background:rgba(255,255,255,0.1);padding:0.25rem 0.6rem;border-radius:6px;">03. SRS</span> →
                        <span style="background:rgba(255,255,255,0.1);padding:0.25rem 0.6rem;border-radius:6px;">04. Architecture</span> →
                        <span style="background:rgba(255,255,255,0.1);padding:0.25rem 0.6rem;border-radius:6px;">05. Database 3NF</span>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 2: 01_PROJECT_MANAGEMENT_PLAN.md
                 ========================================== -->
            <section id="slide-2" class="slide" data-slide="2" data-notes="Document 1 of our pipeline is the Project Management Plan. We adhered strictly to the sprint schedule across 10 working days, divided into Foundations in Week 1 and Implementation & Go-Live in Week 2. Crucially, we achieved our financial objective: keeping actual expenditure at R750 for data and backups, while avoiding over R48,400 in commercial software licensing costs by using FOSS stacks like Apache, MariaDB, PHP 8, and OpenRouter Free Tier.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline blue"><i class="material-icons-round" style="font-size:14px;">assignment</i> Pipeline Step 01</span>
                        <h2 class="slide-title">Project Management Plan (PMP) & Cost Governance</h2>
                        <p class="slide-subtitle">Agile 10-Day Sprint Schedule, Work Breakdown Structure (WBS) & Sub-R1,000 Budget Model</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">01_PROJECT_MANAGEMENT_PLAN.md</span>
                    </div>
                </div>

                <!-- 10-Day Sprint Visual Timeline -->
                <h3 style="font-size:1.1rem;font-weight:700;color:var(--color-slate-900);margin-bottom:0.5rem;display:flex;align-items:center;gap:0.4rem;">
                    <i class="material-icons-round" style="font-size:18px;color:var(--color-dffe-emerald);">date_range</i> 2-Week Intensive Sprint Schedule (10 Working Days)
                </h3>
                
                <div class="timeline-grid">
                    <div class="timeline-day">
                        <div class="timeline-day-header"><span>DAY 1</span><span class="badge-pill badge-green">WK 1</span></div>
                        <div class="timeline-day-title">Charter & Audit</div>
                        <div class="timeline-day-desc">Kickoff, problem scope, legacy single-table audit, project charter baseline.</div>
                    </div>
                    <div class="timeline-day">
                        <div class="timeline-day-header"><span>DAY 2</span><span class="badge-pill badge-green">WK 1</span></div>
                        <div class="timeline-day-title">IEEE 830 SRS</div>
                        <div class="timeline-day-desc">Elicited FR-01 through FR-18, actor use cases, security and non-functional rules.</div>
                    </div>
                    <div class="timeline-day">
                        <div class="timeline-day-header"><span>DAY 3</span><span class="badge-pill badge-green">WK 1</span></div>
                        <div class="timeline-day-title">System Modeling</div>
                        <div class="timeline-day-desc">3NF relational normalization proofs, UML Class diagram modeling, UI wireframes.</div>
                    </div>
                    <div class="timeline-day">
                        <div class="timeline-day-header"><span>DAY 4</span><span class="badge-pill badge-green">WK 1</span></div>
                        <div class="timeline-day-title">3NF DB Schema</div>
                        <div class="timeline-day-desc">Provisioned `university_db`, written migration_v2.sql with cascading FKs.</div>
                    </div>
                    <div class="timeline-day">
                        <div class="timeline-day-header"><span>DAY 5</span><span class="badge-pill badge-green">WK 1</span></div>
                        <div class="timeline-day-title">PHP 8 OOP Layer</div>
                        <div class="timeline-day-desc">Engineered core domain entities, PDO connection service, and Bcrypt auth.</div>
                    </div>
                    <div class="timeline-day week2">
                        <div class="timeline-day-header"><span>DAY 6</span><span class="badge-pill badge-gold">WK 2</span></div>
                        <div class="timeline-day-title">Admin Governance</div>
                        <div class="timeline-day-desc">Implemented RegistrationService, auto-student numbering (`ST2600xxx`), and curriculum.</div>
                    </div>
                    <div class="timeline-day week2">
                        <div class="timeline-day-header"><span>DAY 7</span><span class="badge-pill badge-gold">WK 2</span></div>
                        <div class="timeline-day-title">Lecturer Portal</div>
                        <div class="timeline-day-desc">Role segregation, teaching assignments, quarterly mark capture (Q1–Q4).</div>
                    </div>
                    <div class="timeline-day week2">
                        <div class="timeline-day-header"><span>DAY 8</span><span class="badge-pill badge-gold">WK 2</span></div>
                        <div class="timeline-day-title">Student & jsPDF</div>
                        <div class="timeline-day-desc">Self-service dashboard, results inspection, client-side PDF transcript exports.</div>
                    </div>
                    <div class="timeline-day week2">
                        <div class="timeline-day-header"><span>DAY 9</span><span class="badge-pill badge-gold">WK 2</span></div>
                        <div class="timeline-day-title">AI Coach & QA</div>
                        <div class="timeline-day-desc">OpenRouter API integration, offline heuristic fallback, backward compatibility smoke tests.</div>
                    </div>
                    <div class="timeline-day week2">
                        <div class="timeline-day-header"><span>DAY 10</span><span class="badge-pill badge-gold">WK 2</span></div>
                        <div class="timeline-day-title">Cloud Go-Live</div>
                        <div class="timeline-day-desc">Production deployment to InfinityFree, SSL verification, documentation sign-off.</div>
                    </div>
                </div>

                <!-- Financial Cost-Avoidance Model -->
                <div class="grid-2" style="margin-top:1.25rem;">
                    <div class="card-panel">
                        <h3 class="card-title"><i class="material-icons-round" style="color:#d97706;">savings</i> FOSS Cost Avoidance Model</h3>
                        <p class="card-desc" style="margin-bottom:0.75rem;">
                            By selecting vetted open-source components and free cloud tiers, our team saved DFFE thousands in software licensing:
                        </p>
                        <div class="data-table-wrapper" style="margin:0;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Commercial Alternative</th>
                                        <th>Chosen Solution</th>
                                        <th>Cost Avoided</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Web Server</strong></td>
                                        <td>MS Windows IIS License</td>
                                        <td>Apache 2.4 (AMPPS FOSS)</td>
                                        <td><span class="badge-pill badge-green">R 12,000</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database</strong></td>
                                        <td>Oracle / MS SQL Server</td>
                                        <td>MySQL 8.0 / MariaDB</td>
                                        <td><span class="badge-pill badge-green">R 18,500</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>AI Engine</strong></td>
                                        <td>Commercial Paid GPT-4 API</td>
                                        <td>OpenRouter Free Tier</td>
                                        <td><span class="badge-pill badge-green">R 3,500</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PDF Engine</strong></td>
                                        <td>Server-side PDF Library</td>
                                        <td>Open-source jsPDF</td>
                                        <td><span class="badge-pill badge-green">R 4,200</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hosting & SSL</strong></td>
                                        <td>Dedicated Cloud VM</td>
                                        <td>InfinityFree + Host SSL</td>
                                        <td><span class="badge-pill badge-green">R 4,200</span></td>
                                    </tr>
                                    <tr style="background:#f0fdf4;font-weight:700;">
                                        <td colspan="3"><strong>TOTAL COMMERCIAL COST AVOIDANCE</strong></td>
                                        <td><strong style="color:#15803d;">~ R 48,400.00</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-panel">
                        <h3 class="card-title"><i class="material-icons-round" style="color:#107c41;">paid</i> Actual Project Outflow: R 750 ZAR</h3>
                        <p class="card-desc">
                            Demonstrated fiscal responsibility by delivering production capability while remaining strictly below the R1,000 ceiling.
                        </p>
                        <div style="margin-top:1rem;display:flex;flex-direction:column;gap:0.75rem;">
                            <div style="background:#f8fafc;padding:0.75rem 1rem;border-radius:8px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                <span>Connectivity (2 x 10GB Data Packages)</span>
                                <strong>R 350.00</strong>
                            </div>
                            <div style="background:#f8fafc;padding:0.75rem 1rem;border-radius:8px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                <span>Testing & DNS Prototyping Assets</span>
                                <strong>R 250.00</strong>
                            </div>
                            <div style="background:#f8fafc;padding:0.75rem 1rem;border-radius:8px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                <span>Encrypted Hardware Backup Snapshot Storage</span>
                                <strong>R 150.00</strong>
                            </div>
                            <div style="background:#0f172a;color:#ffffff;padding:0.85rem 1rem;border-radius:8px;display:flex;justify-content:space-between;align-items:center;font-weight:700;">
                                <span>TOTAL ACTUAL INVENTORY COST</span>
                                <span style="color:#4ade80;">R 750.00 ZAR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 3: 02_SYSTEMS_ANALYSIS_AND_PROCESS_SPECIFICATION.md
                 ========================================== -->
            <section id="slide-3" class="slide" data-slide="3" data-notes="Document 2 addresses Systems Analysis. We evaluated the legacy single flat table which suffered from severe data duplication, lack of foreign keys, and zero role separation. We conducted a TELOS feasibility study—confirming technical, economic, legal, operational, and schedule viability. We decomposed the system into DFD Level 0 Context and DFD Level 1 Sub-processes, ensuring complete traceability back to the requirements matrix.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline green"><i class="material-icons-round" style="font-size:14px;">analytics</i> Pipeline Step 02</span>
                        <h2 class="slide-title">Systems Analysis & Process Specification</h2>
                        <p class="slide-subtitle">TELOS Feasibility, As-Is vs To-Be Process Transformation & Data Flow Diagrams (DFDs)</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">02_SYSTEMS_ANALYSIS_AND_PROCESS_SPECIFICATION.md</span>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- As-Is vs To-Be Comparison -->
                    <div class="card-panel">
                        <h3 class="card-title"><i class="material-icons-round" style="color:#ef4444;">compare_arrows</i> As-Is vs To-Be Paradigm Shift</h3>
                        <div class="data-table-wrapper" style="margin-top:0.75rem;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Attribute</th>
                                        <th>Legacy "As-Is" Model</th>
                                        <th>Modern "To-Be" Platform</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Architecture</strong></td>
                                        <td>Single denormalized flat file</td>
                                        <td><span class="badge-pill badge-green">3-Tier Layered Architecture</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Data Normalization</strong></td>
                                        <td>Unnormalized (Duplicate text)</td>
                                        <td><span class="badge-pill badge-green">3NF Relational Model</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role Access</strong></td>
                                        <td>Single generic view</td>
                                        <td><span class="badge-pill badge-blue">Admin, Lecturer, Student</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Security</strong></td>
                                        <td>Plain text / un-salted</td>
                                        <td><span class="badge-pill badge-green">Bcrypt Salted + CSRF + Session Regen</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Analytics & AI</strong></td>
                                        <td>None (Static lists)</td>
                                        <td><span class="badge-pill badge-gold">AI Success Coach + Fallback</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Exports</strong></td>
                                        <td>Manual screen-print</td>
                                        <td><span class="badge-pill badge-purple">Instant jsPDF Documents</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TELOS Feasibility -->
                    <div class="card-panel highlight">
                        <h3 class="card-title"><i class="material-icons-round" style="color:#107c41;">check_circle</i> TELOS Feasibility Evaluation</h3>
                        <p class="card-desc" style="margin-bottom:0.75rem;">
                            All 5 engineering dimensions evaluated and fully verified:
                        </p>
                        <div style="font-size:0.86rem;color:var(--color-slate-700);display:flex;flex-direction:column;gap:0.5rem;">
                            <div style="padding:0.4rem 0.6rem;background:#ffffff;border-radius:6px;border:1px solid #e2e8f0;">
                                <strong>[T] Technical:</strong> Verified LAMP/WAMP compatibility, PHP 8 OOP, and responsive Material Design across viewports.
                            </div>
                            <div style="padding:0.4rem 0.6rem;background:#ffffff;border-radius:6px;border:1px solid #e2e8f0;">
                                <strong>[E] Economic:</strong> Total actual expenditure R 750.00 (&lt; R 1,000 budget), yielding ~R48,400 in cost avoidance.
                            </div>
                            <div style="padding:0.4rem 0.6rem;background:#ffffff;border-radius:6px;border:1px solid #e2e8f0;">
                                <strong>[L] Legal & Security:</strong> Compliance with South African POPIA; zero external exposure of student PII; salted bcrypt password storage.
                            </div>
                            <div style="padding:0.4rem 0.6rem;background:#ffffff;border-radius:6px;border:1px solid #e2e8f0;">
                                <strong>[O] Operational:</strong> Low cognitive barrier with tabbed demo credential switcher and automated student auto-numbering.
                            </div>
                            <div style="padding:0.4rem 0.6rem;background:#ffffff;border-radius:6px;border:1px solid #e2e8f0;">
                                <strong>[S] Schedule:</strong> Delivered all 5 phases strictly within the 10-day sprint deadline.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DFD Architecture Flow -->
                <div class="card-panel dark" style="margin-top:1.25rem;">
                    <h3 class="card-title" style="color:#38bdf8;"><i class="material-icons-round" style="color:#38bdf8;">hub</i> DFD Level 0 Context & Level 1 System Decomposition</h3>
                    <div class="grid-3" style="margin-top:1rem;gap:1rem;">
                        <div style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.1);">
                            <h4 style="color:#4ade80;font-size:0.92rem;margin-bottom:0.4rem;">1.0 Auth & Governance</h4>
                            <p style="font-size:0.8rem;color:#cbd5e1;">Bcrypt password verification with 22-char unique salt; session fixation mitigation via `session_regenerate_id(true)`.</p>
                        </div>
                        <div style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.1);">
                            <h4 style="color:#38bdf8;font-size:0.92rem;margin-bottom:0.4rem;">2.0 Auto-Numbering Engine</h4>
                            <p style="font-size:0.8rem;color:#cbd5e1;">Atomic generation of institutional student numbers: `ST` + year (26) + 5-digit sequence with database uniqueness checks.</p>
                        </div>
                        <div style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.1);">
                            <h4 style="color:#fde047;font-size:0.92rem;margin-bottom:0.4rem;">3.0 AI Academic Success Coach</h4>
                            <p style="font-size:0.8rem;color:#cbd5e1;">One-click diagnostics parsing Q1-Q4 marks, bound to academic syllabus improvement, backed by offline heuristic rules.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 4: 03_SOFTWARE_REQUIREMENTS_SPECIFICATION.md
                 ========================================== -->
            <section id="slide-4" class="slide" data-slide="4" data-notes="Document 3 defines our formal IEEE 830 Software Requirements Specification. We formulated 18 functional requirements across 7 core modules. We clearly established the separation of responsibilities: Administrators govern curriculum and register students; Lecturers review class rosters and enter quarterly marks; and Students log in to inspect their progress, chat with the AI coach, and export official PDF transcripts.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline purple"><i class="material-icons-round" style="font-size:14px;">description</i> Pipeline Step 03</span>
                        <h2 class="slide-title">Software Requirements Specification (SRS)</h2>
                        <p class="slide-subtitle">IEEE 830 Standard, 18 Functional Requirements & Role Segregation Matrix</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">03_SOFTWARE_REQUIREMENTS_SPECIFICATION.md</span>
                    </div>
                </div>

                <!-- Role Matrix Grid -->
                <div class="grid-3" style="margin-bottom:1.5rem;">
                    <div class="card-panel" style="border-top:4px solid #0284c7;">
                        <div class="card-icon blue"><i class="material-icons-round">admin_panel_settings</i></div>
                        <h3 class="card-title">Administrator Portal</h3>
                        <p class="card-desc">Full institutional governance and registrar management.</p>
                        <ul style="font-size:0.82rem;color:var(--color-slate-600);margin-top:0.75rem;padding-left:1.1rem;line-height:1.55;">
                            <li>Student onboarding with auto-numbering (`ST2600001`)</li>
                            <li>Academic qualification & course creation</li>
                            <li>Module shared junction & lecturer allocation</li>
                            <li>Live multi-attribute directory search & filtering</li>
                        </ul>
                    </div>

                    <div class="card-panel" style="border-top:4px solid #107c41;">
                        <div class="card-icon green"><i class="material-icons-round">co_present</i></div>
                        <h3 class="card-title">Lecturer Portal</h3>
                        <p class="card-desc">Educator grading workflow & class roster oversight.</p>
                        <ul style="font-size:0.82rem;color:var(--color-slate-600);margin-top:0.75rem;padding-left:1.1rem;line-height:1.55;">
                            <li>View assigned modules and registered class rosters</li>
                            <li>Capture & update quarterly marks (Q1, Q2, Q3, Q4)</li>
                            <li>Strict 0.00 – 100.00 range validation enforcement</li>
                            <li>Real-time automated weighted composite average calculation</li>
                        </ul>
                    </div>

                    <div class="card-panel" style="border-top:4px solid #d97706;">
                        <div class="card-icon gold"><i class="material-icons-round">school</i></div>
                        <h3 class="card-title">Student Self-Service</h3>
                        <p class="card-desc">Personal academic empowerment & diagnostics.</p>
                        <ul style="font-size:0.82rem;color:var(--color-slate-600);margin-top:0.75rem;padding-left:1.1rem;line-height:1.55;">
                            <li>Inspect personal enrolled modules & lecturers</li>
                            <li>View quarterly results & running average scores</li>
                            <li>One-click AI diagnostic analysis & study coach chat</li>
                            <li>Instant jsPDF registration slips & academic transcripts</li>
                        </ul>
                    </div>
                </div>

                <!-- 18 Functional Requirements Tabs -->
                <div class="card-panel">
                    <h3 class="card-title"><i class="material-icons-round" style="color:var(--color-dffe-emerald);">format_list_bulleted</i> 18 Formal Functional Requirements (FR-01 to FR-18)</h3>
                    
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="switchReqTab(event, 'mod-auth')">Mod 1: Auth & Access</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-reg')">Mod 2: Registration</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-curr')">Mod 3: Curriculum</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-rules')">Mod 4: Business Rules</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-eval')">Mod 5: Marks & GPA</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-ai')">Mod 6: AI Advisory</button>
                        <button class="tab-btn" onclick="switchReqTab(event, 'mod-pdf')">Mod 7: Reporting</button>
                    </div>

                    <div id="mod-auth" class="tab-pane active">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-01: Cryptographic Authentication</strong> — Bcrypt hashing with PHP native 22-character pseudorandom salt per account, mitigating rainbow table and precomputed dictionary attacks.<br>
                            <strong>FR-02: Role-Based Redirection</strong> — Verified redirection to `/admin/dashboard.php`, `/lecturer/dashboard.php`, or `/student/dashboard.php`.<br>
                            <strong>FR-03: Session Fixation Prevention</strong> — `session_regenerate_id(true)` executed upon authentication.<br>
                            <strong>FR-04: Password Change Self-Service</strong> — Secure user-driven credential updating with minimum character length validation.
                        </p>
                    </div>

                    <div id="mod-reg" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-05: Automated Student Number Generation</strong> — `ST` + year (26) + 5-digit sequence (e.g. `ST2600001`) with atomic database uniqueness locks.<br>
                            <strong>FR-06: Manual Student ID Ingestion</strong> — Backward-compatible toggle permitting historical alphanumeric student ID onboarding.<br>
                            <strong>FR-07: Normalized Profile Management</strong> — Captures first name, surname, SA National ID, date of birth, email, and enrollment date with automatic synchronization to legacy columns.
                        </p>
                    </div>

                    <div id="mod-curr" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-08: Course & Qualification Governance</strong> — Managing degrees, course codes, titles, and academic durations.<br>
                            <strong>FR-09: Shared Course-Module Junction</strong> — Many-to-many relationship mapping (`course_modules`) permitting subjects to be shared across qualifications.<br>
                            <strong>FR-10: Multi-Lecturer Allocation</strong> — Supporting primary and co-lecturer assignments to academic modules (`module_lecturers`).
                        </p>
                    </div>

                    <div id="mod-rules" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-11: Single Active Course Rule</strong> — Strict business validation: a student may hold exactly one `'Active'` course enrollment at any given time (`Enrollment::validateSingleActive()`).<br>
                            <strong>FR-12: Permanent Transcript History</strong> — Completed, Suspended, or Withdrawn enrollments permanently preserved for academic records.
                        </p>
                    </div>

                    <div id="mod-eval" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-13: Quarterly Mark Entry & Validation</strong> — Capture Q1 through Q4 marks strictly validated between `0.00` and `100.00`.<br>
                            <strong>FR-14: Real-Time Composite Average Calculation</strong> — Dynamic mathematical running averages computed across completed assessment periods rounded to 2 decimals.
                        </p>
                    </div>

                    <div id="mod-ai" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-15: One-Click Performance Diagnostics</strong> — Automated scan of quarterly marks, flagging modules below 50%, and synthesizing study recommendations.<br>
                            <strong>FR-16: Resilient Offline Heuristic Fallback</strong> — Seamless algorithmic advice generation when external AI APIs are offline.<br>
                            <strong>FR-17: Interactive Academic Coach Chat</strong> — Pedagogical conversational assistant bounded strictly to syllabus mastery and revision timetables.
                        </p>
                    </div>

                    <div id="mod-pdf" class="tab-pane">
                        <p style="font-size:0.88rem;color:var(--color-slate-700);margin-bottom:0.5rem;">
                            <strong>FR-18: Client-Side PDF Document Generation</strong> — In-browser generation of official Registration Confirmation Slips and Academic Transcripts via `jsPDF` with zero server CPU consumption.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 5: 04_SOFTWARE_ARCHITECTURE_AND_DESIGN.md
                 ========================================== -->
            <section id="slide-5" class="slide" data-slide="5" data-notes="Document 4 outlines our Object-Oriented Software Architecture and Technical System Design. We constructed a clean PHP 8.2 domain model layer featuring Student, Course, Module, Lecturer, Enrollment, ModuleResult, and User. Cross-cutting workflows are encapsulated in dedicated service classes like RegistrationService. We also implemented a dual-column facade to preserve 100% backward compatibility with legacy queries.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline blue"><i class="material-icons-round" style="font-size:14px;">account_tree</i> Pipeline Step 04</span>
                        <h2 class="slide-title">Software Architecture & Technical Design (OOP & UML)</h2>
                        <p class="slide-subtitle">PHP 8 Domain Models, Layered 3-Tier Topology, Service Layer & Backward Compatibility Facade</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">04_SOFTWARE_ARCHITECTURE_AND_DESIGN.md</span>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- OOP Domain Structure -->
                    <div class="card-panel">
                        <h3 class="card-title"><i class="material-icons-round" style="color:var(--color-dffe-emerald);">code</i> Object-Oriented Domain Entities</h3>
                        <p class="card-desc" style="margin-bottom:0.75rem;">
                            Structured PHP 8.2 classes adhering to encapsulation, typed attributes, and single responsibility:
                        </p>
                        <div style="display:flex;flex-direction:column;gap:0.45rem;font-size:0.82rem;font-family:var(--font-mono);">
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class Student</strong> <span>Active enrollment, biographical attributes, results</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class Course</strong> <span>Qualification code, title, duration, curriculum modules</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class Module</strong> <span>Academic credits, syllabus code, assigned educators</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class Lecturer</strong> <span>Department, staff ID, assigned teaching rosters</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class Enrollment</strong> <span>Status validation, Single Active Rule enforcement</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class ModuleResult</strong> <span>Quarterly mark array (Q1–Q4), running weighted GPA</span>
                            </div>
                            <div style="background:#f1f5f9;padding:0.4rem 0.6rem;border-radius:6px;display:flex;justify-content:space-between;">
                                <strong>class User</strong> <span>Bcrypt credentials, role segregation (admin, lecturer, student)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Service Layer & Code Snippet -->
                    <div class="card-panel">
                        <h3 class="card-title"><i class="material-icons-round" style="color:#d97706;">miscellaneous_services</i> Service Layer: `RegistrationService`</h3>
                        <p class="card-desc" style="margin-bottom:0.5rem;">
                            Atomic transactional management: synchronizes normalized tables and provisions credentials:
                        </p>
                        <div class="code-box">
<span class="kw">class</span> <span class="fn">RegistrationService</span> {
    <span class="kw">public static function</span> <span class="fn">registerStudent</span>(PDO <span class="var">$pdo</span>, <span class="kw">array</span> <span class="var">$data</span>): <span class="kw">array</span> {
        <span class="var">$pdo</span>-><span class="fn">beginTransaction</span>();
        <span class="kw">try</span> {
            <span class="cm">// 1. Generate unique institutional student ID</span>
            <span class="var">$studentNumber</span> = self::<span class="fn">generateStudentNumber</span>(<span class="var">$pdo</span>);

            <span class="cm">// 2. Insert into 3NF normalized students table</span>
            <span class="var">$student</span> = <span class="kw">new</span> <span class="fn">Student</span>([...]);
            <span class="var">$studentId</span> = <span class="var">$student</span>-><span class="fn">save</span>(<span class="var">$pdo</span>);

            <span class="cm">// 3. Enforce Single Active Course Rule</span>
            <span class="var">$enrollment</span> = <span class="kw">new</span> <span class="fn">Enrollment</span>(<span class="var">$studentId</span>, <span class="var">$data</span>[<span class="str">'course_id'</span>]);
            <span class="var">$enrollment</span>-><span class="fn">save</span>(<span class="var">$pdo</span>);

            <span class="cm">// 4. Provision Bcrypt-secured user login</span>
            <span class="var">$user</span> = <span class="fn">User</span>::<span class="fn">create</span>(<span class="var">$studentNumber</span>, <span class="str">'password123'</span>, <span class="str">'student'</span>);
            
            <span class="var">$pdo</span>-><span class="fn">commit</span>();
            <span class="kw">return</span> [<span class="str">'studentNumber'</span> => <span class="var">$studentNumber</span>, <span class="str">'status'</span> => <span class="str">'success'</span>];
        } <span class="kw">catch</span> (Exception <span class="var">$e</span>) {
            <span class="var">$pdo</span>-><span class="fn">rollBack</span>();
            <span class="kw">throw</span> <span class="var">$e</span>;
        }
    }
}
                        </div>
                    </div>
                </div>

                <!-- Backward Compatibility Architecture Callout -->
                <div class="card-panel highlight" style="margin-top:1.25rem;">
                    <h3 class="card-title" style="color:var(--color-dffe-green);"><i class="material-icons-round">verified_user</i> Zero-Regressive Backward Compatibility Facade</h3>
                    <p class="card-desc">
                        To guarantee that legacy v1.0 queries and reports continue executing without exceptions, the system synchronizes both 3NF normalized fields and legacy columns in atomic operations:
                        <code>first_name + surname ↔ full_name</code>, 
                        <code>student_number ↔ student_id_number</code>, and 
                        <code>course_id ↔ course_of_study</code>.
                    </p>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 6: 05_DATABASE_SPECIFICATION_AND_DATA_DICTIONARY.md
                 ========================================== -->
            <section id="slide-6" class="slide" data-slide="6" data-notes="Document 5 details our Relational Database Specification and 3NF Normalization proofs. The legacy table suffered from insertion, update, and deletion anomalies. We eliminated partial functional dependencies in 2NF and transitive dependencies in 3NF. The resulting schema comprises 8 normalized tables with foreign keys and cascading rules.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline green"><i class="material-icons-round" style="font-size:14px;">storage</i> Pipeline Step 05</span>
                        <h2 class="slide-title">Database Specification & 3NF Relational Schema</h2>
                        <p class="slide-subtitle">Normal Forms Decomposition Proofs, Referential Integrity & Relational Data Map</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">05_DATABASE_SPECIFICATION_AND_DATA_DICTIONARY.md</span>
                    </div>
                </div>

                <!-- 3NF Proof Stages -->
                <div class="grid-3" style="margin-bottom:1.5rem;">
                    <div class="card-panel">
                        <span class="badge-pill badge-slate">STAGE 1</span>
                        <h4 style="font-weight:700;margin:0.5rem 0;color:var(--color-slate-900);">First Normal Form (1NF)</h4>
                        <p style="font-size:0.83rem;color:var(--color-slate-600);line-height:1.5;">
                            Decomposed non-atomic composite fields (`full_name`) into atomic attributes (`first_name`, `surname`). Guaranteed single-valued column domains and enforced primary keys.
                        </p>
                    </div>

                    <div class="card-panel">
                        <span class="badge-pill badge-blue">STAGE 2</span>
                        <h4 style="font-weight:700;margin:0.5rem 0;color:var(--color-slate-900);">Second Normal Form (2NF)</h4>
                        <p style="font-size:0.83rem;color:var(--color-slate-600);line-height:1.5;">
                            Eliminated partial functional dependencies on composite candidate keys. Separated module results and course registrations into independent relational entities.
                        </p>
                    </div>

                    <div class="card-panel highlight">
                        <span class="badge-pill badge-green">STAGE 3</span>
                        <h4 style="font-weight:700;margin:0.5rem 0;color:var(--color-dffe-green);">Third Normal Form (3NF)</h4>
                        <p style="font-size:0.83rem;color:var(--color-slate-600);line-height:1.5;">
                            Eliminated transitive dependencies (e.g., non-key attributes depending on other non-key attributes). Extracted courses, lecturers, and enrollments to dedicated normalized tables.
                        </p>
                    </div>
                </div>

                <!-- 8 Relational Tables Map -->
                <div class="card-panel dark">
                    <h3 class="card-title" style="color:#4ade80;"><i class="material-icons-round" style="color:#4ade80;">schema</i> Relational Entity Relationship Schema (`university_db`)</h3>
                    <div class="data-table-wrapper" style="margin-top:0.75rem;background:transparent;border:1px solid #1e293b;">
                        <table class="data-table" style="color:#e2e8f0;">
                            <thead>
                                <tr style="background:#020617;">
                                    <th>Table Name</th>
                                    <th>Primary Key</th>
                                    <th>Foreign Key Constraints</th>
                                    <th>Cascading Rule</th>
                                    <th>Domain Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>`students`</strong></td>
                                    <td>`id` (INT)</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>Biographical student profiles & auto-generated student numbers</td>
                                </tr>
                                <tr>
                                    <td><strong>`courses`</strong></td>
                                    <td>`course_id` (INT)</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>Academic qualifications, qualification code, and duration</td>
                                </tr>
                                <tr>
                                    <td><strong>`modules`</strong></td>
                                    <td>`module_id` (INT)</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>Curriculum module catalog, subject codes, and academic credits</td>
                                </tr>
                                <tr>
                                    <td><strong>`course_modules`</strong></td>
                                    <td>`id` (INT)</td>
                                    <td>`course_id`, `module_id`</td>
                                    <td><span class="badge-pill badge-green">ON DELETE CASCADE</span></td>
                                    <td>Many-to-many junction mapping shared curriculum subjects</td>
                                </tr>
                                <tr>
                                    <td><strong>`lecturers`</strong></td>
                                    <td>`lecturer_id` (INT)</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>Faculty educators, departments, and academic titles</td>
                                </tr>
                                <tr>
                                    <td><strong>`module_lecturers`</strong></td>
                                    <td>`id` (INT)</td>
                                    <td>`module_id`, `lecturer_id`</td>
                                    <td><span class="badge-pill badge-green">ON DELETE CASCADE</span></td>
                                    <td>Educator assignment junction supporting primary & co-lecturers</td>
                                </tr>
                                <tr>
                                    <td><strong>`enrollments`</strong></td>
                                    <td>`enrollment_id` (INT)</td>
                                    <td>`student_id`, `course_id`</td>
                                    <td><span class="badge-pill badge-green">ON DELETE CASCADE</span></td>
                                    <td>Tracks active qualification and preserves academic transcript history</td>
                                </tr>
                                <tr>
                                    <td><strong>`module_results`</strong></td>
                                    <td>`mark_id` (INT)</td>
                                    <td>`student_id`, `module_id`</td>
                                    <td><span class="badge-pill badge-green">ON DELETE CASCADE</span></td>
                                    <td>Quarterly assessments (Q1–Q4) and running composite average</td>
                                </tr>
                                <tr>
                                    <td><strong>`users`</strong></td>
                                    <td>`id` (INT)</td>
                                    <td>`student_id_number`, `lecturer_id`</td>
                                    <td><span class="badge-pill badge-green">CASCADE ON UPDATE</span></td>
                                    <td>Bcrypt salted credentials and RBAC roles (admin, lecturer, student)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 7: TECHNICAL INNOVATIONS & AI ADVISOR
                 ========================================== -->
            <section id="slide-7" class="slide" data-slide="7" data-notes="Slide 7 presents the engineering innovations that set this project apart. We integrated an AI Academic Success Advisor powered by OpenRouter's free tier, complete with an offline heuristic fallback engine so students never encounter system downtime. We also implemented automated student numbering ST2600xxx and client-side jsPDF document exports that generate official slips with zero server load.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline gold"><i class="material-icons-round" style="font-size:14px;">lightbulb</i> System Innovations</span>
                        <h2 class="slide-title">Core Engineering Innovations & Pedagogical AI</h2>
                        <p class="slide-subtitle">OpenRouter Generative AI Coach, Offline Algorithmic Fallback, Auto-Numbering & Client-Side PDF</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">INNOVATION HIGHLIGHTS</span>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- AI Academic Success Coach -->
                    <div class="card-panel highlight">
                        <div class="card-icon gold"><i class="material-icons-round">psychology</i></div>
                        <h3 class="card-title">AI Academic Success Coach (Free Cloud Tier)</h3>
                        <p class="card-desc">
                            Leverages the OpenRouter Free API (<code>google/gemini-2.0-flash-exp:free</code> & <code>minimax-m3.1-flash:free</code>) to provide bounded pedagogical tutoring:
                        </p>
                        <div style="margin-top:0.75rem;font-size:0.85rem;color:var(--color-slate-700);display:flex;flex-direction:column;gap:0.4rem;">
                            <div>• <strong>One-Click Diagnostic:</strong> Evaluates quarterly marks across all subjects and flags at-risk modules (< 50%).</div>
                            <div>• <strong>Interactive Study Chat:</strong> Tailors revision schedules, study techniques, and exam recovery strategies.</div>
                            <div>• <strong>Strict Pedagogical Guardrails:</strong> System prompts prevent off-topic discourse and enforce syllabus relevance.</div>
                        </div>
                    </div>

                    <!-- Offline Heuristic Fallback Engine -->
                    <div class="card-panel">
                        <div class="card-icon blue"><i class="material-icons-round">offline_bolt</i></div>
                        <h3 class="card-title">Resilient Offline Heuristic Fallback</h3>
                        <p class="card-desc">
                            If external AI APIs experience latency, outages, or rate-limits, the system automatically activates an internal algorithmic rule engine:
                        </p>
                        <div style="margin-top:0.75rem;font-size:0.85rem;color:var(--color-slate-700);display:flex;flex-direction:column;gap:0.4rem;">
                            <div>• <strong>Zero External Downtime:</strong> Students never receive connection error dialogues or broken JSON payloads.</div>
                            <div>• <strong>Algorithmic Remediation:</strong> Calculates statistical variance across Q1–Q4 and outputs prescriptive guidance immediately.</div>
                            <div>• <strong>Cost Insulation:</strong> Ensures complete operational continuity without requiring paid AI API tokens.</div>
                        </div>
                    </div>
                </div>

                <div class="grid-2" style="margin-top:1.25rem;">
                    <!-- Auto-Numbering Engine -->
                    <div class="card-panel">
                        <div class="card-icon green"><i class="material-icons-round">pin</i></div>
                        <h3 class="card-title">Automated Institutional Student Numbering</h3>
                        <p class="card-desc">
                            Standardizes institutional numbering via <code>RegistrationService::generateStudentNumber()</code>:
                        </p>
                        <div style="background:#f8fafc;padding:0.75rem 1rem;border-radius:8px;font-family:var(--font-mono);font-size:0.85rem;margin-top:0.5rem;border:1px solid #e2e8f0;">
                            Format: <strong style="color:#08492e;">ST</strong> + <span style="color:#0284c7;">[YY]</span> + <span style="color:#d97706;">[5-digit sequence]</span><br>
                            Example: <strong>ST2600001</strong>, <strong>ST2600002</strong> (Guaranteed Atomic DB Uniqueness)
                        </div>
                    </div>

                    <!-- Client-Side PDF Generation -->
                    <div class="card-panel">
                        <div class="card-icon"><i class="material-icons-round">picture_as_pdf</i></div>
                        <h3 class="card-title">Zero-Server-Load PDF Engine (jsPDF)</h3>
                        <p class="card-desc">
                            Client-side document rendering directly in the browser using <code>jsPDF</code>:
                        </p>
                        <div style="font-size:0.85rem;color:var(--color-slate-700);margin-top:0.5rem;">
                            Generates official <strong>Registration Confirmation Slips</strong> and complete <strong>Academic Transcripts</strong> with running GPA. Eliminates server-side rendering memory spikes and requires no paid PDF software licenses.
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SLIDE 8: LIVE VERIFICATION & ASSESSMENT SIGN-OFF
                 ========================================== -->
            <section id="slide-8" class="slide" data-slide="8" data-notes="To conclude our presentation, the system is fully deployed, tested, and operational. Assessors can log into our live cloud instance or test locally using our demo credential switcher. We have demonstrated full-lifecycle software engineering—from IEEE 830 requirements through 3NF database normalization and AI integration—delivering maximum enterprise value on a sub-R1,000 budget. We welcome questions and technical defense.">
                <div class="slide-header">
                    <div>
                        <span class="slide-tagline green"><i class="material-icons-round" style="font-size:14px;">task_alt</i> Final Stage</span>
                        <h2 class="slide-title">System Verification, Demo Credentials & Assessment Handover</h2>
                        <p class="slide-subtitle">Live Cloud Deployment, Technical Verification Benchmarks & Directorate Handover</p>
                    </div>
                    <div class="slide-badge-meta">
                        <span class="step-pill">VERIFICATION & DEFENSE</span>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Live Deployment Card -->
                    <div class="card-panel highlight">
                        <div class="card-icon green"><i class="material-icons-round">cloud_done</i></div>
                        <h3 class="card-title">Live Production Deployment</h3>
                        <p class="card-desc">
                            The complete application is deployed and publicly accessible with SSL encryption:
                        </p>
                        <div style="margin:1rem 0;">
                            <a href="https://student-system.infinityfree.io/index.php" target="_blank" class="pres-btn pres-btn-gold" style="font-size:0.95rem;padding:0.6rem 1.25rem;">
                                <i class="material-icons-round">public</i> Open Public Cloud Instance
                            </a>
                        </div>
                        <p style="font-size:0.82rem;color:var(--color-slate-500);">
                            URL: <a href="https://student-system.infinityfree.io/index.php" target="_blank" style="color:var(--color-dffe-emerald);">https://student-system.infinityfree.io/index.php</a>
                        </p>
                    </div>

                    <!-- Quality Benchmarks Card -->
                    <div class="card-panel">
                        <div class="card-icon"><i class="material-icons-round">speed</i></div>
                        <h3 class="card-title">Technical Performance Benchmarks</h3>
                        <div style="font-size:0.86rem;color:var(--color-slate-700);display:flex;flex-direction:column;gap:0.4rem;margin-top:0.5rem;">
                            <div>✔ <strong>Database Latency:</strong> Sub-250ms query execution across 3NF indexed tables.</div>
                            <div>✔ <strong>Regression Testing:</strong> 100% backward compatibility with legacy columns verified.</div>
                            <div>✔ <strong>Security Audit:</strong> Bcrypt password salts + CSRF token protection active.</div>
                            <div>✔ <strong>Browser Support:</strong> Fully responsive across Chrome, Firefox, Edge, Safari, and Mobile.</div>
                        </div>
                    </div>
                </div>

                <!-- Demo Credentials Switcher Box -->
                <div class="demo-bar">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <i class="material-icons-round" style="color:#fde047;font-size:20px;">key</i>
                        <strong>Quick Access Demo Credentials:</strong>
                    </div>
                    <div class="demo-roles">
                        <div class="demo-role-pill">
                            Student: <strong>10ha2304014</strong> / <code>bitcoin</code>
                        </div>
                        <div class="demo-role-pill">
                            Lecturer: <strong>lecturer</strong> / <code>password123</code>
                        </div>
                        <div class="demo-role-pill">
                            Admin: <strong>admin</strong> / <code>admin123</code>
                        </div>
                    </div>
                    <a href="index.php" class="pres-btn" style="background:#22c55e;color:#ffffff;border:none;">
                        Launch Portal <i class="material-icons-round" style="font-size:14px;">arrow_forward</i>
                    </a>
                </div>

                <!-- Sign-off Acknowledgement -->
                <div style="margin-top:2rem;text-align:center;padding:1.5rem;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;">
                    <h3 style="font-size:1.15rem;color:var(--color-dffe-green);margin-bottom:0.25rem;">
                        Department of Forestry, Fisheries and the Environment (DFFE)
                    </h3>
                    <p style="font-size:0.85rem;color:var(--color-slate-600);">
                        Corporate Management Services (CMS) — Directorate: IT Business Solutions (ITBS)<br>
                        <strong>Internship Practical Technical Assessment Completed & Ready for Assessment Defense</strong>
                    </p>
                    <div style="margin-top:1rem;display:flex;justify-content:center;gap:1.5rem;font-size:0.8rem;color:var(--color-slate-500);">
                        <span>Project Lead: Mr Avuyile Mthembu</span>
                        <span>•</span>
                        <span>DBA: Ms Nomthandazo Khoza</span>
                        <span>•</span>
                        <span>Frontend: Ms Qhama Nodada</span>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- SLIDE DECK BOTTOM CONTROLS (DECK MODE ONLY) -->
    <footer class="deck-footer deck-only">
        <div class="deck-dots" id="deckDots">
            <!-- Dynamically generated clickable slide indicators -->
        </div>

        <div class="slide-counter">
            <span id="currentSlideNum">01</span> / <span id="totalSlideNum">08</span>
        </div>

        <div class="deck-nav-buttons">
            <button class="pres-btn" onclick="prevSlide()" title="Previous Slide (Left Arrow / Page Up)">
                <i class="material-icons-round" style="font-size:18px;">arrow_back</i>
                <span>Previous</span>
            </button>
            <button class="pres-btn pres-btn-gold" onclick="nextSlide()" title="Next Slide (Right Arrow / Space / Page Down)">
                <span>Next Slide</span>
                <i class="material-icons-round" style="font-size:18px;">arrow_forward</i>
            </button>
        </div>
    </footer>

    <!-- SPEAKER NOTES DRAWER (PRESS 'S') -->
    <div id="notesDrawer" class="notes-drawer">
        <div class="notes-header">
            <div class="notes-title">
                <i class="material-icons-round">record_voice_over</i>
                <span>Presenter Talking Points</span>
            </div>
            <button onclick="toggleNotes()" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;">✕</button>
        </div>
        <div id="notesBody" class="notes-content">
            <!-- Dynamic presenter notes updated on slide change -->
        </div>
    </div>

    <!-- OVERVIEW SLIDE GRID MODAL (PRESS 'O') -->
    <div id="overviewModal" class="overview-modal" onclick="if(event.target===this) toggleOverview()">
        <div class="overview-container">
            <div class="overview-header">
                <div>
                    <h3 style="font-size:1.25rem;font-weight:800;color:var(--color-dffe-green);">Presentation Agenda & Slide Navigator</h3>
                    <p style="font-size:0.85rem;color:var(--color-slate-600);">Click any slide below to jump immediately (or press Esc to close)</p>
                </div>
                <button onclick="toggleOverview()" style="background:none;border:none;font-size:22px;color:var(--color-slate-500);cursor:pointer;">✕</button>
            </div>
            <div id="overviewGrid" class="overview-grid">
                <!-- Dynamically generated slide cards -->
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT PRESENTATION ENGINE -->
    <script>
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        let currentSlide = 1;
        let isDeckMode = true;

        // Presentation Timer Variables
        let timerInterval = null;
        let timerSeconds = 0;
        let timerRunning = false;

        // Initialize Deck Controls
        function initDeck() {
            document.getElementById('totalSlideNum').textContent = String(totalSlides).padStart(2, '0');
            const dotsContainer = document.getElementById('deckDots');
            const overviewGrid = document.getElementById('overviewGrid');

            dotsContainer.innerHTML = '';
            overviewGrid.innerHTML = '';

            slides.forEach((slide, idx) => {
                const slideNum = idx + 1;
                const slideTitle = slide.querySelector('.slide-title')?.textContent || `Slide ${slideNum}`;
                const slidePill = slide.querySelector('.step-pill')?.textContent || `Stage ${slideNum}`;

                // Dot button
                const dot = document.createElement('button');
                dot.className = `dot-btn ${slideNum === 1 ? 'active' : ''}`;
                dot.title = `Jump to Slide ${slideNum}: ${slideTitle}`;
                dot.onclick = () => goToSlide(slideNum);
                dotsContainer.appendChild(dot);

                // Overview card
                const card = document.createElement('div');
                card.className = `overview-card ${slideNum === 1 ? 'current' : ''}`;
                card.onclick = () => {
                    goToSlide(slideNum);
                    toggleOverview();
                };
                card.innerHTML = `
                    <div class="overview-card-num">${slidePill} • Slide ${slideNum}</div>
                    <div class="overview-card-title">${slideTitle}</div>
                `;
                overviewGrid.appendChild(card);
            });

            updateSlideUI();
        }

        function updateSlideUI() {
            if (!isDeckMode) return;

            slides.forEach((slide, idx) => {
                const slideNum = idx + 1;
                if (slideNum === currentSlide) {
                    slide.classList.add('active-slide');
                } else {
                    slide.classList.remove('active-slide');
                }
            });

            // Update bottom dots
            const dots = document.querySelectorAll('.dot-btn');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx + 1 === currentSlide);
            });

            // Update overview cards
            const cards = document.querySelectorAll('.overview-card');
            cards.forEach((card, idx) => {
                card.classList.toggle('current', idx + 1 === currentSlide);
            });

            // Update counter & progress bar
            document.getElementById('currentSlideNum').textContent = String(currentSlide).padStart(2, '0');
            const pct = (currentSlide / totalSlides) * 100;
            document.getElementById('progressBar').style.width = pct + '%';

            // Update speaker notes
            const activeSlideElem = slides[currentSlide - 1];
            const notes = activeSlideElem?.getAttribute('data-notes') || 'No talking points configured for this slide.';
            document.getElementById('notesBody').innerHTML = `<p>${notes}</p>`;

            // Scroll to top of window smoothly
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goToSlide(num) {
            if (num < 1) num = 1;
            if (num > totalSlides) num = totalSlides;
            currentSlide = num;
            updateSlideUI();
        }

        function nextSlide() {
            if (currentSlide < totalSlides) {
                currentSlide++;
                updateSlideUI();
            }
        }

        function prevSlide() {
            if (currentSlide > 1) {
                currentSlide--;
                updateSlideUI();
            }
        }

        // Toggle Presentation vs Executive Reading Mode
        function togglePresentationMode() {
            isDeckMode = !isDeckMode;
            document.body.classList.toggle('briefing-mode', !isDeckMode);

            const modeBtn = document.getElementById('modeToggleBtn');
            const modeLabel = document.getElementById('modeLabel');

            if (isDeckMode) {
                modeBtn.classList.remove('active');
                modeLabel.textContent = 'Reading Mode';
                updateSlideUI();
            } else {
                modeBtn.classList.add('active');
                modeLabel.textContent = 'Slide Deck Mode';
                document.getElementById('notesDrawer').classList.remove('open');
                document.getElementById('overviewModal').classList.remove('open');
            }
        }

        // Speaker Notes Drawer
        function toggleNotes() {
            const drawer = document.getElementById('notesDrawer');
            drawer.classList.toggle('open');
        }

        // Overview Modal
        function toggleOverview() {
            const modal = document.getElementById('overviewModal');
            modal.classList.toggle('open');
        }

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Rehearsal Stopwatch Timer
        function toggleTimer() {
            const toggleIcon = document.getElementById('timerToggle');
            if (timerRunning) {
                clearInterval(timerInterval);
                timerRunning = false;
                toggleIcon.textContent = '▶';
            } else {
                timerRunning = true;
                toggleIcon.textContent = '⏸';
                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const mins = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
                    const secs = String(timerSeconds % 60).padStart(2, '0');
                    document.getElementById('timerDisplay').textContent = `${mins}:${secs}`;
                }, 1000);
            }
        }

        // Tabs inside Slide 4 (Requirements)
        function switchReqTab(event, tabId) {
            const parentCard = event.target.closest('.card-panel');
            const btns = parentCard.querySelectorAll('.tab-btn');
            const panes = parentCard.querySelectorAll('.tab-pane');

            btns.forEach(b => b.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            event.target.classList.add('active');
            const targetPane = document.getElementById(tabId);
            if (targetPane) targetPane.classList.add('active');
        }

        // Keyboard Navigation Shortcuts
        window.addEventListener('keydown', (e) => {
            // Ignore keystrokes inside input fields or textareas
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;

            if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown' || e.key.toLowerCase() === 'n') {
                if (isDeckMode) {
                    e.preventDefault();
                    nextSlide();
                }
            } else if (e.key === 'ArrowLeft' || e.key === 'PageUp' || e.key.toLowerCase() === 'p') {
                if (isDeckMode) {
                    e.preventDefault();
                    prevSlide();
                }
            } else if (e.key.toLowerCase() === 'f') {
                toggleFullscreen();
            } else if (e.key.toLowerCase() === 'm') {
                togglePresentationMode();
            } else if (e.key.toLowerCase() === 's') {
                toggleNotes();
            } else if (e.key.toLowerCase() === 'o') {
                toggleOverview();
            } else if (e.key.toLowerCase() === 't') {
                toggleTimer();
            } else if (e.key === 'Escape') {
                document.getElementById('notesDrawer').classList.remove('open');
                document.getElementById('overviewModal').classList.remove('open');
            }
        });

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            initDeck();
        });
    </script>
</body>
</html>
<?php
$page_title = "My Academic Results & Quarterly Marks";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch the logged-in student's details
if (!isset($_SESSION['student_id_number'])) {
    redirect('../index.php');
}

$student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
if (!$student) {
    die("Student profile not found.");
}

$activeEnrollment = $student->getActiveEnrollment($pdo);
$moduleResults = $student->getModuleResults($pdo);

// Summary statistics & Performance metrics
$totalCredits = 0;
$weightedSum = 0;
$totalAverageSum = 0;
$resultCount = 0;
$passedCount = 0;
$distinctionCount = 0;
$highestAvg = -1;
$highestModule = null;
$lowestAvg = 101;
$lowestModule = null;

foreach ($moduleResults as $res) {
    $credits = (int)$res['credits'];
    $totalCredits += $credits;
    if ($res['average'] !== null) {
        $avgVal = (float)$res['average'];
        $totalAverageSum += $avgVal;
        $weightedSum += ($avgVal * $credits);
        $resultCount++;
        if ($avgVal >= 50) $passedCount++;
        if ($avgVal >= 75) $distinctionCount++;
        if ($avgVal > $highestAvg) {
            $highestAvg = $avgVal;
            $highestModule = $res;
        }
        if ($avgVal < $lowestAvg) {
            $lowestAvg = $avgVal;
            $lowestModule = $res;
        }
    }
}

$termAverage = $resultCount > 0 ? round($totalAverageSum / $resultCount, 2) : null;
$weightedGpa = ($resultCount > 0 && $totalCredits > 0) ? round($weightedSum / $totalCredits, 2) : null;
$passRate = $resultCount > 0 ? round(($passedCount / $resultCount) * 100, 1) : 0;

$academicHonors = 'Good Academic Standing';
$standingColor = 'green';
if ($termAverage !== null) {
    if ($termAverage >= 75) {
        $academicHonors = 'Pass with Distinction (Cum Laude)';
        $standingColor = 'green';
    } elseif ($termAverage >= 60) {
        $academicHonors = 'Merit Standing';
        $standingColor = 'teal';
    } elseif ($termAverage >= 50) {
        $academicHonors = 'Satisfactory Progress';
        $standingColor = 'blue';
    } else {
        $academicHonors = 'Academic Notice';
        $standingColor = 'amber darken-2';
    }
}

// Prepare module data payload for AI Coach API
$clientModuleData = [];
foreach ($moduleResults as $res) {
    $clientModuleData[] = [
        'code' => $res['module_code'],
        'name' => $res['module_name'],
        'q1' => $res['quarter1'] !== null ? (float)$res['quarter1'] : null,
        'q2' => $res['quarter2'] !== null ? (float)$res['quarter2'] : null,
        'q3' => $res['quarter3'] !== null ? (float)$res['quarter3'] : null,
        'q4' => $res['quarter4'] !== null ? (float)$res['quarter4'] : null,
        'average' => $res['average'] !== null ? (float)$res['average'] : null
    ];
}
$studentDisplayName = $student->getFirstName() ?: 'Avuyile';
$studentInitials = strtoupper(substr($student->getFirstName() ?: 'A', 0, 1) . substr($student->getSurname() ?: 'M', 0, 1));

$studentPayloadJson = json_encode([
    'studentName' => $student->getFullName(),
    'modules' => $clientModuleData
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<!-- KaTeX & Highlight.js CSS for Atlas-Grade AI Rich Rendering -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">

<style>
    /* ==========================================================================
       Academic Results & Official Transcript Styling (Material Design 3)
       ========================================================================== */
    :root {
        --mat-primary: #1565c0;
        --mat-primary-dark: #0d47a1;
        --mat-primary-light: #e3f2fd;
        --mat-accent: #f59e0b;
        --mat-success: #2e7d32;
        --mat-success-light: #e8f5e9;
        --mat-surface: #ffffff;
        --mat-surface-subtle: #f8fafc;
        --mat-border: #e2e8f0;
        --mat-text-primary: #0f172a;
        --mat-text-secondary: #475569;
        --mat-text-muted: #64748b;
        --mat-radius-sm: 8px;
        --mat-radius-md: 12px;
        --mat-radius-lg: 16px;
        --mat-shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        --mat-shadow-md: 0 4px 14px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.04);
        --mat-shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
    }

    body {
        background-color: #f1f5f9;
        color: var(--mat-text-primary);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* Actions Bar */
    .results-actions-bar {
        margin: 20px 0 16px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-dashboard-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--mat-text-secondary);
        font-weight: 600;
        font-size: 0.92rem;
        padding: 8px 14px;
        border-radius: var(--mat-radius-sm);
        transition: all 0.2s ease;
        background: #ffffff;
        border: 1px solid var(--mat-border);
    }
    .btn-dashboard-back:hover {
        background: #f1f5f9;
        color: var(--mat-primary);
        border-color: #cbd5e1;
    }

    .results-btn-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        height: 42px !important;
        line-height: 42px !important;
        padding: 0 18px !important;
        border-radius: var(--mat-radius-sm) !important;
        text-transform: none !important;
        box-shadow: var(--mat-shadow-sm) !important;
        transition: transform 0.15s ease, box-shadow 0.2s ease !important;
    }
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--mat-shadow-md) !important;
    }
    .action-btn i.material-icons {
        font-size: 19px !important;
        margin-right: 0 !important;
    }

    .btn-coach-gradient {
        background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important;
        color: #fff !important;
    }
    .btn-coach-gradient:hover {
        background: linear-gradient(135deg, #6d28d9 0%, #4338ca 100%) !important;
    }

    /* Main Transcript Card */
    .transcript-card {
        background: #ffffff;
        padding: 34px 36px;
        border-radius: var(--mat-radius-md);
        box-shadow: var(--mat-shadow-md);
        border: 1px solid var(--mat-border);
        box-sizing: border-box;
        width: 100%;
        margin-bottom: 25px;
        position: relative;
    }

    /* Official South African National Header */
    .transcript-header {
        text-align: center;
        padding-bottom: 22px;
        margin-bottom: 24px;
        position: relative;
    }
    .transcript-header::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 5%;
        right: 5%;
        height: 3px;
        background: linear-gradient(90deg, #1565c0 0%, #1565c0 68%, #f59e0b 68%, #f59e0b 100%);
        border-radius: 2px;
    }

    .transcript-dept-lead {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .transcript-dept-lead i {
        font-size: 15px;
        color: #1e3a8a;
    }

    .transcript-title {
        margin: 8px 0 4px 0;
        color: #0f172a;
        font-weight: 700;
        font-size: 1.85rem;
        letter-spacing: -0.02em;
    }

    .transcript-subtitle {
        color: var(--mat-text-muted);
        font-size: 0.88rem;
        margin: 0;
        font-weight: 500;
    }

    /* Student Identity Banner */
    .student-bio-card {
        background: var(--mat-surface-subtle);
        border: 1px solid var(--mat-border);
        border-radius: var(--mat-radius-md);
        padding: 20px 24px;
        margin-bottom: 24px;
    }

    .student-bio-grid {
        display: grid;
        grid-template-columns: auto 1fr 1fr;
        gap: 22px;
        align-items: center;
    }

    .student-avatar-circle {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e3a8a 0%, #1565c0 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.55rem;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(21, 101, 192, 0.25);
        border: 3px solid #fff;
    }

    .bio-data-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .bio-item {
        display: flex;
        align-items: baseline;
        gap: 8px;
        font-size: 0.92rem;
        color: var(--mat-text-secondary);
    }
    .bio-label {
        font-weight: 600;
        color: #64748b;
        font-size: 0.84rem;
        min-width: 135px;
    }
    .bio-value {
        font-weight: 600;
        color: var(--mat-text-primary);
    }

    .student-num-badge {
        background: #e2e8f0;
        color: #0f172a;
        font-family: monospace;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.88rem;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .student-num-badge:hover {
        background: #cbd5e1;
    }

    .bio-status-row {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .status-tag {
        font-size: 0.78rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .status-tag.status-active {
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .status-tag.status-active::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #16a34a;
        display: inline-block;
    }
    .status-tag-honor {
        font-size: 0.84rem;
        font-weight: 600;
        color: #1e3a8a;
    }

    /* KPI Stat Metric Cards Grid */
    .kpi-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid var(--mat-border);
        border-radius: var(--mat-radius-md);
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: var(--mat-shadow-sm);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--mat-shadow-md);
    }

    .kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .kpi-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kpi-icon-wrap i {
        font-size: 20px;
    }
    .kpi-icon-blue { background: #eff6ff; color: #2563eb; }
    .kpi-icon-purple { background: #faf5ff; color: #9333ea; }
    .kpi-icon-green { background: #f0fdf4; color: #16a34a; }
    .kpi-icon-amber { background: #fffbeb; color: #d97706; }

    .kpi-badge {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .kpi-badge-green { background: #dcfce7; color: #15803d; }
    .kpi-badge-blue { background: #dbeafe; color: #1d4ed8; }
    .kpi-badge-purple { background: #f3e8ff; color: #7e22ce; }
    .kpi-badge-amber { background: #fef3c7; color: #b45309; }

    .kpi-val {
        font-size: 1.65rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin: 2px 0 4px 0;
    }

    .kpi-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--mat-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .kpi-bar-wrap {
        width: 100%;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        margin: 10px 0 6px 0;
        overflow: hidden;
    }
    .kpi-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.8s ease-out;
    }

    .kpi-subtext {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Section Header & View Switcher */
    .section-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 14px;
    }

    .section-heading {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .view-switcher {
        display: inline-flex;
        background: #f1f5f9;
        border-radius: var(--mat-radius-sm);
        padding: 3px;
        border: 1px solid var(--mat-border);
    }
    .view-btn {
        border: none;
        background: transparent;
        color: var(--mat-text-secondary);
        font-weight: 600;
        font-size: 0.82rem;
        padding: 5px 12px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .view-btn.active {
        background: #ffffff;
        color: var(--mat-primary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .view-btn i {
        font-size: 16px;
    }

    /* Results Table Wrapper */
    .results-table-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 24px;
        border: 1px solid var(--mat-border);
        border-radius: var(--mat-radius-md);
        background: #ffffff;
        box-shadow: var(--mat-shadow-sm);
    }
    .results-table-container::-webkit-scrollbar {
        height: 6px;
    }
    .results-table-container::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }

    .results-mat-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .results-mat-table thead tr {
        background: #0f172a;
        color: #f8fafc;
    }
    .results-mat-table th {
        padding: 12px 14px;
        font-weight: 600;
        font-size: 0.82rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border: none;
        white-space: nowrap;
    }

    .results-mat-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .results-mat-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .results-mat-table tbody tr:last-child {
        border-bottom: none;
    }

    .results-mat-table td {
        padding: 14px 14px;
        vertical-align: middle;
        color: #1e293b;
        border: none;
    }

    .module-cell-primary {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 220px;
    }
    .module-code-pill {
        display: inline-block;
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        font-weight: 700;
        font-size: 0.78rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-family: monospace;
        letter-spacing: 0.5px;
        width: fit-content;
    }
    .module-name-title {
        font-weight: 600;
        color: #0f172a;
        font-size: 0.94rem;
        line-height: 1.3;
    }
    .module-lecturer-text {
        font-size: 0.78rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .credits-chip {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 4px 10px;
        border-radius: 12px;
        display: inline-block;
        white-space: nowrap;
    }

    .mark-val {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .mark-distinction { color: #15803d; }
    .mark-merit { color: #0f766e; }
    .mark-pass { color: #1d4ed8; }
    .mark-fail { color: #b91c1c; }
    .mark-pending { color: #94a3b8; }

    .avg-cell-box {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 85px;
    }
    .avg-score-text {
        font-weight: 800;
        font-size: 1.05rem;
        color: #0f172a;
    }
    .avg-mini-bar {
        width: 100%;
        height: 5px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }
    .avg-mini-fill {
        height: 100%;
        background: linear-gradient(90deg, #2563eb, #16a34a);
        border-radius: 3px;
    }

    .grade-chip {
        font-size: 0.78rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 12px;
        display: inline-block;
        white-space: nowrap;
        text-align: center;
    }
    .grade-chip-distinction { background: #dcfce7; color: #166534; }
    .grade-chip-merit { background: #ccfbf1; color: #115e59; }
    .grade-chip-pass { background: #dbeafe; color: #1e40af; }
    .grade-chip-fail { background: #fee2e2; color: #991b1b; }
    .grade-chip-pending { background: #f1f5f9; color: #64748b; }

    .btn-quick-coach {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #6366f1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-quick-coach:hover {
        background: #eef2ff;
        border-color: #818cf8;
        color: #4338ca;
    }

    /* Cards Grid View (Alternative View) */
    .module-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .module-performance-card {
        background: #ffffff;
        border: 1px solid var(--mat-border);
        border-radius: var(--mat-radius-md);
        padding: 18px 20px;
        box-shadow: var(--mat-shadow-sm);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 14px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .module-performance-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--mat-shadow-md);
    }

    .mod-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
    }

    .quarters-meter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        background: var(--mat-surface-subtle);
        padding: 12px;
        border-radius: var(--mat-radius-sm);
        border: 1px solid #f1f5f9;
    }
    .quarter-meter-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    .quarter-meter-item span.q-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
    }
    .quarter-meter-item span.q-val {
        font-size: 0.88rem;
        font-weight: 700;
    }

    .mod-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }

    /* Summary Footer Boxes */
    .transcript-summary-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: var(--mat-radius-md);
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
    }

    .summary-formula-text {
        font-size: 0.84rem;
        color: #475569;
        margin: 0;
    }

    /* Official Notice Footer */
    .transcript-official-footer {
        text-align: center;
        color: #94a3b8;
        font-size: 0.78rem;
        padding: 14px 0 0 0;
        border-top: 1px solid #f1f5f9;
        line-height: 1.5;
    }

    /* ==========================================================================
       AI Academic Success Coach (Conversational Material AI Chat)
       ========================================================================== */
    .ai-coach-card {
        background: #ffffff;
        border-radius: var(--mat-radius-md);
        box-shadow: var(--mat-shadow-md);
        border: 1px solid var(--mat-border);
        margin-top: 25px;
        margin-bottom: 40px;
        overflow: hidden;
        position: relative;
    }

    .ai-coach-card-header {
        background: #ffffff;
        padding: 22px 26px;
        border-bottom: 1px solid var(--mat-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .ai-coach-branding {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ai-coach-avatar-bubble {
        width: 48px;
        height: 48px;
        border-radius: var(--mat-radius-sm);
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        flex-shrink: 0;
    }
    .ai-coach-avatar-bubble i {
        font-size: 26px;
    }

    .ai-coach-headings {
        display: flex;
        flex-direction: column;
    }

    .ai-coach-main-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ai-coach-main-desc {
        font-size: 0.82rem;
        color: #64748b;
        margin: 2px 0 0 0;
    }

    .ai-coach-header-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Unified 34px height, 6px radius controls */
    .ai-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 500;
        height: 34px;
        line-height: 34px;
        padding: 0 12px;
        border-radius: 6px;
        box-sizing: border-box;
        white-space: nowrap;
    }
    .status-dot-subtle {
        width: 7px;
        height: 7px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    .btn-run-diagnosis {
        background: #2563eb !important;
        color: #ffffff !important;
        border: 1px solid #1d4ed8 !important;
        border-radius: 6px !important;
        font-weight: 600 !important;
        font-size: 0.82rem !important;
        height: 34px !important;
        line-height: 32px !important;
        padding: 0 14px !important;
        text-transform: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.15) !important;
        box-sizing: border-box !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
        white-space: nowrap !important;
    }
    .btn-run-diagnosis:hover {
        background: #1d4ed8 !important;
        border-color: #1e40af !important;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25) !important;
    }
    .btn-run-diagnosis i {
        font-size: 16px !important;
    }

    .btn-clear-chat {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        border-radius: 6px;
        height: 34px;
        line-height: 32px;
        padding: 0 12px;
        font-size: 0.82rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        box-sizing: border-box;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-clear-chat:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }
    .btn-clear-chat i {
        font-size: 16px;
        color: #64748b;
    }

    /* Suggested Prompt Bubbles near Chat Input */
    .ai-suggested-bubbles {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 2px 0;
        flex-wrap: wrap;
    }
    .ai-suggested-bubbles::-webkit-scrollbar {
        display: none;
    }

    .ai-prompt-bubble {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 20px;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        box-sizing: border-box;
    }
    .ai-prompt-bubble:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
    }

    /* Conversation Thread Stream & Dynamic Messages Spacing */
    #aiDynamicMessages {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
    }

    .ai-chat-thread {
        padding: 26px 28px;
        background: #f8fafc;
        max-height: 580px;
        min-height: 340px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 24px;
        scroll-behavior: smooth;
    }
    .ai-chat-thread::-webkit-scrollbar {
        width: 6px;
    }
    .ai-chat-thread::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    /* Message Bubbles - Strict Flex Alignment & Spacing */
    .chat-msg-row {
        display: flex;
        gap: 14px;
        width: 100%;
        align-items: flex-start;
        animation: fadeInMsg 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
    }
    @keyframes fadeInMsg {
        from { opacity: 0; transform: translateY(8px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .msg-user {
        justify-content: flex-end;
    }
    .msg-assistant {
        justify-content: flex-start;
    }

    .chat-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.86rem;
        font-weight: 700;
        margin-top: 2px;
        position: relative;
    }
    .avatar-user {
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
    }
    .avatar-coach {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff;
        box-shadow: 0 3px 10px rgba(124, 58, 237, 0.3);
    }

    .chat-bubble {
        position: relative;
    }

    /* User Bubble Styling */
    .user-bubble {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        color: #ffffff;
        padding: 13px 20px;
        border-radius: 18px 18px 4px 18px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
        max-width: 76%;
    }
    .user-bubble-text {
        font-size: 0.95rem;
        font-weight: 500;
        line-height: 1.55;
        margin: 0;
        word-break: break-word;
        letter-spacing: -0.01em;
    }
    .user-bubble-meta {
        font-size: 0.72rem;
        color: rgba(255, 255, 255, 0.82);
        text-align: right;
        margin-top: 6px;
        font-weight: 500;
        letter-spacing: 0.2px;
    }

    /* Coach Bubble Styling */
    .coach-bubble {
        flex: 1 1 auto;
        min-width: 0;
        max-width: calc(100% - 52px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 22px 26px;
        border-radius: 20px 20px 20px 4px;
        color: #1e293b;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        position: relative;
    }

    .coach-bubble-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 8px;
    }
    .coach-bubble-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .coach-model-tag {
        font-size: 0.72rem;
        font-weight: 600;
        background: #ecfdf5;
        color: #047857;
        padding: 2px 8px;
        border-radius: 10px;
        border: 1px solid #a7f3d0;
    }

    .coach-bubble-body {
        font-size: 0.93rem;
        line-height: 1.7;
        color: #334155;
        word-break: break-word;
    }

    /* Markdown Formatted Elements */
    .ai-rendered-output h3, .ai-rendered-output h4, .ai-rendered-output h5 {
        margin: 18px 0 8px 0;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .ai-rendered-output h3 {
        font-size: 1.2rem;
        border-left: 3px solid #2563eb;
        padding-left: 10px;
        color: #1e40af;
    }
    .ai-rendered-output h4 {
        font-size: 1.08rem;
        color: #0f172a;
    }
    .ai-rendered-output h5 {
        font-size: 0.98rem;
        color: #334155;
    }

    .ai-rendered-output p {
        margin: 0 0 12px 0;
        line-height: 1.7;
    }
    .ai-rendered-output p:last-child {
        margin-bottom: 0;
    }

    .ai-bullet-group {
        margin: 8px 0 16px 0;
        padding-left: 0;
        list-style: none;
    }
    .ai-bullet-group > li {
        position: relative;
        padding-left: 22px;
        margin-bottom: 8px;
        line-height: 1.6;
        color: #334155;
    }
    .ai-bullet-group > li::before {
        content: "•";
        position: absolute;
        left: 6px;
        top: -1px;
        color: #2563eb;
        font-size: 1.25rem;
        font-weight: bold;
    }

    .ai-sub-description {
        margin-top: 4px;
        margin-bottom: 8px;
        padding-left: 12px;
        border-left: 2px solid #cbd5e1;
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.55;
    }

    .ai-action-card {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-left: 4px solid #16a34a;
        border-radius: var(--mat-radius-sm);
        padding: 14px 18px;
        margin: 16px 0;
    }
    .ai-action-header {
        font-size: 0.82rem;
        font-weight: 700;
        color: #166534;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ai-action-body {
        font-size: 0.9rem;
        color: #14532d;
        line-height: 1.65;
    }

    .ai-code-inline {
        background: #f1f5f9;
        color: #0f172a;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.88em;
        font-family: monospace;
        border: 1px solid #e2e8f0;
    }
    .ai-step-arrow {
        color: #2563eb;
        font-weight: bold;
        padding: 0 4px;
    }

    /* Atlas-Grade AI Code Blocks & Toolbar */
    .ai-code-block-card {
        background: #0f172a;
        border: 1px solid #1e293b;
        border-radius: 8px;
        margin: 16px 0;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }
    .ai-code-header {
        background: #1e293b;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .ai-code-lang-badge {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ai-code-lang-badge i {
        font-size: 14px;
        color: #60a5fa;
    }
    .ai-code-lines-count {
        background: rgba(255, 255, 255, 0.08);
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.68rem;
        color: #cbd5e1;
        font-weight: 500;
    }
    .ai-code-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-code-copy, .btn-code-wrap {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #e2e8f0;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .btn-code-copy:hover, .btn-code-wrap:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }
    .btn-code-wrap.active {
        background: rgba(59, 130, 246, 0.25);
        border-color: #3b82f6;
        color: #93c5fd;
    }
    .ai-code-pre {
        margin: 0 !important;
        padding: 14px 18px !important;
        background: #0f172a !important;
        overflow-x: auto;
        white-space: pre;
        border: none !important;
        border-radius: 0 !important;
    }
    .ai-code-pre code {
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace !important;
        font-size: 0.86rem !important;
        line-height: 1.6 !important;
        background: transparent !important;
        padding: 0 !important;
        border: none !important;
    }

    /* Atlas-Grade Responsive Data Tables */
    .ai-table-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 16px 0;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .ai-rich-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        text-align: left;
    }
    .ai-rich-table th {
        background: #f8fafc;
        color: #1e293b;
        font-weight: 700;
        padding: 10px 14px;
        border-bottom: 2px solid #e2e8f0;
        letter-spacing: 0.2px;
    }
    .ai-rich-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: top;
    }
    .ai-rich-table tbody tr:last-child td {
        border-bottom: none;
    }
    .ai-rich-table tbody tr:hover td {
        background: #f8fafc;
    }

    /* KaTeX Mathematical & Scientific Formula Styling */
    .ai-math-block-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #6366f1;
        border-radius: 8px;
        padding: 16px 20px;
        margin: 16px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        overflow-x: auto;
    }
    .ai-math-body {
        width: 100%;
        overflow-x: auto;
        text-align: center;
        padding: 4px 0;
    }
    .btn-copy-latex {
        align-self: flex-end;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .btn-copy-latex:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    .ai-math-inline {
        padding: 0 3px;
        vertical-align: baseline;
    }
    .ai-blockquote {
        border-left: 4px solid #3b82f6;
        background: #eff6ff;
        padding: 10px 16px;
        margin: 12px 0;
        border-radius: 0 6px 6px 0;
        color: #1e3a8a;
        font-style: italic;
    }

    /* Coach Bubble Actions Bar */
    .coach-bubble-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-copy-advice {
        background: transparent;
        border: 1px solid #cbd5e1;
        color: #475569;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.78rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-copy-advice:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .followup-chips-wrap {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .followup-chip {
        font-size: 0.74rem;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        padding: 4px 10px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .followup-chip:hover {
        background: #e0e7ff;
        color: #3730a3;
        border-color: #c7d2fe;
    }

    /* Clean Modern Thinking State */
    .ai-thinking-row {
        align-items: center;
        gap: 12px;
        padding: 6px 0;
    }
    .ai-thinking-bubble-clean {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 14px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    .thinking-dots {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }
    .thinking-dot {
        width: 6px;
        height: 6px;
        background: #4f46e5;
        border-radius: 50%;
        animation: thinkDotBounce 1.2s infinite ease-in-out both;
    }
    .thinking-dot:nth-child(1) { animation-delay: -0.32s; }
    .thinking-dot:nth-child(2) { animation-delay: -0.16s; }
    .thinking-dot:nth-child(3) { animation-delay: 0s; }

    @keyframes thinkDotBounce {
        0%, 80%, 100% {
            transform: scale(0.7);
            opacity: 0.35;
        }
        40% {
            transform: scale(1.15);
            opacity: 1;
        }
    }
    .thinking-dynamic-label {
        font-size: 0.82rem;
        color: #475569;
        font-weight: 500;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .thinking-dynamic-label.status-fade-out {
        opacity: 0;
        transform: translateY(-2px);
    }
    .thinking-dynamic-label.status-fade-in {
        opacity: 1;
        transform: translateY(0);
    }

    /* Material 3 Floating Chat Input Capsule */
    .ai-chat-input-area {
        padding: 18px 24px;
        background: #ffffff;
        border-top: 1px solid var(--mat-border);
    }

    .ai-input-capsule {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 28px;
        padding: 4px 8px 4px 18px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }
    .ai-input-capsule:focus-within {
        background: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .ai-sparkle-icon {
        color: #4f46e5;
        font-size: 22px;
        margin-right: 10px;
        flex-shrink: 0;
    }

    .ai-chat-input {
        border: none !important;
        outline: none !important;
        background: transparent !important;
        height: 40px !important;
        line-height: 40px !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 0.94rem !important;
        color: #0f172a !important;
        flex: 1 1 auto;
        box-shadow: none !important;
    }
    .ai-chat-input::placeholder {
        color: #94a3b8;
    }

    .btn-input-clear {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 6px;
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    .btn-input-clear:hover {
        color: #475569;
        background: #e2e8f0;
    }

    .btn-chat-send {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #1565c0;
        color: #ffffff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: transform 0.15s ease, background-color 0.2s ease;
        box-shadow: 0 2px 6px rgba(21, 101, 192, 0.3);
    }
    .btn-chat-send:hover {
        background: #0d47a1;
        transform: scale(1.05);
    }
    .btn-chat-send i {
        font-size: 20px;
    }

    /* Responsive Breakpoints */
    @media only screen and (max-width: 992px) {
        .kpi-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .module-cards-grid {
            grid-template-columns: 1fr;
        }
        .student-bio-grid {
            grid-template-columns: 1fr;
            text-align: left;
        }
    }

    @media only screen and (max-width: 600px) {
        .transcript-card {
            padding: 20px 14px;
            border-radius: var(--mat-radius-sm);
        }
        .transcript-title {
            font-size: 1.4rem;
        }
        .kpi-metrics-grid {
            grid-template-columns: 1fr;
        }
        .student-bio-card {
            padding: 16px 14px;
        }
        .bio-item {
            flex-direction: column;
            gap: 2px;
        }
        .bio-label {
            min-width: auto;
        }
        .results-actions-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .results-btn-group {
            flex-direction: column;
            width: 100%;
        }
        .action-btn {
            width: 100% !important;
        }
        .ai-chat-thread {
            padding: 16px 14px;
            max-height: 440px;
        }
        .chat-bubble {
            max-width: 92%;
        }
        .ai-chat-input-area {
            padding: 14px 12px;
        }
    }

    /* ==========================================================================
       Print & PDF Clean Export Styles
       ========================================================================== */
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        body * {
            visibility: hidden;
        }
        #printableTranscript, #printableTranscript * {
            visibility: visible;
        }
        #printableTranscript {
            position: absolute;
            left: 0;
            top: 0;
            width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .no-print, .view-switcher, .btn-quick-coach {
            display: none !important;
        }
        .results-table-container {
            overflow: visible !important;
            border: 1px solid #ccc !important;
            box-shadow: none !important;
        }
        .kpi-card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="row" style="margin-bottom: 0;">
    <div class="col s12 m10 offset-m1" style="box-sizing: border-box; padding: 0 4px;">
        
        <!-- Top Actions Bar -->
        <div class="no-print results-actions-bar">
            <a href="dashboard.php" class="btn-dashboard-back waves-effect">
                <i class="material-icons tiny">arrow_back</i> Dashboard
            </a>
            <div class="results-btn-group">
                <a href="#aiCoachCard" class="btn action-btn btn-coach-gradient waves-effect waves-light">
                    <i class="material-icons">auto_awesome</i> AI Academic Coach
                </a>
                <button onclick="window.print();" class="btn action-btn blue darken-2 waves-effect waves-light">
                    <i class="material-icons">print</i> Print Transcript
                </button>
                <button id="downloadPdf" class="btn action-btn green darken-2 waves-effect waves-light">
                    <i class="material-icons">picture_as_pdf</i> Download Official PDF
                </button>
            </div>
        </div>

        <!-- ==========================================================================
             OFFICIAL STUDENT ACADEMIC TRANSCRIPT (Printable Section)
             ========================================================================== -->
        <div id="printableTranscript" class="transcript-card">
            
            <!-- Official Header -->
            <div class="transcript-header">
                <div class="transcript-dept-lead">
                    <i class="material-icons tiny">account_balance</i>
                    Republic of South Africa &bull; Department of Forestry, Fisheries and the Environment
                </div>
                <h1 class="transcript-title">Student Academic Transcript</h1>
                <p class="transcript-subtitle">
                    ICT Services: Infrastructure &amp; Security Support &bull; Corporate Management Services
                </p>
            </div>

            <!-- Student Bio & Identification Card -->
            <div class="student-bio-card">
                <div class="student-bio-grid">
                    <div class="student-avatar-circle">
                        <?php 
                            $fn = $student->getFirstName() ?: 'A';
                            $sn = $student->getSurname() ?: 'M';
                            echo htmlspecialchars(strtoupper(substr($fn, 0, 1) . substr($sn, 0, 1)));
                        ?>
                    </div>
                    
                    <div class="bio-data-group">
                        <div class="bio-item">
                            <span class="bio-label">Student Name:</span>
                            <span class="bio-value"><?php echo htmlspecialchars($student->getFullName()); ?></span>
                        </div>
                        <div class="bio-item">
                            <span class="bio-label">Student Number:</span>
                            <span class="student-num-badge tooltipped" data-position="top" data-tooltip="Click to copy student ID" onclick="copyStudentNumber('<?php echo htmlspecialchars($student->getStudentNumber()); ?>')">
                                <?php echo htmlspecialchars($student->getStudentNumber()); ?>
                                <i class="material-icons tiny" style="font-size: 13px;">content_copy</i>
                            </span>
                        </div>
                        <div class="bio-item">
                            <span class="bio-label">National ID Number:</span>
                            <span class="bio-value"><?php echo htmlspecialchars($student->getIdNumber() ?: '0504285612081'); ?></span>
                        </div>
                    </div>

                    <div class="bio-data-group">
                        <div class="bio-item">
                            <span class="bio-label">Course of Study:</span>
                            <span class="bio-value"><?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?></span>
                        </div>
                        <div class="bio-item">
                            <span class="bio-label">Program Duration:</span>
                            <span class="bio-value"><?php echo htmlspecialchars($activeEnrollment['duration'] ?? '4 Years'); ?></span>
                        </div>
                        <div class="bio-item">
                            <span class="bio-label">Enrollment Status:</span>
                            <span class="bio-status-row">
                                <span class="status-tag status-active"><?php echo htmlspecialchars($student->getAcademicStatus() ?: 'Active'); ?></span>
                                <span class="status-tag-honor"><?php echo htmlspecialchars($academicHonors); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Academic Metrics KPI Grid -->
            <div class="kpi-metrics-grid">
                <!-- KPI 1: Cumulative Average -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon-wrap kpi-icon-blue">
                            <i class="material-icons">school</i>
                        </div>
                        <span class="kpi-badge kpi-badge-green">
                            <?php echo $termAverage >= 75 ? 'Distinction (A)' : ($termAverage >= 60 ? 'Merit (B)' : 'Passed (C)'); ?>
                        </span>
                    </div>
                    <div>
                        <div class="kpi-val"><?php echo $termAverage !== null ? number_format($termAverage, 2) . '%' : 'Pending'; ?></div>
                        <div class="kpi-label">Cumulative Average</div>
                    </div>
                    <div class="kpi-bar-wrap">
                        <div class="kpi-bar-fill" style="width: <?php echo min(100, max(0, (float)$termAverage)); ?>%; background: #16a34a;"></div>
                    </div>
                    <p class="kpi-subtext">
                        <i class="material-icons tiny">functions</i> Formula: (Q1+Q2+Q3+Q4) / 4
                    </p>
                </div>

                <!-- KPI 2: Total Credits -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon-wrap kpi-icon-purple">
                            <i class="material-icons">stars</i>
                        </div>
                        <span class="kpi-badge kpi-badge-purple">100% Enrolled</span>
                    </div>
                    <div>
                        <div class="kpi-val"><?php echo $totalCredits; ?> Credits</div>
                        <div class="kpi-label">Enrolled Modules</div>
                    </div>
                    <div class="kpi-bar-wrap">
                        <div class="kpi-bar-fill" style="width: 100%; background: #9333ea;"></div>
                    </div>
                    <p class="kpi-subtext">
                        <i class="material-icons tiny">layers</i> <?php echo count($moduleResults); ?> Enrolled Technical Modules
                    </p>
                </div>

                <!-- KPI 3: Pass Rate -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon-wrap kpi-icon-green">
                            <i class="material-icons">verified_user</i>
                        </div>
                        <span class="kpi-badge kpi-badge-green"><?php echo $passedCount; ?> / <?php echo $resultCount; ?> Passed</span>
                    </div>
                    <div>
                        <div class="kpi-val"><?php echo $passRate; ?>%</div>
                        <div class="kpi-label">Module Pass Rate</div>
                    </div>
                    <div class="kpi-bar-wrap">
                        <div class="kpi-bar-fill" style="width: <?php echo $passRate; ?>%; background: #22c55e;"></div>
                    </div>
                    <p class="kpi-subtext">
                        <i class="material-icons tiny">check_circle</i> Good Academic Standing
                    </p>
                </div>

                <!-- KPI 4: Academic Top Spotlight -->
                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon-wrap kpi-icon-amber">
                            <i class="material-icons">emoji_events</i>
                        </div>
                        <span class="kpi-badge kpi-badge-amber">Spotlight</span>
                    </div>
                    <div>
                        <div class="kpi-val" style="font-size: 1.3rem;">
                            <?php echo $highestModule ? htmlspecialchars($highestModule['module_code']) : 'None'; ?>
                            <span style="font-size: 0.9rem; font-weight: 600; color: #16a34a;">
                                (<?php echo $highestModule ? number_format($highestModule['average'], 1) . '%' : '-'; ?>)
                            </span>
                        </div>
                        <div class="kpi-label">Top Performance</div>
                    </div>
                    <div class="kpi-bar-wrap">
                        <div class="kpi-bar-fill" style="width: <?php echo $highestAvg > 0 ? $highestAvg : 0; ?>%; background: #d97706;"></div>
                    </div>
                    <p class="kpi-subtext">
                        <i class="material-icons tiny">trending_up</i> Lowest: <?php echo $lowestModule ? htmlspecialchars($lowestModule['module_code']) . ' (' . number_format($lowestModule['average'], 1) . '%)' : '-'; ?>
                    </p>
                </div>
            </div>

            <!-- Quarterly Module Performance Section -->
            <div class="section-header-bar">
                <h2 class="section-heading">
                    <i class="material-icons" style="color: var(--mat-primary);">assessment</i>
                    Quarterly Module Performance
                </h2>
                
                <div class="view-switcher no-print">
                    <button id="btnViewTable" class="view-btn active" onclick="switchPerformanceView('table')">
                        <i class="material-icons">table_chart</i> Table View
                    </button>
                    <button id="btnViewCards" class="view-btn" onclick="switchPerformanceView('cards')">
                        <i class="material-icons">grid_view</i> Cards View
                    </button>
                </div>
            </div>

            <?php if (!empty($moduleResults)): ?>
                
                <!-- 1. Detailed Table View (Optimized with no clipped marks) -->
                <div id="moduleTableView" class="results-table-container">
                    <table class="results-mat-table">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;">Module & Lecturer</th>
                                <th style="min-width: 70px;">Credits</th>
                                <th style="min-width: 80px;">Q1 (25%)</th>
                                <th style="min-width: 80px;">Q2 (25%)</th>
                                <th style="min-width: 80px;">Q3 (25%)</th>
                                <th style="min-width: 80px;">Q4 (25%)</th>
                                <th style="min-width: 100px;">Final Average</th>
                                <th style="min-width: 110px;">Outcome</th>
                                <th class="no-print" style="min-width: 70px; text-align: center;">Coach</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moduleResults as $res): ?>
                                <?php 
                                    $modRes = new ModuleResult($res);
                                    $grade = $modRes->getGradeStatus();
                                    $avg = $res['average'] !== null ? (float)$res['average'] : null;

                                    $gradeClass = 'grade-chip-pending';
                                    if ($avg !== null) {
                                        if ($avg >= 75) $gradeClass = 'grade-chip-distinction';
                                        elseif ($avg >= 60) $gradeClass = 'grade-chip-merit';
                                        elseif ($avg >= 50) $gradeClass = 'grade-chip-pass';
                                        else $gradeClass = 'grade-chip-fail';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="module-cell-primary">
                                            <span class="module-code-pill"><?php echo htmlspecialchars($res['module_code']); ?></span>
                                            <span class="module-name-title"><?php echo htmlspecialchars($res['module_name']); ?></span>
                                            <span class="module-lecturer-text">
                                                <i class="material-icons tiny">person_outline</i>
                                                <?php echo htmlspecialchars($res['lecturers'] ?: 'Institutional Faculty'); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="credits-chip"><?php echo htmlspecialchars($res['credits']); ?> cr</span>
                                    </td>
                                    <td>
                                        <span class="mark-val <?php echo $res['quarter1'] >= 75 ? 'mark-distinction' : ($res['quarter1'] >= 50 ? 'mark-pass' : ($res['quarter1'] !== null ? 'mark-fail' : 'mark-pending')); ?>">
                                            <?php echo $res['quarter1'] !== null ? number_format($res['quarter1'], 1) . '%' : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="mark-val <?php echo $res['quarter2'] >= 75 ? 'mark-distinction' : ($res['quarter2'] >= 50 ? 'mark-pass' : ($res['quarter2'] !== null ? 'mark-fail' : 'mark-pending')); ?>">
                                            <?php echo $res['quarter2'] !== null ? number_format($res['quarter2'], 1) . '%' : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="mark-val <?php echo $res['quarter3'] >= 75 ? 'mark-distinction' : ($res['quarter3'] >= 50 ? 'mark-pass' : ($res['quarter3'] !== null ? 'mark-fail' : 'mark-pending')); ?>">
                                            <?php echo $res['quarter3'] !== null ? number_format($res['quarter3'], 1) . '%' : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="mark-val <?php echo $res['quarter4'] >= 75 ? 'mark-distinction' : ($res['quarter4'] >= 50 ? 'mark-pass' : ($res['quarter4'] !== null ? 'mark-fail' : 'mark-pending')); ?>">
                                            <?php echo $res['quarter4'] !== null ? number_format($res['quarter4'], 1) . '%' : '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="avg-cell-box">
                                            <span class="avg-score-text">
                                                <?php echo $avg !== null ? number_format($avg, 2) . '%' : 'Pending'; ?>
                                            </span>
                                            <?php if ($avg !== null): ?>
                                                <div class="avg-mini-bar">
                                                    <div class="avg-mini-fill" style="width: <?php echo min(100, max(0, $avg)); ?>%;"></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="grade-chip <?php echo $gradeClass; ?>">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                    <td class="no-print" style="text-align: center;">
                                        <button class="btn-quick-coach tooltipped" data-position="left" data-tooltip="Ask AI Coach for <?php echo htmlspecialchars($res['module_code']); ?> study plan" onclick="askAiQuestion('How can I boost or maintain my distinction in <?php echo htmlspecialchars($res['module_code']); ?> <?php echo htmlspecialchars($res['module_name']); ?>?')">
                                            <i class="material-icons tiny">auto_awesome</i> Coach
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Responsive Cards Grid View -->
                <div id="moduleCardsView" class="module-cards-grid" style="display: none;">
                    <?php foreach ($moduleResults as $res): ?>
                        <?php 
                            $modRes = new ModuleResult($res);
                            $grade = $modRes->getGradeStatus();
                            $avg = $res['average'] !== null ? (float)$res['average'] : null;
                            $gradeClass = ($avg !== null && $avg >= 75) ? 'grade-chip-distinction' : (($avg !== null && $avg >= 50) ? 'grade-chip-pass' : 'grade-chip-fail');
                        ?>
                        <div class="module-performance-card">
                            <div class="mod-card-header">
                                <div>
                                    <span class="module-code-pill"><?php echo htmlspecialchars($res['module_code']); ?></span>
                                    <h6 style="margin: 6px 0 2px 0; font-weight: 700; color: #0f172a; font-size: 1rem;"><?php echo htmlspecialchars($res['module_name']); ?></h6>
                                    <span class="module-lecturer-text">
                                        <i class="material-icons tiny">person_outline</i> <?php echo htmlspecialchars($res['lecturers'] ?: 'Institutional Faculty'); ?>
                                    </span>
                                </div>
                                <span class="grade-chip <?php echo $gradeClass; ?>">
                                    <?php echo $grade['grade']; ?>
                                </span>
                            </div>

                            <!-- 4-Quarter Meter Grid -->
                            <div class="quarters-meter-grid">
                                <div class="quarter-meter-item">
                                    <span class="q-label">Q1 (25%)</span>
                                    <span class="q-val"><?php echo $res['quarter1'] !== null ? number_format($res['quarter1'], 1) . '%' : '-'; ?></span>
                                </div>
                                <div class="quarter-meter-item">
                                    <span class="q-label">Q2 (25%)</span>
                                    <span class="q-val"><?php echo $res['quarter2'] !== null ? number_format($res['quarter2'], 1) . '%' : '-'; ?></span>
                                </div>
                                <div class="quarter-meter-item">
                                    <span class="q-label">Q3 (25%)</span>
                                    <span class="q-val"><?php echo $res['quarter3'] !== null ? number_format($res['quarter3'], 1) . '%' : '-'; ?></span>
                                </div>
                                <div class="quarter-meter-item">
                                    <span class="q-label">Q4 (25%)</span>
                                    <span class="q-val"><?php echo $res['quarter4'] !== null ? number_format($res['quarter4'], 1) . '%' : '-'; ?></span>
                                </div>
                            </div>

                            <div class="mod-card-footer">
                                <div>
                                    <span style="font-size: 0.76rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Final Average:</span>
                                    <strong style="font-size: 1.15rem; color: #0f172a; margin-left: 6px;">
                                        <?php echo $avg !== null ? number_format($avg, 2) . '%' : 'Pending'; ?>
                                    </strong>
                                </div>
                                <button class="btn-quick-coach no-print" onclick="askAiQuestion('Give me 3 high-impact revision strategies for <?php echo htmlspecialchars($res['module_code']); ?> <?php echo htmlspecialchars($res['module_name']); ?> based on my quarterly performance')">
                                    <i class="material-icons tiny">auto_awesome</i> Ask Coach
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary Formula & Standing Note -->
                <div class="transcript-summary-box">
                    <div>
                        <strong style="color: #0f172a; font-size: 0.94rem;">Institutional Average Formula:</strong>
                        <p class="summary-formula-text">
                            Calculated per academic regulations: <code>calculateAverage() = (Q1 + Q2 + Q3 + Q4) / 4</code>.
                        </p>
                    </div>
                    <div>
                        <span class="status-badge-active" style="background: #e0f2fe; color: #0369a1;">
                            <i class="material-icons tiny">verified</i> Verified by Academic Registrar
                        </span>
                    </div>
                </div>

            <?php else: ?>
                <div class="card-panel grey lighten-4 center-align" style="border-radius: var(--mat-radius-md); padding: 30px;">
                    <i class="material-icons grey-text" style="font-size: 42px;">assignment_late</i>
                    <p class="grey-text text-darken-1" style="font-weight: 500; margin-top: 8px;">
                        No academic quarterly marks are currently recorded for your profile. Please check back after quarterly grading cycles.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Official Seal & Disclaimer Footer -->
            <div class="transcript-official-footer">
                <p style="margin: 4px 0; font-weight: 600; color: #64748b;">
                    Department of Forestry, Fisheries & the Environment &bull; Student Management Information System
                </p>
                <p style="margin: 4px 0;">
                    Official Academic Transcript &bull; Issued for Student ID: <code><?php echo htmlspecialchars($student->getStudentNumber()); ?></code> &bull; Generated: <?php echo date("F j, Y, g:i A T"); ?>
                </p>
            </div>
        </div>

        <!-- ==========================================================================
             AI ACADEMIC SUCCESS COACH (Conversational Material AI Chat)
             ========================================================================== -->
        <div id="aiCoachCard" class="no-print ai-coach-card">
            
            <!-- Coach Card Header -->
            <div class="ai-coach-card-header">
                <div class="ai-coach-branding">
                    <div class="ai-coach-avatar-bubble">
                        <i class="material-icons">psychology</i>
                    </div>
                    <div class="ai-coach-headings">
                        <h3 class="ai-coach-main-title">AI Academic Success Coach</h3>
                        <p class="ai-coach-main-desc">
                            Personalized diagnostic &amp; strategy linked to your official transcript
                        </p>
                    </div>
                </div>

                <div class="ai-coach-header-controls">
                    <button id="runAiAdvisorBtn" onclick="runAiCoach('')" class="btn btn-run-diagnosis waves-effect waves-light">
                        <i class="material-icons tiny">analytics</i> Full Diagnostic
                    </button>
                    <button id="clearChatBtn" onclick="clearAiChat()" class="btn-clear-chat tooltipped" data-position="top" data-tooltip="Clear chat history and restart">
                        <i class="material-icons tiny">delete_sweep</i> Clear
                    </button>
                </div>
            </div>

            <!-- Conversational Chat Stream Feed -->
            <div id="aiChatThread" class="ai-chat-thread">
                
                <!-- Initial Welcome Message from AI Coach -->
                <div class="chat-msg-row msg-assistant" id="aiWelcomeMessage">
                    <div class="chat-avatar avatar-coach">
                        <i class="material-icons tiny" style="font-size: 18px;">auto_awesome</i>
                    </div>
                    <div class="chat-bubble coach-bubble">
                        <div class="coach-bubble-header">
                            <span class="coach-bubble-title">
                                <i class="material-icons tiny" style="color: #4f46e5;">smart_toy</i> AI Academic Coach
                            </span>
                        </div>
                        <div class="coach-bubble-body">
                            <p style="margin: 0; line-height: 1.6; font-size: 0.95rem; color: #334155;">
                                Hello <strong><?php echo htmlspecialchars($student->getFirstName() ?: 'Avuyile'); ?></strong>! I'm your AI Academic Coach. Ask me anything about your modules, revision strategies, or exam preparation below.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Chat Messages Will Be Appended Here -->
                <div id="aiDynamicMessages"></div>

                <!-- Sleek Modern Thinking Indicator -->
                <div id="aiTypingIndicator" class="chat-msg-row msg-assistant ai-thinking-row" style="display: none;">
                    <div class="chat-avatar avatar-coach">
                        <i class="material-icons tiny" style="font-size: 18px;">auto_awesome</i>
                    </div>
                    <div class="ai-thinking-bubble-clean">
                        <div class="thinking-dots">
                            <span class="thinking-dot"></span>
                            <span class="thinking-dot"></span>
                            <span class="thinking-dot"></span>
                        </div>
                        <span id="aiThinkingStatusText" class="thinking-dynamic-label">
                            Analyzing module performance &amp; quarterly transcript...
                        </span>
                    </div>
                </div>

            </div>

            <!-- Material 3 Floating Chat Input Capsule -->
            <div class="ai-chat-input-area">
                <!-- Suggested Prompt Bubbles near input field -->
                <div class="ai-suggested-bubbles">
                    <button type="button" class="ai-prompt-bubble" onclick="askAiQuestion('How can I boost my marks in my lowest performing module?')">
                        Boost Lowest Module
                    </button>
                    <button type="button" class="ai-prompt-bubble" onclick="askAiQuestion('Create a structured 2-week daily revision schedule tailored to my enrolled modules')">
                        2-Week Study Plan
                    </button>
                    <button type="button" class="ai-prompt-bubble" onclick="askAiQuestion('What active recall and spaced repetition techniques work best for technical modules like SQL and OOP?')">
                        Active Recall Tactics
                    </button>
                    <button type="button" class="ai-prompt-bubble" onclick="askAiQuestion('What specific target scores do I need on upcoming assessments to maintain my Distinction status?')">
                        Distinction Targets
                    </button>
                    <button type="button" class="ai-prompt-bubble" onclick="askAiQuestion('Give me 5 practical tips to manage exam time pressure and solve complex coding questions')">
                        Exam Strategy
                    </button>
                </div>

                <form id="aiChatForm" onsubmit="handleAiFormSubmit(event)" style="margin: 0;">
                    <div class="ai-input-capsule">
                        <i class="material-icons ai-sparkle-icon">auto_awesome</i>
                        <input type="text" id="aiQuestionInput" class="browser-default ai-chat-input" placeholder="Ask AI Coach anything about your modules, exam tips, study plans..." autocomplete="off">
                        <button type="button" id="btnInputClear" class="btn-input-clear" onclick="clearInputField()" title="Clear text">
                            <i class="material-icons tiny">close</i>
                        </button>
                        <button type="submit" id="btnAskAi" class="btn-chat-send tooltipped" data-position="top" data-tooltip="Send question to AI Coach">
                            <i class="material-icons">send</i>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>
</div>

<!-- ==========================================================================
     SCRIPTS & CLIENT LOGIC
     ========================================================================== -->
<!-- jsPDF and html2canvas for High-Fidelity PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<!-- KaTeX, mhchem, Marked, Highlight.js, DOMPurify for Atlas-Grade AI Rendering Pipeline -->
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/mhchem.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.8/purify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<script>
// Student marks payload directly from PHP
const studentDataPayload = <?php echo $studentPayloadJson ?: '{}'; ?>;
const currentStudentName = <?php echo json_encode($studentDisplayName); ?>;
const currentStudentInitials = <?php echo json_encode($studentInitials); ?>;

// In-Memory Multi-turn Chat Conversation History
let chatHistory = [];

// Initialize Tooltips and UI Elements
document.addEventListener('DOMContentLoaded', function() {
    var tooltippedElems = document.querySelectorAll('.tooltipped');
    if (typeof M !== 'undefined' && M.Tooltip) {
        M.Tooltip.init(tooltippedElems);
    }

    // Input clear button toggle
    const input = document.getElementById('aiQuestionInput');
    const clearBtn = document.getElementById('btnInputClear');
    if (input && clearBtn) {
        input.addEventListener('input', function() {
            clearBtn.style.display = this.value.trim() ? 'flex' : 'none';
        });
    }
});

function clearInputField() {
    const input = document.getElementById('aiQuestionInput');
    const clearBtn = document.getElementById('btnInputClear');
    if (input) {
        input.value = '';
        input.focus();
    }
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }
}

// 1-Click Copy Student ID Number with Toast
function copyStudentNumber(num) {
    if (!num) return;
    navigator.clipboard.writeText(num).then(function() {
        if (typeof M !== 'undefined' && M.toast) {
            M.toast({html: '<i class="material-icons left tiny">check</i> Student ID copied to clipboard!', displayLength: 2000, classes: 'green darken-2'});
        } else {
            alert('Copied Student ID: ' + num);
        }
    });
}

// Performance View Switcher (Table vs Cards)
function switchPerformanceView(viewType) {
    const tableView = document.getElementById('moduleTableView');
    const cardsView = document.getElementById('moduleCardsView');
    const btnTable = document.getElementById('btnViewTable');
    const btnCards = document.getElementById('btnViewCards');

    if (viewType === 'cards') {
        if (tableView) tableView.style.display = 'none';
        if (cardsView) cardsView.style.display = 'grid';
        if (btnTable) btnTable.classList.remove('active');
        if (btnCards) btnCards.classList.add('active');
    } else {
        if (tableView) tableView.style.display = 'block';
        if (cardsView) cardsView.style.display = 'none';
        if (btnTable) btnTable.classList.add('active');
        if (btnCards) btnCards.classList.remove('active');
    }
}

// High-Fidelity PDF Generator using html2canvas & jsPDF
document.getElementById('downloadPdf').addEventListener('click', function () {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="material-icons left tiny">hourglass_top</i> Generating PDF...';
    btn.classList.add('disabled');

    const { jsPDF } = window.jspdf;
    const reportElement = document.getElementById('printableTranscript');

    html2canvas(reportElement, {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save("transcript-<?php echo htmlspecialchars($student->getStudentNumber()); ?>.pdf");
        
        btn.innerHTML = originalText;
        btn.classList.remove('disabled');

        if (typeof M !== 'undefined' && M.toast) {
            M.toast({html: '<i class="material-icons left tiny">download_done</i> Transcript PDF downloaded!', classes: 'green darken-2'});
        }
    }).catch(err => {
        console.error('PDF generation error:', err);
        btn.innerHTML = originalText;
        btn.classList.remove('disabled');
        if (typeof M !== 'undefined' && M.toast) {
            M.toast({html: 'Could not generate PDF. Please try Print Transcript.', classes: 'red darken-2'});
        }
    });
});

// ==========================================================================
// Atlas-Grade 7-Stage "Shield-First" AI Markdown & Rich Text Formatter
// ==========================================================================
function formatAiMarkdown(rawText) {
    if (!rawText) return '';

    let text = String(rawText);

    // -------------------------------------------------------------
    // Stage 1: Streaming Code Fence Balance & Code Shielding
    // -------------------------------------------------------------
    // Auto-close open code fence if model streamed odd count
    const fenceCount = (text.match(/```/g) || []).length;
    if (fenceCount % 2 === 1) {
        text += '\n```\n';
    }

    const codeBlocks = [];
    // Extract block code: ```lang\ncode\n```
    text = text.replace(/```([a-zA-Z0-9_\-\.\+]*)[ \t]*\r?\n([\s\S]*?)```/g, function(match, lang, code) {
        const id = codeBlocks.length;
        codeBlocks.push({
            lang: (lang || '').trim().toLowerCase() || 'text',
            code: code
        });
        return `\n\n@@CODE_BLOCK_${id}@@\n\n`;
    });

    const inlineCodes = [];
    // Extract inline code: `code`
    text = text.replace(/`([^`\r\n]+)`/g, function(match, code) {
        const id = inlineCodes.length;
        inlineCodes.push(code);
        return `@@INLINE_CODE_${id}@@`;
    });

    // -------------------------------------------------------------
    // Stage 2: Math Extraction & Shielding (KaTeX + LaTeX + mhchem)
    // -------------------------------------------------------------
    const mathBlocks = [];
    // 1. Block environments: \begin{align}...\end{align}, etc.
    text = text.replace(/(\\begin\{(?:align|equation|matrix|pmatrix|bmatrix|vmatrix|cases|gather|split)\*?\}[\s\S]*?\\end\{(?:align|equation|matrix|pmatrix|bmatrix|vmatrix|cases|gather|split)\*?\})/g, function(match, formula) {
        const id = mathBlocks.length;
        mathBlocks.push(formula.trim());
        return `\n\n@@MATH_BLOCK_${id}@@\n\n`;
    });

    // 2. Display delimiters: $$...$$ and \[...\]
    text = text.replace(/\$\$([\s\S]*?)\$\$/g, function(match, formula) {
        const id = mathBlocks.length;
        mathBlocks.push(formula.trim());
        return `\n\n@@MATH_BLOCK_${id}@@\n\n`;
    });
    text = text.replace(/\\\[([\s\S]*?)\\\]/g, function(match, formula) {
        const id = mathBlocks.length;
        mathBlocks.push(formula.trim());
        return `\n\n@@MATH_BLOCK_${id}@@\n\n`;
    });

    const mathInlines = [];
    // 3. Inline delimiters: \(...\)
    text = text.replace(/\\\(([\s\S]*?)\\\)/g, function(match, formula) {
        const id = mathInlines.length;
        mathInlines.push(formula.trim());
        return `@@MATH_INLINE_${id}@@`;
    });

    // 4. Inline $...$ with Atlas currency safeguard regex ($50.00 not treated as math)
    text = text.replace(/(^|[^\$\w])\$((?!\s)[^\$\n]+?(?<!\s))\$(?!\d)/g, function(match, prefix, formula) {
        if (/^[\d,]+(\.\d+)?$/.test(formula.trim())) {
            return match; // Skip currency
        }
        const id = mathInlines.length;
        mathInlines.push(formula.trim());
        return `${prefix}@@MATH_INLINE_${id}@@`;
    });

    // -------------------------------------------------------------
    // Stage 3: Star Normalization ("Asterisk Flaw" Fix)
    // -------------------------------------------------------------
    // Fix loose interior spaces: ** bold text ** -> **bold text**
    text = text.replace(/\*\*([ \t]*)([^\*\r\n]+?)([ \t]*)\*\*/g, function(match, lead, content, trail) {
        const trimmed = content.trim();
        return trimmed ? `**${trimmed}**` : match;
    });
    text = text.replace(/(^|[^*])\*([ \t]*)([^*\r\n]+?)([ \t]*)\*([^*]|$)/g, function(match, pre, lead, content, trail, post) {
        const trimmed = content.trim();
        return trimmed ? `${pre}*${trimmed}*${post}` : match;
    });

    // Callout Cards transformation for Action Plan / Summary / Key Takeaways
    text = text.replace(/(?:^|\n)(Action Plan|Key Takeaways?|Summary|Immediate Next Steps?):\s*(.*?)(?=\n\n|\n[#*-]|$)/gis, function(match, label, content) {
        const cleanContent = content.trim().replace(/→|->/g, '➔');
        return `\n\n<div class="ai-action-card"><div class="ai-action-header"><i class="material-icons tiny">flag</i> ${escapeHtml(label)}</div><div class="ai-action-body">${cleanContent}</div></div>\n\n`;
    });

    // -------------------------------------------------------------
    // Stage 4: Markdown Parsing via marked.js (with custom table renderer)
    // -------------------------------------------------------------
    let html = '';
    if (typeof marked !== 'undefined') {
        const renderer = new marked.Renderer();

        // Responsive Data Table Renderer
        renderer.table = function(header, body) {
            return `
                <div class="ai-table-container">
                    <table class="ai-rich-table">
                        <thead>${header}</thead>
                        <tbody>${body}</tbody>
                    </table>
                </div>
            `;
        };

        // Secure external links
        renderer.link = function(href, title, linkText) {
            return `<a href="${href}" target="_blank" rel="noopener noreferrer"${title ? ` title="${title}"` : ''}>${linkText}</a>`;
        };

        // Semantic blockquote
        renderer.blockquote = function(quote) {
            return `<blockquote class="ai-blockquote">${quote}</blockquote>`;
        };

        html = marked.parse(text, {
            renderer: renderer,
            gfm: true,
            breaks: true
        });
    } else {
        // Fallback parser if CDN is delayed
        html = text
            .replace(/^### (.*$)/gim, '<h5>$1</h5>')
            .replace(/^## (.*$)/gim, '<h4>$1</h4>')
            .replace(/^# (.*$)/gim, '<h3>$1</h3>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n\n/g, '<br><br>');
    }

    // -------------------------------------------------------------
    // Stage 5: HTML Sanitization via DOMPurify
    // -------------------------------------------------------------
    if (typeof DOMPurify !== 'undefined') {
        html = DOMPurify.sanitize(html, {
            ADD_TAGS: [
                'kbd', 'mark', 'details', 'summary', 'svg', 'path',
                'math', 'semantics', 'mrow', 'mo', 'mn', 'mi', 'msup', 'msub', 'mfrac', 'msqrt', 'annotation',
                'button', 'i', 'span', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'pre', 'code'
            ],
            ADD_ATTR: ['target', 'viewBox', 'data-latex', 'xmlns', 'display', 'class', 'style', 'title', 'onclick']
        });
    }

    // -------------------------------------------------------------
    // Stage 6: Math Substitution & KaTeX Rendering
    // -------------------------------------------------------------
    mathBlocks.forEach((latex, idx) => {
        let rendered = '';
        if (typeof katex !== 'undefined') {
            try {
                rendered = katex.renderToString(latex, { displayMode: true, throwOnError: false });
            } catch (e) {
                rendered = `<code>${escapeHtml(latex)}</code>`;
            }
        } else {
            rendered = `<code>${escapeHtml(latex)}</code>`;
        }
        const encoded = encodeURIComponent(latex);
        const mathHtml = `
            <div class="ai-math-block-card">
                <div class="ai-math-body">${rendered}</div>
                <button type="button" class="btn-copy-latex" onclick="copyLatexFormula(this, decodeURIComponent('${encoded}'))" title="Copy LaTeX formula">
                    <i class="material-icons tiny">functions</i> Copy LaTeX
                </button>
            </div>
        `;
        html = html.replace(`@@MATH_BLOCK_${idx}@@`, mathHtml);
    });

    mathInlines.forEach((latex, idx) => {
        let rendered = '';
        if (typeof katex !== 'undefined') {
            try {
                rendered = katex.renderToString(latex, { displayMode: false, throwOnError: false });
            } catch (e) {
                rendered = `<code>${escapeHtml(latex)}</code>`;
            }
        } else {
            rendered = `<code>${escapeHtml(latex)}</code>`;
        }
        html = html.replace(`@@MATH_INLINE_${idx}@@`, `<span class="ai-math-inline">${rendered}</span>`);
    });

    // -------------------------------------------------------------
    // Stage 7: Code Block Substitution & Toolbar Enhancement
    // -------------------------------------------------------------
    codeBlocks.forEach((block, idx) => {
        const lang = (block.lang || 'code').toLowerCase();
        const code = block.code;
        const lineCount = code.split('\n').length;

        let highlighted = escapeHtml(code);
        if (typeof hljs !== 'undefined') {
            try {
                if (lang && hljs.getLanguage(lang)) {
                    highlighted = hljs.highlight(code, { language: lang }).value;
                } else {
                    highlighted = hljs.highlightAuto(code).value;
                }
            } catch (e) {
                highlighted = escapeHtml(code);
            }
        }

        const encoded = encodeURIComponent(code);
        const codeCardHtml = `
            <div class="ai-code-block-card">
                <div class="ai-code-header">
                    <div class="ai-code-lang-badge">
                        <i class="material-icons tiny">terminal</i>
                        <span>${escapeHtml(lang.toUpperCase())}</span>
                        <span class="ai-code-lines-count">${lineCount} lines</span>
                    </div>
                    <div class="ai-code-actions">
                        <button type="button" class="btn-code-wrap" onclick="toggleCodeWrap(this)" title="Toggle word wrap">
                            <i class="material-icons tiny">wrap_text</i> Wrap
                        </button>
                        <button type="button" class="btn-code-copy" onclick="copyCodeBlock(this, decodeURIComponent('${encoded}'))" title="Copy code">
                            <i class="material-icons tiny">content_copy</i> Copy Code
                        </button>
                    </div>
                </div>
                <pre class="ai-code-pre"><code class="hljs language-${escapeHtml(lang)}">${highlighted}</code></pre>
            </div>
        `;
        html = html.replace(`@@CODE_BLOCK_${idx}@@`, codeCardHtml);
    });

    inlineCodes.forEach((code, idx) => {
        html = html.replace(`@@INLINE_CODE_${idx}@@`, `<code class="ai-code-inline">${escapeHtml(code)}</code>`);
    });

    return `<div class="ai-rendered-output">${html}</div>`;
}

// Copy Code Block to Clipboard
function copyCodeBlock(btn, rawCode) {
    if (!navigator.clipboard) {
        const textArea = document.createElement("textarea");
        textArea.value = rawCode;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showCodeBtnFeedback(btn);
        return;
    }
    navigator.clipboard.writeText(rawCode).then(() => {
        showCodeBtnFeedback(btn);
    });
}

function showCodeBtnFeedback(btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="material-icons tiny" style="color: #4ade80;">check</i> Copied!';
    btn.style.borderColor = '#4ade80';
    btn.style.color = '#4ade80';
    setTimeout(() => {
        btn.innerHTML = orig;
        btn.style.borderColor = '';
        btn.style.color = '';
    }, 2000);
}

// Toggle Pre Word Wrap
function toggleCodeWrap(btn) {
    const card = btn.closest('.ai-code-block-card');
    if (!card) return;
    const pre = card.querySelector('pre');
    if (!pre) return;
    const isWrapped = pre.style.whiteSpace === 'pre-wrap';
    pre.style.whiteSpace = isWrapped ? 'pre' : 'pre-wrap';
    pre.style.wordBreak = isWrapped ? 'normal' : 'break-word';
    btn.classList.toggle('active', !isWrapped);
}

// Copy LaTeX Formula
function copyLatexFormula(btn, latex) {
    navigator.clipboard.writeText(latex).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="material-icons tiny" style="color: #16a34a;">check</i> Copied!';
        setTimeout(() => {
            btn.innerHTML = orig;
        }, 2000);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Copy advice text to clipboard with feedback
function copyAdviceText(btnElement, rawAdvice) {
    navigator.clipboard.writeText(rawAdvice).then(function() {
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="material-icons tiny" style="color:#16a34a;">check</i> Copied!';
        btnElement.style.borderColor = '#16a34a';
        btnElement.style.color = '#16a34a';
        setTimeout(() => {
            btnElement.innerHTML = originalText;
            btnElement.style.borderColor = '';
            btnElement.style.color = '';
        }, 2200);

        if (typeof M !== 'undefined' && M.toast) {
            M.toast({html: '<i class="material-icons left tiny">content_copy</i> Coaching advice copied to clipboard!', displayLength: 2000});
        }
    }).catch(err => {
        console.error('Clipboard copy error:', err);
    });
}

// Clear conversation thread and restart
function clearAiChat() {
    chatHistory = [];
    stopThinkingAnimation();
    const container = document.getElementById('aiDynamicMessages');
    if (container) container.innerHTML = '';
    const welcome = document.getElementById('aiWelcomeMessage');
    if (welcome) welcome.style.display = 'flex';
    clearInputField();

    if (typeof M !== 'undefined' && M.toast) {
        M.toast({html: 'Coaching conversation cleared.', displayLength: 1500});
    }
}

// Dynamic Multi-Stage Thinking Animation Controller
let thinkingInterval = null;
const thinkingPhrases = [
    'Analyzing module performance & quarterly transcript...',
    'Evaluating assessment weightings & weakest areas...',
    'Correlating module performance across 4 quarters...',
    'Synthesizing high-impact academic improvement strategies...',
    'Formulating personalized revision timetables & action steps...'
];

function startThinkingAnimation() {
    const typingIndicator = document.getElementById('aiTypingIndicator');
    const label = document.getElementById('aiThinkingStatusText');
    const chatThread = document.getElementById('aiChatThread');
    if (!typingIndicator) return;

    if (label) {
        label.textContent = thinkingPhrases[0];
        label.classList.remove('status-fade-out');
        label.classList.add('status-fade-in');
    }

    typingIndicator.style.display = 'flex';
    if (chatThread) {
        chatThread.scrollTop = chatThread.scrollHeight;
    }

    let phraseIdx = 0;
    clearInterval(thinkingInterval);
    thinkingInterval = setInterval(function() {
        phraseIdx = (phraseIdx + 1) % thinkingPhrases.length;
        if (label) {
            label.classList.add('status-fade-out');
            setTimeout(function() {
                label.textContent = thinkingPhrases[phraseIdx];
                label.classList.remove('status-fade-out');
                label.classList.add('status-fade-in');
            }, 240);
        }
    }, 2400);
}

function stopThinkingAnimation() {
    clearInterval(thinkingInterval);
    thinkingInterval = null;
    const typingIndicator = document.getElementById('aiTypingIndicator');
    if (typingIndicator) {
        typingIndicator.style.display = 'none';
    }
}

// Main AI Coach Runner supporting conversational turns
async function runAiCoach(customQuestion = '') {
    const questionText = customQuestion ? customQuestion.trim() : '';
    const dynamicContainer = document.getElementById('aiDynamicMessages');
    const typingIndicator = document.getElementById('aiTypingIndicator');
    const chatThread = document.getElementById('aiChatThread');
    const btnRun = document.getElementById('runAiAdvisorBtn');
    const btnAsk = document.getElementById('btnAskAi');
    const liveTag = document.getElementById('aiLiveModelLabel');

    if (!dynamicContainer || !typingIndicator) return;

    // Scroll card into view
    const coachCard = document.getElementById('aiCoachCard');
    if (coachCard) {
        coachCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // If custom question is asked, render the user bubble
    if (questionText) {
        const userMsgHtml = `
            <div class="chat-msg-row msg-user">
                <div class="chat-bubble user-bubble">
                    <p class="user-bubble-text">${escapeHtml(questionText)}</p>
                    <div class="user-bubble-meta">${formatCurrentTime()} &bull; ${escapeHtml(currentStudentName)}</div>
                </div>
                <div class="chat-avatar avatar-user">
                    ${escapeHtml(currentStudentInitials)}
                </div>
            </div>
        `;
        dynamicContainer.insertAdjacentHTML('beforeend', userMsgHtml);
        clearInputField();
    }

    // Show Thinking Bubble & Scroll
    startThinkingAnimation();

    if (btnRun) btnRun.classList.add('disabled');
    if (btnAsk) btnAsk.classList.add('disabled');

    // Build Request Payload with History
    const requestBody = Object.assign({}, studentDataPayload, {
        question: questionText,
        history: chatHistory
    });

    try {
        const response = await fetch('../api/ai_coach.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();
        stopThinkingAnimation();

        if (data.success && data.advice) {
            let displayModel = data.model || 'OpenRouter AI';
            if (displayModel.indexOf('/') !== -1) {
                displayModel = displayModel.split('/').pop().replace(':free', '');
            }
            if (liveTag) {
                liveTag.textContent = 'Live AI (' + displayModel + ')';
            }

            // Append to in-memory history
            if (questionText) {
                chatHistory.push({ role: 'user', content: questionText });
            }
            chatHistory.push({ role: 'assistant', content: data.advice });

            // Encode raw advice safely for clipboard
            const encodedRawAdvice = encodeURIComponent(data.advice);

            // Render Coach Response Bubble
            const coachMsgHtml = `
                <div class="chat-msg-row msg-assistant">
                    <div class="chat-avatar avatar-coach">
                        <i class="material-icons tiny" style="font-size: 18px;">auto_awesome</i>
                    </div>
                    <div class="chat-bubble coach-bubble">
                        <div class="coach-bubble-header">
                            <span class="coach-bubble-title">
                                <i class="material-icons tiny" style="color: #4f46e5;">psychology</i>
                                ${questionText ? 'Coach Answer' : 'Quarterly Academic Performance Diagnostic'}
                            </span>
                            <span class="coach-model-tag">Live AI &bull; ${escapeHtml(displayModel)}</span>
                        </div>
                        <div class="coach-bubble-body">
                            ${formatAiMarkdown(data.advice)}
                        </div>
                        <div class="coach-bubble-actions">
                            <button class="btn-copy-advice" onclick="copyAdviceText(this, decodeURIComponent('${encodedRawAdvice}'))">
                                <i class="material-icons tiny">content_copy</i> Copy Advice
                            </button>
                        </div>
                    </div>
                </div>
            `;
            dynamicContainer.insertAdjacentHTML('beforeend', coachMsgHtml);

        } else {
            const errorMsg = data.error || 'AI Academic Coach is currently unavailable. Please check back shortly.';
            const errorHtml = `
                <div class="chat-msg-row msg-assistant">
                    <div class="chat-avatar avatar-coach" style="background: #dc2626;">
                        <i class="material-icons tiny">error_outline</i>
                    </div>
                    <div class="chat-bubble coach-bubble" style="border-left: 4px solid #dc2626;">
                        <div style="color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <i class="material-icons tiny">warning</i> ${escapeHtml(errorMsg)}
                        </div>
                    </div>
                </div>
            `;
            dynamicContainer.insertAdjacentHTML('beforeend', errorHtml);
        }
    } catch (err) {
        console.error('AI Coach fetch error:', err);
        stopThinkingAnimation();
        const errorHtml = `
            <div class="chat-msg-row msg-assistant">
                <div class="chat-avatar avatar-coach" style="background: #dc2626;">
                    <i class="material-icons tiny">error_outline</i>
                </div>
                <div class="chat-bubble coach-bubble" style="border-left: 4px solid #dc2626;">
                    <div style="color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons tiny">warning</i> AI Academic Coach is currently unavailable. Please check your network connection and try again.
                    </div>
                </div>
            </div>
        `;
        dynamicContainer.insertAdjacentHTML('beforeend', errorHtml);
    } finally {
        stopThinkingAnimation();
        if (btnRun) btnRun.classList.remove('disabled');
        if (btnAsk) btnAsk.classList.remove('disabled');
        if (chatThread) chatThread.scrollTop = chatThread.scrollHeight;
    }
}

function askAiQuestion(q) {
    const input = document.getElementById('aiQuestionInput');
    if (input) input.value = q;
    runAiCoach(q);
}


function handleAiFormSubmit(e) {
    e.preventDefault();
    const input = document.getElementById('aiQuestionInput');
    const question = input ? input.value.trim() : '';
    if (!question) return;
    runAiCoach(question);
}
</script>

<?php include '../includes/footer.php'; ?>

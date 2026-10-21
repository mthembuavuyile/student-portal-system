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

// Summary statistics
$totalCredits = 0;
$weightedSum = 0;
$totalAverageSum = 0;
$resultCount = 0;

foreach ($moduleResults as $res) {
    $credits = (int)$res['credits'];
    $totalCredits += $credits;
    if ($res['average'] !== null) {
        $totalAverageSum += (float)$res['average'];
        $weightedSum += ((float)$res['average'] * $credits);
        $resultCount++;
    }
}

$termAverage = $resultCount > 0 ? round($totalAverageSum / $resultCount, 2) : null;
$weightedGpa = ($resultCount > 0 && $totalCredits > 0) ? round($weightedSum / $totalCredits, 2) : null;

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
$studentPayloadJson = json_encode([
    'studentName' => $student->getFullName(),
    'modules' => $clientModuleData
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<style>
    .transcript-card { 
        background: #fff; 
        padding: 30px; 
        border-radius: 8px; 
        margin-top: 20px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        box-sizing: border-box;
        width: 100%;
        max-width: 100%;
    }
    .transcript-header { 
        border-bottom: 3px solid #1565c0; 
        padding-bottom: 15px; 
        margin-bottom: 25px; 
    }
    .department-badge { 
        background: #e3f2fd; 
        color: #0d47a1; 
        padding: 6px 14px; 
        border-radius: 20px; 
        font-weight: 500; 
        font-size: 0.85rem; 
        display: inline-block; 
        max-width: 100%;
        line-height: 1.4;
        word-break: break-word;
    }
    .bio-summary-box {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        box-sizing: border-box;
        border: 1px solid #eef2f6;
    }
    .results-actions-bar {
        margin-top: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .results-btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .results-table-wrapper {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin: 15px 0 25px 0;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .results-table-wrapper::-webkit-scrollbar {
        height: 6px;
    }
    .results-table-wrapper::-webkit-scrollbar-thumb {
        background-color: #90caf9;
        border-radius: 4px;
    }
    .results-table {
        min-width: 820px;
        margin: 0;
        width: 100%;
    }
    .results-table th, .results-table td {
        padding: 12px 14px;
        white-space: nowrap;
    }

    @media only screen and (max-width: 600px) {
        .transcript-card {
            padding: 16px 12px;
            margin-top: 10px;
            border-radius: 6px;
        }
        .transcript-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        .transcript-header h4 {
            font-size: 1.35rem !important;
            margin: 8px 0 !important;
        }
        .transcript-header p {
            font-size: 0.8rem;
        }
        .department-badge {
            font-size: 0.72rem;
            padding: 4px 10px;
            border-radius: 14px;
        }
        .bio-summary-box {
            padding: 12px;
            margin-bottom: 15px;
            font-size: 0.88rem;
        }
        .results-actions-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .results-actions-bar a.btn-flat {
            align-self: flex-start;
            padding: 0 6px;
        }
        .results-btn-group {
            flex-direction: column;
            width: 100%;
            gap: 8px;
        }
        .results-btn-group button,
        .results-btn-group a.btn {
            width: 100% !important;
            justify-content: center;
            display: flex;
            align-items: center;
            font-size: 0.88rem;
            padding: 0 12px;
            box-sizing: border-box;
            margin: 0;
        }
    }

    @media print {
        body * { visibility: hidden; }
        #printableTranscript, #printableTranscript * { visibility: visible; }
        #printableTranscript { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; padding: 0; }
        .no-print { display: none !important; }
        .results-table-wrapper { overflow: visible !important; border: none !important; box-shadow: none !important; }
        .results-table { min-width: 100% !important; }
    }
</style>

<div class="row" style="margin-bottom: 0;">
    <div class="col s12 m10 offset-m1" style="box-sizing: border-box; padding: 0 5px;">
        <div class="no-print results-actions-bar">
            <a href="dashboard.php" class="btn-flat waves-effect"><i class="material-icons left">arrow_back</i>Dashboard</a>
            <div class="results-btn-group">
                <a href="#aiCoachCard" class="btn waves-effect waves-light purple darken-2" style="font-weight: 600;">
                    <i class="material-icons left">auto_awesome</i>AI Academic Coach
                </a>
                <button onclick="window.print();" class="btn waves-effect waves-light blue darken-2"><i class="material-icons left">print</i>Print Transcript</button>
                <button id="downloadPdf" class="btn waves-effect waves-light green darken-2"><i class="material-icons left">download</i>Download PDF</button>
            </div>
        </div>

        <div id="printableTranscript" class="transcript-card">
            <div class="transcript-header center-align">
                <span class="department-badge">Republic of South Africa &bull; Department of Forestry, Fisheries and the Environment</span>
                <h4 style="margin: 10px 0 5px 0; color: #1565c0; font-weight: 600;">Student Academic Transcript</h4>
                <p class="grey-text" style="margin: 0;">ICT Services: Infrastructure & Security Support &bull; Corporate Management Services</p>
            </div>

            <!-- Student Bio Summary -->
            <div class="bio-summary-box">
                <div class="row" style="margin-bottom: 0;">
                    <div class="col s12 m6">
                        <p style="margin: 4px 0;"><strong>Student Name:</strong> <?php echo htmlspecialchars($student->getFullName()); ?></p>
                        <p style="margin: 4px 0;"><strong>Student Number:</strong> <code><?php echo htmlspecialchars($student->getStudentNumber()); ?></code></p>
                        <p style="margin: 4px 0;"><strong>National ID Number:</strong> <?php echo htmlspecialchars($student->getIdNumber() ?: 'On Record'); ?></p>
                    </div>
                    <div class="col s12 m6">
                        <p style="margin: 4px 0;"><strong>Course of Study:</strong> <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?></p>
                        <p style="margin: 4px 0;"><strong>Program Duration:</strong> <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?></p>
                        <p style="margin: 4px 0;"><strong>Enrollment Status:</strong> <span class="chip green white-text tiny"><?php echo htmlspecialchars($student->getAcademicStatus()); ?></span></p>
                    </div>
                </div>
            </div>

            <!-- Academic Results Table Section -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                <h5 style="font-weight: 600; margin: 5px 0;">
                    <i class="material-icons left blue-text text-darken-2">assessment</i>Quarterly Module Performance
                </h5>
                <span class="hide-on-med-and-up blue-text text-darken-2" style="font-size: 0.8rem; display: inline-flex; align-items: center; background: #e3f2fd; padding: 4px 10px; border-radius: 12px; margin: 4px 0;">
                    <i class="material-icons tiny" style="margin-right: 4px;">swap_horiz</i>Swipe table sideways for full marks
                </span>
            </div>

            <?php if (!empty($moduleResults)): ?>
                <div class="results-table-wrapper">
                    <table class="bordered highlight striped results-table" style="font-size: 0.92rem;">
                        <thead class="blue darken-2 white-text">
                            <tr>
                                <th style="min-width: 105px;">Module Code</th>
                                <th style="min-width: 180px;">Module Name</th>
                                <th style="min-width: 70px;">Credits</th>
                                <th style="min-width: 160px;">Lecturers</th>
                                <th style="min-width: 80px;">Q1 (25%)</th>
                                <th style="min-width: 80px;">Q2 (25%)</th>
                                <th style="min-width: 80px;">Q3 (25%)</th>
                                <th style="min-width: 80px;">Q4 (25%)</th>
                                <th style="min-width: 85px;">Average</th>
                                <th style="min-width: 85px;">Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moduleResults as $res): ?>
                                <?php 
                                    $modRes = new ModuleResult($res);
                                    $grade = $modRes->getGradeStatus();
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['module_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($res['module_name']); ?></td>
                                    <td><?php echo htmlspecialchars($res['credits']); ?></td>
                                    <td><small><?php echo htmlspecialchars($res['lecturers'] ?: 'Institutional Faculty'); ?></small></td>
                                    <td><?php echo $res['quarter1'] !== null ? number_format($res['quarter1'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter2'] !== null ? number_format($res['quarter2'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter3'] !== null ? number_format($res['quarter3'], 1) . '%' : '-'; ?></td>
                                    <td><?php echo $res['quarter4'] !== null ? number_format($res['quarter4'], 1) . '%' : '-'; ?></td>
                                    <td>
                                        <strong style="color: #0d47a1; font-size: 1.05rem;">
                                            <?php echo $res['average'] !== null ? number_format($res['average'], 2) . '%' : 'Pending'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="chip tiny <?php echo $grade['color']; ?> white-text">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Totals -->
                <div class="row" style="margin-top: 15px; margin-bottom: 0;">
                    <div class="col s12 m6">
                        <div class="card-panel blue lighten-5 blue-text text-darken-4" style="padding: 15px; margin-bottom: 15px; border-radius: 6px;">
                            <h6 style="margin: 0 0 6px 0; font-size: 1rem;"><strong>Cumulative Institutional Average:</strong> <?php echo $termAverage !== null ? $termAverage . '%' : 'N/A'; ?></h6>
                            <p style="margin: 0; font-size: 0.82rem;">Calculated using institutional quarterly formula: calculateAverage() = (Q1 + Q2 + Q3 + Q4) / 4.</p>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <div class="card-panel green lighten-5 green-text text-darken-4" style="padding: 15px; margin-bottom: 15px; border-radius: 6px;">
                            <h6 style="margin: 0 0 6px 0; font-size: 1rem;"><strong>Total Enrolled Credits:</strong> <?php echo $totalCredits; ?> Credits</h6>
                            <p style="margin: 0; font-size: 0.82rem;">Status: Good Academic Standing &bull; All enrolled modules authenticated.</p>
                        </div>
                    </div>
                </div>

                <!-- Consolidated AI Academic Success Coach Section (Material Design) -->
                <div id="aiCoachCard" class="card-panel z-depth-1 no-print ai-coach-card">
                    <div class="ai-coach-header">
                        <div class="ai-coach-title-area">
                            <div class="ai-coach-avatar">
                                <i class="material-icons white-text">psychology</i>
                            </div>
                            <div class="ai-coach-title-text">
                                <h6 class="ai-coach-title">
                                    AI Academic Success Coach
                                </h6>
                                <span class="ai-coach-subtitle">
                                    Direct diagnostic & module coaching powered by OpenRouter AI relay
                                </span>
                            </div>
                        </div>
                        <div class="ai-coach-btn-area">
                            <button id="runAiAdvisorBtn" onclick="runAiCoach('')" class="btn waves-effect waves-light blue darken-2" style="font-weight: 600; text-transform: none; border-radius: 6px; box-shadow: 0 2px 6px rgba(21, 101, 192, 0.25);">
                                <i class="material-icons left">analytics</i>Run Performance Diagnosis
                            </button>
                        </div>
                    </div>

                    <!-- Quick Prompt Chips (Material Design with Vector Icons) -->
                    <div class="ai-quick-topics-section">
                        <span class="ai-quick-topics-label">
                            Quick Coaching Topics:
                        </span>
                        <div class="ai-quick-chips-wrapper">
                            <a href="#!" class="ai-quick-chip waves-effect" onclick="askAiQuestion('How can I boost my marks in my lowest module?')">
                                <i class="material-icons">trending_up</i> <span>Boost lowest module</span>
                            </a>
                            <a href="#!" class="ai-quick-chip waves-effect" onclick="askAiQuestion('Create a 2-week daily revision schedule for my modules')">
                                <i class="material-icons">event</i> <span>2-Week timetable</span>
                            </a>
                            <a href="#!" class="ai-quick-chip waves-effect" onclick="askAiQuestion('What active recall study techniques work best for technical modules?')">
                                <i class="material-icons">school</i> <span>Best study techniques</span>
                            </a>
                            <a href="#!" class="ai-quick-chip waves-effect" onclick="askAiQuestion('How do I prepare for upcoming final assessments?')">
                                <i class="material-icons">flag</i> <span>Exam prep advice</span>
                            </a>
                        </div>
                    </div>

                    <!-- Interactive Coach Question Input -->
                    <form id="aiCoachForm" class="ai-coach-form" onsubmit="handleAiFormSubmit(event)">
                        <div class="ai-coach-input-container">
                            <input type="text" id="aiQuestionInput" class="browser-default ai-coach-input" placeholder="Ask your coach anything about marks or revision...">
                        </div>
                        <button type="submit" id="btnAskAi" class="btn waves-effect waves-light grey darken-3 btn-ask-ai">
                            <span>Ask</span><i class="material-icons right">send</i>
                        </button>
                    </form>

                    <!-- AI Coach Output Box -->
                    <div id="aiAdvisorOutput" class="card-panel z-depth-1 ai-advisor-output" style="display:none;">
                        <div class="ai-advisor-output-header">
                            <span id="aiAdvisorTitle" class="ai-advisor-title">
                                <i class="material-icons" style="color: #1565c0; font-size: 20px;">psychology</i> Coach Feedback
                            </span>
                            <span id="aiAdvisorModelTag" class="chip tiny" style="background: #e3f2fd; color: #0d47a1; font-weight: 600; margin: 0; height: 24px; line-height: 24px; font-size: 0.75rem;">AI Active</span>
                        </div>
                        <div id="aiContent" class="ai-content-box">
                            <em>Consulting coach...</em>
                        </div>
                    </div>
                </div>

                <style>
                    /* AI Academic Coach Card & Material Output Styling */
                    .ai-coach-card {
                        margin-top: 25px;
                        padding: 22px;
                        border: 1px solid #e0e0e0;
                        border-radius: 8px;
                        background: #fff;
                        box-sizing: border-box;
                    }

                    .ai-coach-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        flex-wrap: wrap;
                        gap: 12px;
                        margin-bottom: 16px;
                    }

                    .ai-coach-title-area {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }

                    .ai-coach-avatar {
                        width: 44px;
                        height: 44px;
                        border-radius: 8px;
                        background: #1565c0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 2px 6px rgba(21, 101, 192, 0.25);
                        flex-shrink: 0;
                    }

                    .ai-coach-title {
                        margin: 0;
                        font-weight: 700;
                        color: #212121;
                        font-size: 1.15rem;
                        line-height: 1.3;
                    }

                    .ai-coach-subtitle {
                        font-size: 0.8rem;
                        color: #757575;
                        display: block;
                        margin-top: 2px;
                    }

                    .ai-quick-topics-section {
                        margin-bottom: 14px;
                    }

                    .ai-quick-topics-label {
                        font-size: 0.72rem;
                        font-weight: 700;
                        color: #757575;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        display: block;
                        margin-bottom: 8px;
                    }

                    .ai-quick-chips-wrapper {
                        display: flex;
                        gap: 8px;
                        flex-wrap: wrap;
                    }

                    .ai-quick-chip {
                        background: #f5f5f5;
                        border: 1px solid #e0e0e0;
                        color: #1565c0;
                        font-size: 0.8rem;
                        font-weight: 500;
                        padding: 6px 14px;
                        border-radius: 16px;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        text-decoration: none;
                        transition: all 0.2s ease;
                        box-sizing: border-box;
                    }
                    .ai-quick-chip i {
                        font-size: 16px;
                        color: #1565c0;
                    }
                    .ai-quick-chip:hover {
                        background: #e3f2fd;
                        border-color: #90caf9;
                        color: #0d47a1;
                        transform: translateY(-1px);
                    }

                    /* Interactive Coach Form */
                    .ai-coach-form {
                        margin: 0;
                        display: flex;
                        gap: 8px;
                        align-items: center;
                        width: 100%;
                        box-sizing: border-box;
                    }

                    .ai-coach-input-container {
                        flex: 1 1 auto;
                        min-width: 0; /* CRITICAL: prevents flex child from collapsing or overflowing */
                        position: relative;
                    }

                    .ai-coach-input {
                        width: 100% !important;
                        height: 44px !important;
                        line-height: 44px !important;
                        border: 1.5px solid #bdbdbd !important;
                        border-radius: 6px !important;
                        padding: 0 14px !important;
                        font-size: 0.9rem !important;
                        margin: 0 !important;
                        box-sizing: border-box !important;
                        background: #fafafa !important;
                        color: #212121 !important;
                        outline: none !important;
                        transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s !important;
                    }

                    .ai-coach-input:focus {
                        border-color: #1565c0 !important;
                        background: #fff !important;
                        box-shadow: 0 0 0 2px rgba(21, 101, 192, 0.15) !important;
                    }

                    .btn-ask-ai {
                        flex: 0 0 auto !important;
                        width: auto !important;
                        margin: 0 !important;
                        margin-top: 0 !important;
                        height: 44px !important;
                        line-height: 44px !important;
                        border-radius: 6px !important;
                        text-transform: none !important;
                        font-weight: 600 !important;
                        padding: 0 18px !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        gap: 6px !important;
                        white-space: nowrap !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
                    }

                    .btn-ask-ai i.material-icons.right {
                        margin-left: 0 !important;
                        font-size: 18px !important;
                        float: none !important;
                    }

                    /* Output Panel */
                    .ai-advisor-output {
                        margin-top: 18px;
                        padding: 18px;
                        background: #fafafa;
                        border: 1px solid #e0e0e0;
                        border-radius: 6px;
                        box-sizing: border-box;
                    }

                    .ai-advisor-output-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        flex-wrap: wrap;
                        gap: 8px;
                        margin-bottom: 12px;
                        border-bottom: 1px solid #e0e0e0;
                        padding-bottom: 8px;
                    }

                    .ai-advisor-title {
                        font-weight: 700;
                        color: #212121;
                        font-size: 0.92rem;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        flex-wrap: wrap;
                        word-break: break-word;
                    }

                    .ai-content-box {
                        line-height: 1.7;
                        font-size: 0.92rem;
                        color: #424242;
                        overflow-x: auto;
                        word-break: break-word;
                    }

                    /* Responsive Mobile Breakpoint (<= 600px) */
                    @media only screen and (max-width: 600px) {
                        .ai-coach-card {
                            margin-top: 16px;
                            padding: 16px 12px;
                        }
                        .ai-coach-header {
                            flex-direction: column;
                            align-items: stretch;
                            gap: 12px;
                        }
                        .ai-coach-title {
                            font-size: 1.05rem;
                        }
                        .ai-coach-subtitle {
                            font-size: 0.76rem;
                        }
                        .ai-coach-btn-area {
                            width: 100%;
                        }
                        .ai-coach-btn-area #runAiAdvisorBtn {
                            width: 100% !important;
                            display: flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                            margin: 0 !important;
                            height: 42px;
                            line-height: 42px;
                            font-size: 0.88rem;
                        }
                        .ai-quick-chips-wrapper {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            gap: 8px;
                        }
                        .ai-quick-chip {
                            font-size: 0.76rem;
                            padding: 8px 6px;
                            border-radius: 8px;
                            justify-content: center;
                            text-align: center;
                            min-height: 40px;
                            width: 100%;
                        }
                        .ai-quick-chip i {
                            font-size: 15px;
                        }
                        .ai-coach-form {
                            display: flex;
                            flex-direction: row;
                            gap: 8px;
                            align-items: center;
                        }
                        .ai-coach-input {
                            font-size: 16px !important; /* Prevents auto zoom on iOS Safari */
                            height: 42px !important;
                            line-height: 42px !important;
                            padding: 0 10px !important;
                        }
                        .btn-ask-ai {
                            height: 42px !important;
                            line-height: 42px !important;
                            padding: 0 14px !important;
                            width: auto !important;
                            margin-top: 0 !important;
                        }
                        .ai-advisor-output {
                            padding: 14px 10px;
                        }
                    }

                    /* Ultra-compact phone screens (<= 380px) */
                    @media only screen and (max-width: 380px) {
                        .ai-quick-chips-wrapper {
                            grid-template-columns: 1fr;
                        }
                        .ai-coach-form {
                            flex-direction: column;
                            align-items: stretch;
                        }
                        .ai-coach-input-container {
                            width: 100%;
                        }
                        .btn-ask-ai {
                            width: 100% !important;
                        }
                    }

                    .ai-rendered-output {
                        line-height: 1.75;
                        font-size: 0.93rem;
                        color: #333333;
                    }
                    .ai-rendered-output p {
                        margin: 0 0 14px 0;
                        line-height: 1.75;
                        color: #333333;
                    }
                    .ai-rendered-output p:last-child {
                        margin-bottom: 0;
                    }
                    .ai-rendered-output h3, .ai-rendered-output h4, .ai-rendered-output h5 {
                        margin: 18px 0 8px 0;
                        font-weight: 700;
                        color: #1565c0;
                        letter-spacing: -0.01em;
                    }
                    .ai-rendered-output h3 { font-size: 1.25rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 6px; }
                    .ai-rendered-output h4 { font-size: 1.1rem; color: #0d47a1; }
                    .ai-rendered-output h5 { font-size: 1rem; color: #1565c0; }
                    .ai-bullet-group {
                        margin: 8px 0 16px 0;
                        padding-left: 0;
                        list-style: none;
                    }
                    .ai-bullet-group > li {
                        position: relative;
                        padding-left: 22px;
                        margin-bottom: 10px;
                        line-height: 1.65;
                        color: #333333;
                    }
                    .ai-bullet-group > li::before {
                        content: "•";
                        position: absolute;
                        left: 6px;
                        top: -1px;
                        color: #1565c0;
                        font-size: 1.3rem;
                        font-weight: bold;
                    }
                    .ai-bullet-group > li strong {
                        color: #111827;
                    }
                    .ai-sub-description {
                        margin-top: 4px;
                        margin-bottom: 8px;
                        padding-left: 14px;
                        border-left: 2px solid #90caf9;
                        color: #616161;
                        font-size: 0.88rem;
                        line-height: 1.55;
                    }
                    .ai-action-card {
                        background: #f5f9ff;
                        border: 1px solid #bbdefb;
                        border-left: 4px solid #1565c0;
                        border-radius: 6px;
                        padding: 12px 16px;
                        margin: 16px 0 8px 0;
                    }
                    .ai-action-header {
                        font-size: 0.82rem;
                        font-weight: 700;
                        color: #0d47a1;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 6px;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    }
                    .ai-action-header i {
                        font-size: 16px;
                        color: #1565c0;
                    }
                    .ai-action-body {
                        font-size: 0.9rem;
                        color: #212121;
                        line-height: 1.65;
                    }
                    .ai-step-arrow {
                        color: #1565c0;
                        font-weight: bold;
                        padding: 0 4px;
                    }
                    .ai-code-inline {
                        background: #e3f2fd;
                        color: #0d47a1;
                        padding: 2px 6px;
                        border-radius: 4px;
                        font-weight: 600;
                        font-size: 0.88em;
                        font-family: monospace;
                    }
                </style>

            <?php else: ?>
                <div class="card-panel grey lighten-4 center-align">
                    <p class="grey-text">No academic marks are currently recorded for your profile. Please check back after quarterly examinations.</p>
                </div>
            <?php endif; ?>

            <div class="divider" style="margin: 25px 0 15px 0;"></div>
            <div class="row center-align grey-text transcript-footer-notice" style="font-size: 0.8rem; margin-bottom: 0; padding: 0 8px;">
                <p style="margin: 4px 0;">This is an official system transcript issued by the Student Portal System &bull; System Design by Mr Avuyile Mthembu</p>
                <p style="margin: 4px 0;">Generated on <?php echo date("F j, Y, g:i A T"); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- jsPDF and html2canvas for PDF Generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('downloadPdf').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const reportElement = document.getElementById('printableTranscript');
    
    html2canvas(reportElement, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save("transcript-<?php echo htmlspecialchars($student->getStudentNumber()); ?>.pdf");
    });
});

// Student marks payload directly from PHP
const studentDataPayload = <?php echo $studentPayloadJson ?: '{}'; ?>;

// Enhanced Markdown & Rich Text Formatter
function formatAiMarkdown(rawText) {
    if (!rawText) return '';

    // 1. Sanitize HTML
    var text = rawText
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    // 2. Headers
    text = text.replace(/^### (.*$)/gim, '<h5>$1</h5>');
    text = text.replace(/^## (.*$)/gim, '<h4>$1</h4>');
    text = text.replace(/^# (.*$)/gim, '<h3>$1</h3>');

    // 3. Highlight Callout Cards for Action Plan, Key Takeaways, Recommendations
    text = text.replace(/(?:^|\n)(Action Plan|Key Takeaways?|Summary|Immediate Next Steps?):\s*(.*?)(?=\n\n|\n[#*-]|$)/gis, function(match, label, content) {
        var cleanContent = content.trim().replace(/→|->/g, '<span class="ai-step-arrow">➔</span>');
        return '\n<div class="ai-action-card"><div class="ai-action-header"><i class="material-icons tiny">flag</i> ' + label + '</div><div class="ai-action-body">' + cleanContent + '</div></div>\n';
    });

    // 4. Bold, Italics, Code
    text = text.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>');
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
    text = text.replace(/`([^`]+)`/g, '<code class="ai-code-inline">$1</code>');
    text = text.replace(/→|->/g, '<span class="ai-step-arrow">➔</span>');

    // 5. Line by line parsing to build clean list structures & descriptions
    var lines = text.split('\n');
    var outputLines = [];
    var inList = false;

    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        var isBullet = line.match(/^(\s*)([-*•])\s+(.*)$/);
        var isNumbered = line.match(/^(\s*)(\d+)\.\s+(.*)$/);

        if (isBullet) {
            if (!inList) {
                outputLines.push('<ul class="ai-bullet-group">');
                inList = true;
            }
            outputLines.push('<li>' + isBullet[3] + '</li>');
        } else if (isNumbered) {
            if (!inList) {
                outputLines.push('<ul class="ai-bullet-group">');
                inList = true;
            }
            outputLines.push('<li><strong>' + isNumbered[2] + '.</strong> ' + isNumbered[3] + '</li>');
        } else {
            var trimmed = line.trim();
            if (inList && trimmed === '') {
                outputLines.push('</ul>');
                inList = false;
            } else if (inList && !trimmed.match(/^<(div|h|p)/)) {
                // Indented descriptive continuation under the bullet point
                outputLines.push('<div class="ai-sub-description">' + trimmed + '</div>');
            } else {
                if (inList) {
                    outputLines.push('</ul>');
                    inList = false;
                }
                outputLines.push(line);
            }
        }
    }
    if (inList) {
        outputLines.push('</ul>');
    }

    // 6. Wrap loose blocks into proper paragraphs
    var paragraphs = outputLines.join('\n').split(/\n\s*\n/);
    var finalResult = paragraphs.map(function(p) {
        var trimmed = p.trim();
        if (!trimmed) return '';
        if (trimmed.match(/^<(h[1-6]|ul|ol|div|table|blockquote)/i)) {
            return trimmed;
        }
        return '<p>' + trimmed.replace(/\n/g, '<br>') + '</p>';
    }).join('\n');

    return '<div class="ai-rendered-output">' + finalResult + '</div>';
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Unified AI Coach Runner
async function runAiCoach(customQuestion = '') {
    const outputDiv = document.getElementById('aiAdvisorOutput');
    const contentDiv = document.getElementById('aiContent');
    const titleSpan = document.getElementById('aiAdvisorTitle');
    const modelTag = document.getElementById('aiAdvisorModelTag');
    const btn = document.getElementById('runAiAdvisorBtn');
    const askBtn = document.getElementById('btnAskAi');

    if (!outputDiv || !contentDiv) return;

    outputDiv.style.display = 'block';
    outputDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    if (customQuestion) {
        titleSpan.innerHTML = '<span style="display:inline-flex; align-items:center; gap:6px;"><i class="material-icons tiny" style="color:#1565c0;">chat</i> <strong>Topic:</strong> &ldquo;' + escapeHtml(customQuestion) + '&rdquo;</span>';
    } else {
        titleSpan.innerHTML = '<span style="display:inline-flex; align-items:center; gap:6px;"><i class="material-icons tiny" style="color:#1565c0;">assessment</i> <strong>Quarterly Academic Performance Diagnostic</strong></span>';
    }

    contentDiv.innerHTML = '<div style="display:flex; align-items:center; gap:10px; color:#1565c0; padding: 14px 0;"><div class="preloader-wrapper small active" style="width:20px; height:20px;"><div class="spinner-layer spinner-blue-only"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"><div class="circle"></div></div><div class="circle-clipper right"><div class="circle"></div></div></div></div><span>Consulting AI academic coach...</span></div>';

    if (btn) btn.classList.add('disabled');
    if (askBtn) askBtn.classList.add('disabled');

    const requestBody = Object.assign({}, studentDataPayload, {
        question: customQuestion
    });

    try {
        const response = await fetch('../api/ai_coach.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(requestBody)
        });

        const data = await response.json();
        if (data.success && data.advice) {
            contentDiv.innerHTML = formatAiMarkdown(data.advice);
            if (modelTag) {
                let displayModel = data.model || 'OpenRouter AI';
                if (displayModel.indexOf('/') !== -1) {
                    displayModel = displayModel.split('/').pop().replace(':free', '');
                }
                modelTag.textContent = 'Live AI (' + displayModel + ')';
                modelTag.style.background = '#e8f5e9';
                modelTag.style.color = '#2e7d32';
            }
        } else {
            const errorMsg = data.error || 'AI Academic Success Coach is currently unavailable. Please try again later.';
            contentDiv.innerHTML = '<div style="padding: 14px 16px; background: #ffebee; border: 1px solid #ffcdd2; border-radius: 6px; color: #c62828; font-weight: 500; display: flex; align-items: center; gap: 8px;"><i class="material-icons" style="font-size: 20px;">error_outline</i> ' + escapeHtml(errorMsg) + '</div>';
            if (modelTag) {
                modelTag.textContent = 'Unavailable';
                modelTag.style.background = '#ffebee';
                modelTag.style.color = '#c62828';
            }
        }
    } catch (err) {
        contentDiv.innerHTML = '<div style="padding: 14px 16px; background: #ffebee; border: 1px solid #ffcdd2; border-radius: 6px; color: #c62828; font-weight: 500; display: flex; align-items: center; gap: 8px;"><i class="material-icons" style="font-size: 20px;">error_outline</i> AI Academic Success Coach is currently unavailable. Please try again later.</div>';
        if (modelTag) {
            modelTag.textContent = 'Unavailable';
            modelTag.style.background = '#ffebee';
            modelTag.style.color = '#c62828';
        }
    } finally {
        if (btn) btn.classList.remove('disabled');
        if (askBtn) askBtn.classList.remove('disabled');
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

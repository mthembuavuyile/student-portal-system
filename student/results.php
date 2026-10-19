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
        .results-btn-group button {
            width: 100%;
            justify-content: center;
            display: flex;
            align-items: center;
            font-size: 0.88rem;
            padding: 0 12px;
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
                <a href="#aiAdvisorModal" class="btn waves-effect waves-light amber darken-3 modal-trigger" style="font-weight: 600;">
                    <i class="material-icons left">auto_awesome</i>AI Performance Insights
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

            <?php else: ?>
                <div class="card-panel grey lighten-4 center-align">
                    <p class="grey-text">No academic marks are currently recorded for your profile. Please check back after quarterly examinations.</p>
                </div>
            <?php endif; ?>

            <div class="divider" style="margin: 25px 0 15px 0;"></div>
            <div class="row center-align grey-text" style="font-size: 0.8rem; margin-bottom: 0;">
                <p style="margin: 4px 0;">This is an official system transcript issued by the Student Portal System &bull; System Design by Mr Avuyile Mthembu</p>
                <p style="margin: 4px 0;">Generated on <?php echo date("F j, Y, g:i A"); ?></p>
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
</script>

<?php 
// Include AI Success Advisor Modal
include __DIR__ . '/../includes/ai_advisor_modal.php'; 
?>

<?php include '../includes/footer.php'; ?>

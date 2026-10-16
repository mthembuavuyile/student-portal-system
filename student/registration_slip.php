<?php
$page_title = "Registration Confirmation Slip";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch student data
if (!isset($_SESSION['student_id_number'])) {
    redirect('../index.php');
}

$student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
if (!$student) {
    die("No registration data found.");
}

$activeEnrollment = $student->getActiveEnrollment($pdo);
?>

<style>
    .report-container { background: #fff; padding: 30px; border: 1px solid #ddd; margin-top: 20px; border-radius: 6px; }
    .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #00695c; padding-bottom: 12px; }
    .report-header h4 { margin: 0; color: #00695c; font-weight: 600; }
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table td { padding: 12px 10px; border-bottom: 1px solid #eee; }
    .report-table td:first-child { font-weight: bold; width: 32%; }
    .report-footer { text-align: center; margin-top: 35px; font-size: 0.8em; color: #777; }
    .actions-bar { padding: 15px 0; text-align: right; }

    @media print {
        body * { visibility: hidden; }
        .report-container, .report-container * { visibility: visible; }
        .report-container { position: absolute; left: 0; top: 0; width: 100%; border: none; }
        .actions-bar { display: none; }
    }
</style>

<div class="row">
    <div class="col s12 m10 offset-m1">
        <div class="actions-bar">
            <a href="dashboard.php" class="btn-flat waves-effect"><i class="material-icons left">arrow_back</i>Back to Dashboard</a>
            <button onclick="window.print();" class="btn waves-effect waves-light blue darken-2"><i class="material-icons left">print</i>Print</button>
            <button id="downloadPdf" class="btn waves-effect waves-light green darken-2"><i class="material-icons left">download</i>Download as PDF</button>
        </div>
        <div id="reportContent" class="report-container z-depth-1">
            <div class="report-header">
                <h4>Registration Confirmation Slip</h4>
                <p style="margin: 5px 0 0 0; color: #555;">Department of Forestry, Fisheries and the Environment &bull; Student Portal System</p>
            </div>
            
            <table class="report-table">
                <tbody>
                    <tr>
                        <td>Student Name:</td>
                        <td><?php echo htmlspecialchars($student->getFullName()); ?></td>
                    </tr>
                    <tr>
                        <td>Student Number:</td>
                        <td><code><?php echo htmlspecialchars($student->getStudentNumber()); ?></code></td>
                    </tr>
                    <tr>
                        <td>National ID Number:</td>
                        <td><?php echo htmlspecialchars($student->getIdNumber() ?: 'Recorded'); ?></td>
                    </tr>
                    <tr>
                        <td>Registration Timestamp:</td>
                        <td><?php echo date("F j, Y, g:i:s a", strtotime($student->getRegistrationTimestamp())); ?></td>
                    </tr>
                    <tr>
                        <td>Enrolled Course:</td>
                        <td><?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?> (<?php echo htmlspecialchars($activeEnrollment['course_code'] ?? 'Active'); ?>)</td>
                    </tr>
                    <tr>
                        <td>Program Duration:</td>
                        <td><?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?></td>
                    </tr>
                    <tr>
                        <td>Enrollment Status:</td>
                        <td><strong class="teal-text text-darken-3"><?php echo htmlspecialchars($student->getAcademicStatus()); ?></strong> (Single Active Course Enforced)</td>
                    </tr>
                </tbody>
            </table>

            <div class="report-footer">
                <p>This document confirms official registration and active course enrollment in the Student Portal System.</p>
                <p>System Architecture & Design: Mr Avuyile Mthembu &bull; ICT Services: Infrastructure & Security Support</p>
                <p>Generated on: <?php echo date("F j, Y, g:i a"); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- jsPDF library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('downloadPdf').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const reportElement = document.getElementById('reportContent');
    
    html2canvas(reportElement).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save("registration-slip-<?php echo htmlspecialchars($student->getStudentNumber()); ?>.pdf");
    });
});
</script>

<?php include '../includes/footer.php'; ?>
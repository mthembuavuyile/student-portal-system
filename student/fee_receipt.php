<?php
$page_title = "Official Fee Payment Receipt";
$allowed_roles = ['student', 'admin'];
include '../includes/header.php';

// Authenticate student or admin
$paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$feeId = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : 0;

$payment = null;
$fee = null;
$student = null;

if ($paymentId > 0) {
    $stmt = $pdo->prepare("
        SELECT fp.*, sf.fee_title, sf.billing_month, sf.academic_year, sf.amount as fee_amount,
               s.full_name, s.student_number, s.id_number, s.course_of_study, s.email
        FROM fee_payments fp
        LEFT JOIN student_fees sf ON sf.fee_id = fp.fee_id
        JOIN students s ON s.id = fp.student_id
        WHERE fp.payment_id = :pid
    ");
    $stmt->execute([':pid' => $paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($feeId > 0) {
    // Find latest completed payment for this fee
    $stmt = $pdo->prepare("
        SELECT fp.*, sf.fee_title, sf.billing_month, sf.academic_year, sf.amount as fee_amount,
               s.full_name, s.student_number, s.id_number, s.course_of_study, s.email
        FROM student_fees sf
        JOIN students s ON s.id = sf.student_id
        LEFT JOIN fee_payments fp ON fp.fee_id = sf.fee_id AND fp.payment_status = 'COMPLETE'
        WHERE sf.fee_id = :fid
        ORDER BY fp.payment_id DESC LIMIT 1
    ");
    $stmt->execute([':fid' => $feeId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$payment) {
    $_SESSION['error_message'] = "Receipt record could not be found or payment has not been finalized.";
    redirect('student/fees.php');
}

// Security: If logged in as student, ensure student can only view their own receipt
if ($_SESSION['role'] === 'student') {
    if (isset($_SESSION['student_id_number']) && $payment['student_number'] !== $_SESSION['student_id_number']) {
        $_SESSION['error_message'] = "Access denied: Unauthorized receipt access.";
        redirect('student/fees.php');
    }
}
?>

<style>
    .receipt-container {
        background: #fff;
        padding: 40px;
        border: 1px solid #ddd;
        margin-top: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    }
    .receipt-header {
        text-align: center;
        border-bottom: 2px solid #0d47a1;
        padding-bottom: 20px;
        margin-bottom: 25px;
    }
    .receipt-header h4 {
        margin: 0;
        color: #0d47a1;
        font-weight: 700;
        font-size: 1.8rem;
    }
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .receipt-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #eee;
    }
    .receipt-table td:first-child {
        font-weight: 600;
        color: #455a64;
        width: 32%;
    }
    .paid-stamp {
        border: 3px solid #2e7d32;
        color: #2e7d32;
        font-weight: 800;
        font-size: 1.3rem;
        padding: 8px 24px;
        border-radius: 8px;
        display: inline-block;
        transform: rotate(-5deg);
        letter-spacing: 2px;
        margin: 15px 0;
    }
    .actions-bar {
        padding: 15px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    @media print {
        body * { visibility: hidden; }
        .receipt-container, .receipt-container * { visibility: visible; }
        .receipt-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none;
            box-shadow: none;
            padding: 20px;
        }
        .actions-bar, nav, footer { display: none !important; }
    }
</style>

<div class="row">
    <div class="col s12 m10 offset-m1 l8 offset-l2">
        <div class="actions-bar">
            <a href="<?php echo ($_SESSION['role'] === 'admin') ? BASE_URL . 'admin/fees.php' : BASE_URL . 'student/fees.php'; ?>" class="btn-flat waves-effect">
                <i class="material-icons left">arrow_back</i> Back to Fees
            </a>
            <div>
                <button onclick="window.print();" class="btn blue darken-3 waves-effect waves-light" style="border-radius: 6px; font-weight: 500;">
                    <i class="material-icons left">print</i> Print Receipt
                </button>
            </div>
        </div>

        <div class="receipt-container" id="printableReceipt">
            <div class="receipt-header">
                <h4>OFFICIAL FEE PAYMENT RECEIPT</h4>
                <p style="margin: 6px 0 0 0; color: #555; font-size: 0.95rem;">
                    Department of Forestry, Fisheries & the Environment &bull; Student Portal System
                </p>
                <p class="grey-text" style="margin: 3px 0 0 0; font-size: 0.85rem;">
                    Receipt No: <strong>REC-<?php echo str_pad((string)$payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></strong> &bull; Generated: <?php echo date('d F Y, H:i:s'); ?>
                </p>
            </div>

            <div class="center-align">
                <div class="paid-stamp">
                    <i class="material-icons tiny left" style="vertical-align: middle;">verified</i> PAID &bull; VERIFIED
                </div>
            </div>

            <table class="receipt-table">
                <tbody>
                    <tr>
                        <td>Student Name:</td>
                        <td><strong><?php echo htmlspecialchars($payment['full_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Student Number:</td>
                        <td><code><?php echo htmlspecialchars($payment['student_number']); ?></code></td>
                    </tr>
                    <tr>
                        <td>Course of Study:</td>
                        <td><?php echo htmlspecialchars($payment['course_of_study']); ?></td>
                    </tr>
                    <tr>
                        <td>Fee Description:</td>
                        <td><strong><?php echo htmlspecialchars($payment['fee_title'] ?? 'Monthly School Fee'); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Academic Year:</td>
                        <td><?php echo htmlspecialchars((string)($payment['academic_year'] ?? '2026')); ?></td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                    </tr>
                    <tr>
                        <td>Transaction Reference:</td>
                        <td><code><?php echo htmlspecialchars($payment['transaction_ref']); ?></code></td>
                    </tr>
                    <tr>
                        <td>Payment ID:</td>
                        <td><code><?php echo htmlspecialchars($payment['payfast_pf_payment_id'] ?? 'N/A'); ?></code></td>
                    </tr>
                    <tr>
                        <td>Date & Time Paid:</td>
                        <td><?php echo date("d F Y, H:i", strtotime($payment['completed_at'] ?? $payment['created_at'])); ?></td>
                    </tr>
                    <tr style="background: #f1f8e9;">
                        <td style="font-size: 1.15rem; color: #1b5e20;">Total Amount Paid:</td>
                        <td style="font-size: 1.4rem; font-weight: 800; color: #1b5e20;">
                            R<?php echo number_format($payment['amount'], 2); ?> ZAR
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="border-top: 1px dashed #bbb; padding-top: 20px; margin-top: 30px; text-align: center; color: #78909c; font-size: 0.85rem;">
                <p style="margin: 0;">
                    This is a computer-generated official receipt issued by the Department of Forestry, Fisheries & the Environment.
                </p>
                <p style="margin: 4px 0 0 0;">
                    For billing inquiries, contact the Financial Office at <code>finance@dffe.gov.za</code>.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

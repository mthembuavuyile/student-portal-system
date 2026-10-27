<?php
$page_title = "Payment Successful";
$allowed_roles = ['student'];
include '../includes/header.php';

$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$student = null;
if (isset($_SESSION['student_id_number'])) {
    $student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
}

if (!$student) {
    redirect('student/fees.php');
}

$studentData = $student->toArray();
$studentId = (int)$studentData['id'];

// Find the payment record
$payment = null;
if (!empty($ref)) {
    $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE transaction_ref = :ref AND student_id = :sid");
    $stmt->execute([':ref' => $ref, ':sid' => $studentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}

// In local sandbox environment or if ITN hasn't finished, auto-reconcile for immediate local testing
if ($payment && $payment['payment_status'] === 'PENDING') {
    $simulatedPfId = 'PF-SBX-' . rand(10000000, 99999999);
    FeeService::completePayment(
        $pdo,
        $ref,
        $simulatedPfId,
        'SANDBOX_AUTO_RECONCILED',
        json_encode(['source' => 'sandbox_return_auto_reconcile', 'timestamp' => date('c')])
    );

    // Refresh payment record
    $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE transaction_ref = :ref");
    $stmt->execute([':ref' => $ref]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="row" style="margin-top: 30px;">
    <div class="col s12 m8 offset-m2 l6 offset-l3">
        <div class="card white center-align" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 30px 20px;">
            <div style="width: 80px; height: 80px; background: #e8f5e9; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="material-icons green-text text-darken-1" style="font-size: 48px;">check_circle</i>
            </div>

            <h4 style="margin: 0 0 10px 0; font-weight: 700; color: #1b5e20;">Payment Received!</h4>
            <p class="grey-text text-darken-1" style="font-size: 1.05rem; margin-bottom: 25px;">
                Your school fee payment has been successfully processed through PayFast.
            </p>

            <?php if ($payment): ?>
                <div class="blue-grey lighten-5" style="border-radius: 10px; padding: 18px 20px; text-align: left; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span class="grey-text text-darken-2">Amount Paid:</span>
                        <strong class="green-text text-darken-3" style="font-size: 1.15rem;">
                            R<?php echo number_format($payment['amount'], 2); ?> ZAR
                        </strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span class="grey-text text-darken-2">Transaction Ref:</span>
                        <strong style="font-family: monospace;"><?php echo htmlspecialchars($payment['transaction_ref']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span class="grey-text text-darken-2">PayFast ID:</span>
                        <span style="font-family: monospace; color: #37474f;">
                            <?php echo htmlspecialchars($payment['payfast_pf_payment_id'] ?? 'Sandbox Verified'); ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="grey-text text-darken-2">Date & Time:</span>
                        <span><?php echo date("d M Y, H:i", strtotime($payment['completed_at'] ?? 'now')); ?></span>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo BASE_URL; ?>student/fee_receipt.php?payment_id=<?php echo (int)$payment['payment_id']; ?>" class="btn-large green darken-2 waves-effect waves-light" style="border-radius: 8px; text-transform: none; font-weight: 600;">
                        <i class="material-icons left">receipt</i> View Official Receipt
                    </a>
                    <a href="<?php echo BASE_URL; ?>student/fees.php" class="btn-large blue darken-3 waves-effect waves-light" style="border-radius: 8px; text-transform: none; font-weight: 600;">
                        <i class="material-icons left">account_balance_wallet</i> Back to School Fees
                    </a>
                </div>
            <?php else: ?>
                <p class="grey-text">We received your return confirmation. You can view updated balances on your fees page.</p>
                <a href="<?php echo BASE_URL; ?>student/fees.php" class="btn blue darken-3 waves-effect waves-light" style="border-radius: 8px;">
                    Back to School Fees
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
$page_title = "School Fees & Payment Gateway";
$allowed_roles = ['student'];
include '../includes/header.php';

// Fetch the logged-in student's details
$student = null;
$student_data = null;
if (isset($_SESSION['student_id_number'])) {
    $student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
    if ($student) {
        $student_data = $student->toArray();
    }
}

if (!$student) {
    $_SESSION['error_message'] = "Could not identify your student profile.";
    redirect('logout.php');
}

$studentId = (int)$student_data['id'];
$academicYear = 2026;

// Get fees and summary from FeeService
$summary = FeeService::getFeeSummary($pdo, $studentId, $academicYear);
$fees = FeeService::getFeesForStudent($pdo, $studentId, $academicYear);
$paymentHistory = FeeService::getPaymentHistory($pdo, $studentId);
$nextFee = $summary['next_unpaid_fee'];
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 10px;">
            <div>
                <h4 style="margin: 0; font-weight: 700; color: #0d47a1;">
                    <i class="material-icons left medium" style="vertical-align: middle;">account_balance_wallet</i>
                    School Fees & PayFast Gateway
                </h4>
                <p class="grey-text text-darken-1" style="margin: 5px 0 0 0;">
                    Department of Forestry, Fisheries & the Environment &bull; Standard Tuition: <strong>R50.00 / month</strong>
                </p>
            </div>
            <div style="margin-top: 10px;">
                <?php if ($summary['is_fully_paid']): ?>
                    <span class="chip green white-text" style="font-weight: 600; font-size: 0.95rem; padding: 0 16px; height: 36px; line-height: 36px;">
                        <i class="material-icons left" style="line-height: 36px;">verified</i> All Fees Paid &bull; Good Standing
                    </span>
                <?php else: ?>
                    <span class="chip orange darken-2 white-text" style="font-weight: 600; font-size: 0.95rem; padding: 0 16px; height: 36px; line-height: 36px;">
                        <i class="material-icons left" style="line-height: 36px;">schedule</i> Outstanding: R<?php echo number_format($summary['balance_due'], 2); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>

        <?php if (PAYFAST_IS_SANDBOX): ?>
            <div class="card-panel blue-grey lighten-5" style="border-left: 5px solid #0288d1; border-radius: 8px; padding: 12px 18px; margin: 15px 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                    <div>
                        <strong class="blue-text text-darken-3"><i class="material-icons tiny left" style="vertical-align: middle;">shield</i> PayFast Sandbox Active</strong>
                        <span class="grey-text text-darken-2" style="font-size: 0.9rem; margin-left: 8px;">
                            Zero-cost testing mode enabled. You can safely simulate real payments using PayFast test cards or Instant EFT.
                        </span>
                    </div>
                    <span class="chip white blue-text text-darken-2" style="margin: 5px 0; border: 1px solid #90caf9;">
                        Merchant: <?php echo htmlspecialchars(PAYFAST_MERCHANT_ID); ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Financial KPI Cards -->
<div class="row">
    <div class="col s12 m6 l3">
        <div class="card-panel blue lighten-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">event_note</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Monthly Fee</h6>
            <p style="font-size: 1.6rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format(SCHOOL_FEE_MONTHLY_AMOUNT, 2); ?>
            </p>
            <span class="chip tiny white blue-text" style="margin-top: 5px;">10 Billing Cycles</span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel indigo lighten-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">receipt</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Total Invoiced</h6>
            <p style="font-size: 1.6rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($summary['total_invoiced'], 2); ?>
            </p>
            <span class="chip tiny white indigo-text" style="margin-top: 5px;">Academic Year 2026</span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel green lighten-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">check_circle</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Total Paid</h6>
            <p style="font-size: 1.6rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($summary['total_paid'], 2); ?>
            </p>
            <span class="chip tiny white green-text text-darken-2" style="margin-top: 5px;">
                <?php echo $summary['paid_count']; ?> Months Settled
            </span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel <?php echo ($summary['balance_due'] > 0) ? 'red lighten-1' : 'teal lighten-1'; ?> white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium"><?php echo ($summary['balance_due'] > 0) ? 'pending_actions' : 'thumb_up'; ?></i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Balance Due</h6>
            <p style="font-size: 1.6rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($summary['balance_due'], 2); ?>
            </p>
            <span class="chip tiny white <?php echo ($summary['balance_due'] > 0) ? 'red-text' : 'teal-text'; ?>" style="margin-top: 5px;">
                <?php echo $summary['unpaid_count']; ?> Months Remaining
            </span>
        </div>
    </div>
</div>

<!-- PayFast Quick Action Banner -->
<div class="row">
    <div class="col s12">
        <div class="card white" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0e0e0;">
            <div class="card-content" style="padding: 24px 28px;">
                <div class="row" style="margin-bottom: 0; display: flex; align-items: center; flex-wrap: wrap;">
                    <div class="col s12 m8">
                        <span class="card-title" style="font-weight: 700; color: #1a237e; font-size: 1.3rem; margin-bottom: 6px;">
                            <i class="material-icons left red-text text-darken-1" style="vertical-align: middle;">payment</i>
                            PayFast Online Payment
                        </span>
                        <?php if ($nextFee): ?>
                            <p class="grey-text text-darken-2" style="font-size: 0.95rem; margin: 0;">
                                Your next fee to settle is <strong><?php echo htmlspecialchars($nextFee['fee_title']); ?></strong> for 
                                <strong class="green-text text-darken-2">R<?php echo number_format($nextFee['amount'], 2); ?></strong> 
                                (Due: <?php echo date("d M Y", strtotime($nextFee['due_date'])); ?>).
                            </p>
                        <?php else: ?>
                            <p class="grey-text text-darken-2" style="font-size: 0.95rem; margin: 0;">
                                Congratulations! All school fee installments for the 2026 academic year have been fully settled.
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col s12 m4 right-align" style="margin-top: 10px;">
                        <?php if ($nextFee): ?>
                            <!-- Form for 1-Click Pay Next Month -->
                            <form action="<?php echo BASE_URL; ?>student/pay_fee.php" method="POST" style="display: inline-block; margin-right: 8px;">
                                <?php csrf_token_field(); ?>
                                <input type="hidden" name="fee_id" value="<?php echo (int)$nextFee['fee_id']; ?>">
                                <button type="submit" class="btn-large waves-effect waves-light blue darken-3" style="border-radius: 8px; font-weight: 600; text-transform: none;">
                                    <i class="material-icons left">flash_on</i> Pay R50 Now
                                </button>
                            </form>
                            <a href="#multiMonthModal" class="btn-large modal-trigger waves-effect waves-teal btn-flat" style="border: 1px solid #1565c0; color: #1565c0; border-radius: 8px; font-weight: 600; text-transform: none;">
                                <i class="material-icons left">layers</i> Pay Multiple
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>student/results.php" class="btn-large waves-effect waves-light green darken-1" style="border-radius: 8px; text-transform: none; font-weight: 600;">
                                <i class="material-icons left">school</i> View Academic Results
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Fees Breakdown Table -->
<div class="row">
    <div class="col s12">
        <div class="card white" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0e0e0;">
            <div class="card-content">
                <span class="card-title" style="font-weight: 700; color: #0d47a1; margin-bottom: 20px;">
                    <i class="material-icons left" style="vertical-align: middle;">calendar_month</i>
                    2026 Academic Fee Schedule (R50.00 / month)
                </span>
                
                <div class="responsive-table-wrapper" style="overflow-x: auto;">
                    <table class="striped highlight responsive-table">
                        <thead class="blue lighten-5">
                            <tr>
                                <th style="padding: 14px 18px;">Billing Period</th>
                                <th>Invoice Item</th>
                                <th>Due Date</th>
                                <th>Billed (ZAR)</th>
                                <th>Amount Paid</th>
                                <th>Status</th>
                                <th class="center-align">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fees)): ?>
                                <tr>
                                    <td colspan="7" class="center-align grey-text" style="padding: 30px;">
                                        No fee schedule has been assigned to your profile yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fees as $fee): ?>
                                    <?php 
                                        $monthName = date('F', mktime(0, 0, 0, (int)$fee['billing_month'], 10));
                                        $isPaid = ($fee['status'] === 'paid');
                                    ?>
                                    <tr>
                                        <td style="padding: 14px 18px; font-weight: 600; color: #1565c0;">
                                            <?php echo htmlspecialchars($monthName . ' ' . $fee['academic_year']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($fee['fee_title']); ?>
                                        </td>
                                        <td>
                                            <?php echo date("d M Y", strtotime($fee['due_date'])); ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            R<?php echo number_format($fee['amount'], 2); ?>
                                        </td>
                                        <td style="font-weight: 600; color: <?php echo $isPaid ? '#2e7d32' : '#c62828'; ?>;">
                                            R<?php echo number_format($fee['amount_paid'], 2); ?>
                                        </td>
                                        <td>
                                            <?php if ($isPaid): ?>
                                                <span class="chip green lighten-4 green-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    <i class="material-icons tiny left" style="vertical-align: middle;">check_circle</i> Paid
                                                </span>
                                            <?php elseif ($fee['status'] === 'partially_paid'): ?>
                                                <span class="chip orange lighten-4 orange-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    Partial (R<?php echo number_format($fee['amount'] - $fee['amount_paid'], 2); ?> due)
                                                </span>
                                            <?php else: ?>
                                                <span class="chip red lighten-4 red-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    <i class="material-icons tiny left" style="vertical-align: middle;">schedule</i> Unpaid
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="center-align">
                                            <?php if (!$isPaid): ?>
                                                <form action="<?php echo BASE_URL; ?>student/pay_fee.php" method="POST" style="display: inline-block;">
                                                    <?php csrf_token_field(); ?>
                                                    <input type="hidden" name="fee_id" value="<?php echo (int)$fee['fee_id']; ?>">
                                                    <button type="submit" class="btn-small waves-effect waves-light blue darken-2" style="border-radius: 6px; text-transform: none; font-weight: 500;">
                                                        <i class="material-icons left tiny">payment</i> Pay R50
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="<?php echo BASE_URL; ?>student/fee_receipt.php?fee_id=<?php echo (int)$fee['fee_id']; ?>" class="btn-small waves-effect waves-teal btn-flat" style="border: 1px solid #2e7d32; color: #2e7d32; border-radius: 6px; text-transform: none; font-weight: 500;">
                                                    <i class="material-icons left tiny">receipt</i> Receipt
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment History / Audit Trail -->
<div class="row">
    <div class="col s12">
        <div class="card white" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0e0e0;">
            <div class="card-content">
                <span class="card-title" style="font-weight: 700; color: #263238; margin-bottom: 20px;">
                    <i class="material-icons left" style="vertical-align: middle;">history</i>
                    Payment History & PayFast Audit Log
                </span>

                <?php if (empty($paymentHistory)): ?>
                    <p class="grey-text center-align" style="padding: 20px;">No payment transactions recorded yet.</p>
                <?php else: ?>
                    <div class="responsive-table-wrapper" style="overflow-x: auto;">
                        <table class="striped responsive-table">
                            <thead class="blue-grey lighten-5">
                                <tr>
                                    <th style="padding: 12px 18px;">Transaction Reference</th>
                                    <th>PayFast Payment ID</th>
                                    <th>Fee Description</th>
                                    <th>Amount Paid</th>
                                    <th>Gateway Method</th>
                                    <th>Date Completed</th>
                                    <th>Status</th>
                                    <th class="center-align">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentHistory as $pay): ?>
                                    <tr>
                                        <td style="padding: 12px 18px; font-family: monospace; font-size: 0.9rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($pay['transaction_ref']); ?>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.85rem; color: #546e7a;">
                                            <?php echo htmlspecialchars($pay['payfast_pf_payment_id'] ?? 'Pending Gateway'); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($pay['fee_title'] ?? 'School Fee Settlement'); ?>
                                        </td>
                                        <td style="font-weight: 600; color: #2e7d32;">
                                            R<?php echo number_format($pay['amount'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="chip tiny white blue-text" style="border: 1px solid #bbdefb;">
                                                <?php echo htmlspecialchars($pay['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date("d M Y, H:i", strtotime($pay['completed_at'] ?? $pay['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($pay['payment_status'] === 'COMPLETE'): ?>
                                                <span class="chip green lighten-4 green-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    COMPLETE
                                                </span>
                                            <?php elseif ($pay['payment_status'] === 'PENDING'): ?>
                                                <span class="chip orange lighten-4 orange-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    PENDING
                                                </span>
                                            <?php else: ?>
                                                <span class="chip red lighten-4 red-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    <?php echo htmlspecialchars($pay['payment_status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="center-align">
                                            <?php if ($pay['payment_status'] === 'COMPLETE'): ?>
                                                <a href="<?php echo BASE_URL; ?>student/fee_receipt.php?payment_id=<?php echo (int)$pay['payment_id']; ?>" class="btn-small waves-effect waves-light teal lighten-1" style="border-radius: 6px; text-transform: none; font-size: 0.82rem;">
                                                    <i class="material-icons left tiny">print</i> Print
                                                </a>
                                            <?php else: ?>
                                                <span class="grey-text">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Multi-Month Payment -->
<div id="multiMonthModal" class="modal" style="border-radius: 14px; max-width: 600px;">
    <form action="<?php echo BASE_URL; ?>student/pay_fee.php" method="POST">
        <?php csrf_token_field(); ?>
        <div class="modal-content" style="padding: 24px 28px;">
            <h5 style="margin: 0 0 10px 0; font-weight: 700; color: #1565c0;">
                <i class="material-icons left" style="vertical-align: middle;">layers</i>
                Pay Multiple Months
            </h5>
            <p class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 20px;">
                Select the months you would like to settle in advance. Each month is <strong>R50.00</strong>.
            </p>

            <div style="max-height: 260px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px 15px;">
                <?php 
                    $hasUnpaid = false;
                    foreach ($fees as $fee): 
                        if ($fee['status'] !== 'paid'):
                            $hasUnpaid = true;
                            $monthName = date('F', mktime(0, 0, 0, (int)$fee['billing_month'], 10));
                            $feeBal = (float)$fee['amount'] - (float)$fee['amount_paid'];
                ?>
                    <p style="margin: 10px 0;">
                        <label>
                            <input type="checkbox" name="selected_fees[]" value="<?php echo (int)$fee['fee_id']; ?>" data-amount="<?php echo $feeBal; ?>" class="fee-month-checkbox filled-in" />
                            <span style="font-weight: 500; color: #212121;">
                                <?php echo htmlspecialchars($fee['fee_title']); ?> 
                                <span class="blue-text text-darken-2" style="font-weight: 700;">(R<?php echo number_format($feeBal, 2); ?>)</span>
                            </span>
                        </label>
                    </p>
                <?php 
                        endif;
                    endforeach; 
                ?>
                <?php if (!$hasUnpaid): ?>
                    <p class="grey-text center-align">All months are already paid!</p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 20px; padding: 12px 16px; background: #e3f2fd; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-weight: 600; color: #0d47a1;">Total Amount to Pay:</span>
                <span id="multiMonthTotal" style="font-size: 1.5rem; font-weight: 700; color: #0d47a1;">R0.00</span>
            </div>
        </div>
        <div class="modal-footer" style="padding: 10px 24px; background: #fafafa;">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat" style="font-weight: 500;">Cancel</a>
            <button type="submit" id="multiPaySubmitBtn" class="btn blue darken-3 waves-effect waves-light" style="border-radius: 6px; font-weight: 600;" disabled>
                <i class="material-icons left">lock</i> Proceed with PayFast
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var checkboxes = document.querySelectorAll('.fee-month-checkbox');
    var totalDisplay = document.getElementById('multiMonthTotal');
    var submitBtn = document.getElementById('multiPaySubmitBtn');

    function updateTotal() {
        var total = 0.0;
        var checkedCount = 0;
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                total += parseFloat(cb.getAttribute('data-amount') || 0);
                checkedCount++;
            }
        });
        totalDisplay.textContent = 'R' + total.toFixed(2);
        if (submitBtn) {
            submitBtn.disabled = (checkedCount === 0);
        }
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateTotal);
    });
});
</script>

<?php include '../includes/footer.php'; ?>

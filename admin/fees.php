<?php
$page_title = "Tuition Fees";
$allowed_roles = ['admin'];
include '../includes/header.php';

$academicYear = 2026;

// Handle Manual Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_payment') {
    if (validate_csrf_token('admin/fees.php')) {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $feeId     = (int)($_POST['fee_id'] ?? 0);
        $amount    = (float)($_POST['amount'] ?? 50.00);
        $method    = sanitize_input($_POST['payment_method'] ?? 'EFT/Direct Bank');
        $notes     = sanitize_input($_POST['notes'] ?? 'Admin manual settlement');

        if ($studentId > 0 && $feeId > 0 && $amount > 0) {
            $success = FeeService::recordManualPayment($pdo, $studentId, $feeId, $amount, $method, $notes);
            if ($success) {
                $_SESSION['success_message'] = "Manual payment of R" . number_format($amount, 2) . " successfully recorded!";
            } else {
                $_SESSION['error_message'] = "Failed to record manual payment.";
            }
        } else {
            $_SESSION['error_message'] = "Please provide valid student, fee, and amount details.";
        }
        redirect('admin/fees.php');
    }
}

// Handle Generate Billing Run for All Students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'billing_run') {
    if (validate_csrf_token('admin/fees.php')) {
        $runMonth = (int)($_POST['billing_month'] ?? 0);
        $feeAmount = (float)($_POST['fee_amount'] ?? 50.00);

        if ($runMonth >= 1 && $runMonth <= 12) {
            $monthName = date('F', mktime(0, 0, 0, $runMonth, 10));
            $allStudents = get_all_students($pdo);
            $inserted = 0;

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM student_fees WHERE student_id = :sid AND academic_year = :year AND billing_month = :month");
            $stmtInsert = $pdo->prepare("
                INSERT INTO student_fees (student_id, fee_title, academic_year, billing_month, amount, amount_paid, due_date, status)
                VALUES (:sid, :title, :year, :month, :amt, 0.00, :due, 'unpaid')
            ");

            $dueDate = date('Y-m-t', strtotime("{$academicYear}-{$runMonth}-01"));

            foreach ($allStudents as $st) {
                $stmtCheck->execute([':sid' => $st['id'], ':year' => $academicYear, ':month' => $runMonth]);
                if ($stmtCheck->fetchColumn() == 0) {
                    $stmtInsert->execute([
                        ':sid'   => $st['id'],
                        ':title' => "School Fee - {$monthName} {$academicYear}",
                        ':year'  => $academicYear,
                        ':month' => $runMonth,
                        ':amt'   => $feeAmount,
                        ':due'   => $dueDate
                    ]);
                    $inserted++;
                }
            }

            $_SESSION['success_message'] = "Billing run completed! Invoiced {$inserted} students for {$monthName} {$academicYear} at R" . number_format($feeAmount, 2) . " each.";
        } else {
            $_SESSION['error_message'] = "Invalid billing month selected.";
        }
        redirect('admin/fees.php');
    }
}

// Fetch financial metrics
$overview = FeeService::getSystemFinancialOverview($pdo, $academicYear);

// Fetch students with fee balances
$sqlStudents = "
    SELECT 
        s.id, s.student_number, s.full_name, s.course_of_study, s.email,
        COUNT(sf.fee_id) as total_invoices,
        COALESCE(SUM(sf.amount), 0) as total_invoiced,
        COALESCE(SUM(sf.amount_paid), 0) as total_paid,
        COALESCE(SUM(sf.amount - sf.amount_paid), 0) as balance_due,
        SUM(CASE WHEN sf.status = 'paid' THEN 1 ELSE 0 END) as paid_invoices_count
    FROM students s
    LEFT JOIN student_fees sf ON sf.student_id = s.id AND sf.academic_year = :year
    GROUP BY s.id
    ORDER BY s.full_name ASC
";
$stmt = $pdo->prepare($sqlStudents);
$stmt->execute([':year' => $academicYear]);
$studentLedger = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent payment audit log
$recentPayments = $pdo->query("
    SELECT fp.*, sf.fee_title, s.full_name, s.student_number
    FROM fee_payments fp
    LEFT JOIN student_fees sf ON sf.fee_id = fp.fee_id
    JOIN students s ON s.id = fp.student_id
    ORDER BY fp.created_at DESC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

// Unpaid fees for manual payment dropdown
$unpaidFees = $pdo->query("
    SELECT sf.fee_id, sf.fee_title, sf.amount, sf.amount_paid, s.full_name, s.student_number, s.id as student_id
    FROM student_fees sf
    JOIN students s ON s.id = sf.student_id
    WHERE sf.status != 'paid' AND sf.academic_year = {$academicYear}
    ORDER BY s.full_name ASC, sf.billing_month ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col s12">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 15px;">
            <div>
                <h4 style="margin: 0; font-weight: 700; color: #0d47a1;">
                    <i class="material-icons left medium" style="vertical-align: middle;">account_balance</i>
                    Tuition Fees
                </h4>
                <p class="grey-text text-darken-1" style="margin: 5px 0 0 0;">
                    Department of Forestry, Fisheries & the Environment &bull; Student Portal System
                </p>
            </div>
            <div style="margin-top: 10px; display: flex; gap: 8px;">
                <a href="#billingRunModal" class="btn modal-trigger waves-effect waves-light blue darken-3" style="border-radius: 6px; font-weight: 500; text-transform: none;">
                    <i class="material-icons left">play_arrow</i> Monthly Billing Run
                </a>
                <a href="#manualPaymentModal" class="btn modal-trigger waves-effect waves-light green darken-2" style="border-radius: 6px; font-weight: 500; text-transform: none;">
                    <i class="material-icons left">add_circle</i> Record Manual / EFT Payment
                </a>
            </div>
        </div>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<!-- Financial Summary KPIs -->
<div class="row">
    <div class="col s12 m6 l3">
        <div class="card-panel blue darken-2 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">receipt_long</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Total Invoiced</h6>
            <p style="font-size: 1.8rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($overview['total_invoiced'], 2); ?>
            </p>
            <span class="chip tiny white blue-text" style="margin-top: 5px;">
                <?php echo $overview['total_students_billed']; ?> Students Active
            </span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel green darken-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">account_balance_wallet</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Total Collected</h6>
            <p style="font-size: 1.8rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($overview['total_collected'], 2); ?>
            </p>
            <span class="chip tiny white green-text text-darken-2" style="margin-top: 5px;">
                <?php echo $overview['total_paid_invoices']; ?> Invoices Settled
            </span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel red darken-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">pending_actions</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Total Arrears</h6>
            <p style="font-size: 1.8rem; margin: 5px 0 0 0; font-weight: 700;">
                R<?php echo number_format($overview['total_outstanding'], 2); ?>
            </p>
            <span class="chip tiny white red-text" style="margin-top: 5px;">
                <?php echo $overview['total_unpaid_invoices']; ?> Invoices Pending
            </span>
        </div>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel teal darken-1 white-text center-align" style="border-radius: 12px; padding: 18px 12px;">
            <i class="material-icons medium">pie_chart</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Collection Rate</h6>
            <p style="font-size: 1.8rem; margin: 5px 0 0 0; font-weight: 700;">
                <?php echo $overview['collection_rate']; ?>%
            </p>
            <span class="chip tiny white teal-text text-darken-2" style="margin-top: 5px;">
                Target: 100%
            </span>
        </div>
    </div>
</div>

<!-- Main Tabs: Student Ledger vs Transactions -->
<div class="row">
    <div class="col s12">
        <div class="card white" style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e0e0e0;">
            <div class="card-tabs">
                <ul class="tabs tabs-fixed-width">
                    <li class="tab"><a class="active" href="#tabLedger"><i class="material-icons tiny" style="vertical-align: middle;">people</i> Student Directory</a></li>
                    <li class="tab"><a href="#tabAudit"><i class="material-icons tiny" style="vertical-align: middle;">history</i> Payment Transactions</a></li>
                </ul>
            </div>

            <!-- Tab 1: Student Fee Directory -->
            <div id="tabLedger" class="card-content">
                <div style="margin-bottom: 20px;">
                    <input type="text" id="studentFeeSearch" placeholder="Search by student name or student number..." style="border: 1px solid #cfd8dc; border-radius: 8px; padding: 10px 14px; box-sizing: border-box;" />
                </div>

                <div class="responsive-table-wrapper" style="overflow-x: auto;">
                    <table class="striped highlight responsive-table" id="feeLedgerTable">
                        <thead class="blue lighten-5">
                            <tr>
                                <th style="padding: 14px 18px;">Student Number</th>
                                <th>Student Name</th>
                                <th>Course of Study</th>
                                <th>Billed (ZAR)</th>
                                <th>Paid (ZAR)</th>
                                <th>Outstanding</th>
                                <th>Progress</th>
                                <th>Standing</th>
                                <th class="center-align">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentLedger as $row): ?>
                                <?php 
                                    $isGoodStanding = ($row['balance_due'] <= 0);
                                    $ratio = $row['total_invoices'] > 0 ? ($row['paid_invoices_count'] . ' / ' . $row['total_invoices']) : 'None';
                                ?>
                                <tr>
                                    <td style="padding: 14px 18px; font-weight: 600; font-family: monospace;">
                                        <?php echo htmlspecialchars($row['student_number']); ?>
                                    </td>
                                    <td style="font-weight: 600; color: #1565c0;">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['course_of_study']); ?>
                                    </td>
                                    <td style="font-weight: 600;">
                                        R<?php echo number_format($row['total_invoiced'], 2); ?>
                                    </td>
                                    <td style="font-weight: 600; color: #2e7d32;">
                                        R<?php echo number_format($row['total_paid'], 2); ?>
                                    </td>
                                    <td style="font-weight: 700; color: <?php echo $isGoodStanding ? '#2e7d32' : '#c62828'; ?>;">
                                        R<?php echo number_format($row['balance_due'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="chip tiny grey lighten-3"><?php echo $ratio; ?> Paid</span>
                                    </td>
                                    <td>
                                        <?php if ($isGoodStanding): ?>
                                            <span class="chip green lighten-4 green-text text-darken-3" style="font-weight: 600;">
                                                <i class="material-icons tiny left" style="vertical-align: middle;">check_circle</i> Up to Date
                                            </span>
                                        <?php else: ?>
                                            <span class="chip red lighten-4 red-text text-darken-3" style="font-weight: 600;">
                                                <i class="material-icons tiny left" style="vertical-align: middle;">warning</i> In Arrears
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center-align">
                                        <a href="student_profile.php?id=<?php echo (int)$row['id']; ?>" class="btn-small waves-effect waves-light blue-grey darken-1" style="border-radius: 6px; text-transform: none;">
                                            <i class="material-icons left tiny">visibility</i> Profile
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Payment Transactions -->
            <div id="tabAudit" class="card-content">
                <div class="responsive-table-wrapper" style="overflow-x: auto;">
                    <table class="striped responsive-table">
                        <thead class="blue-grey lighten-5">
                            <tr>
                                <th style="padding: 12px 18px;">Reference</th>
                                <th>Student</th>
                                <th>Payment ID</th>
                                <th>Fee Item</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Timestamp</th>
                                <th>Status</th>
                                <th class="center-align">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPayments)): ?>
                                <tr>
                                    <td colspan="9" class="center-align grey-text" style="padding: 25px;">No transactions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPayments as $p): ?>
                                    <tr>
                                        <td style="padding: 12px 18px; font-family: monospace; font-size: 0.88rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($p['transaction_ref']); ?>
                                        </td>
                                        <td style="font-weight: 500;">
                                            <?php echo htmlspecialchars($p['full_name']); ?>
                                            <div class="grey-text" style="font-size: 0.8rem;"><?php echo htmlspecialchars($p['student_number']); ?></div>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.85rem; color: #37474f;">
                                            <?php echo htmlspecialchars($p['payfast_pf_payment_id'] ?? 'Pending'); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($p['fee_title'] ?? 'School Fee Settlement'); ?>
                                        </td>
                                        <td style="font-weight: 700; color: #2e7d32;">
                                            R<?php echo number_format($p['amount'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="chip tiny white blue-text" style="border: 1px solid #bbdefb;">
                                                <?php echo htmlspecialchars($p['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem;">
                                            <?php echo date("d M Y, H:i", strtotime($p['completed_at'] ?? $p['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($p['payment_status'] === 'COMPLETE'): ?>
                                                <span class="chip green lighten-4 green-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    COMPLETE
                                                </span>
                                            <?php elseif ($p['payment_status'] === 'PENDING'): ?>
                                                <span class="chip orange lighten-4 orange-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    PENDING
                                                </span>
                                            <?php else: ?>
                                                <span class="chip red lighten-4 red-text text-darken-3" style="font-weight: 600; margin: 0;">
                                                    <?php echo htmlspecialchars($p['payment_status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="center-align">
                                            <?php if ($p['payment_status'] === 'COMPLETE'): ?>
                                                <a href="<?php echo BASE_URL; ?>student/fee_receipt.php?payment_id=<?php echo (int)$p['payment_id']; ?>" target="_blank" class="btn-small waves-effect waves-light teal lighten-1" style="border-radius: 6px; text-transform: none; font-size: 0.8rem;">
                                                    <i class="material-icons left tiny">receipt</i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="grey-text">&mdash;</span>
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

<!-- Modal: Record Manual / EFT Payment -->
<div id="manualPaymentModal" class="modal" style="border-radius: 14px; max-width: 600px;">
    <form action="fees.php" method="POST">
        <?php csrf_token_field(); ?>
        <input type="hidden" name="action" value="manual_payment">
        <div class="modal-content" style="padding: 25px 30px;">
            <h5 style="margin: 0 0 10px 0; font-weight: 700; color: #2e7d32;">
                <i class="material-icons left" style="vertical-align: middle;">add_circle</i>
                Record Manual / EFT Payment
            </h5>
            <p class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 20px;">
                Record direct bank deposits, cash payments, or bursary disbursements for a student.
            </p>

            <div class="input-field">
                <select name="fee_id" id="manualFeeSelect" required>
                    <option value="" disabled selected>Select an unpaid fee invoice</option>
                    <?php foreach ($unpaidFees as $uf): ?>
                        <option value="<?php echo (int)$uf['fee_id']; ?>" data-student="<?php echo (int)$uf['student_id']; ?>" data-amount="<?php echo (float)$uf['amount'] - (float)$uf['amount_paid']; ?>">
                            <?php echo htmlspecialchars($uf['full_name']); ?> (<?php echo htmlspecialchars($uf['student_number']); ?>) &bull; <?php echo htmlspecialchars($uf['fee_title']); ?> (R<?php echo number_format($uf['amount'] - $uf['amount_paid'], 2); ?> due)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>Unpaid Student Fee Invoice</label>
            </div>

            <input type="hidden" name="student_id" id="manualStudentId" value="">

            <div class="input-field">
                <input type="number" step="0.01" min="1" name="amount" id="manualAmount" value="50.00" required>
                <label for="manualAmount" class="active">Payment Amount (ZAR)</label>
            </div>

            <div class="input-field">
                <select name="payment_method" required>
                    <option value="Direct EFT" selected>Direct EFT / Bank Transfer</option>
                    <option value="Cash Deposit">Cash Deposit at Campus Cashier</option>
                    <option value="Bursary Allocation">Bursary / NSFAS Allocation</option>
                    <option value="Debit Order">Debit Order</option>
                </select>
                <label>Payment Method</label>
            </div>

            <div class="input-field">
                <input type="text" name="notes" id="manualNotes" placeholder="e.g. Bank Ref: FNB-DEP-991823">
                <label for="manualNotes" class="active">Audit Notes / Bank Reference</label>
            </div>
        </div>
        <div class="modal-footer" style="padding: 10px 25px; background: #fafafa;">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Cancel</a>
            <button type="submit" class="btn green darken-2 waves-effect waves-light" style="border-radius: 6px; font-weight: 600;">
                <i class="material-icons left">save</i> Record & Issue Receipt
            </button>
        </div>
    </form>
</div>

<!-- Modal: Generate Monthly Billing Run -->
<div id="billingRunModal" class="modal" style="border-radius: 14px; max-width: 500px;">
    <form action="fees.php" method="POST">
        <?php csrf_token_field(); ?>
        <input type="hidden" name="action" value="billing_run">
        <div class="modal-content" style="padding: 25px 30px;">
            <h5 style="margin: 0 0 10px 0; font-weight: 700; color: #1565c0;">
                <i class="material-icons left" style="vertical-align: middle;">play_arrow</i>
                Execute Monthly Billing Run
            </h5>
            <p class="grey-text text-darken-1" style="font-size: 0.9rem; margin-bottom: 20px;">
                Generates a monthly tuition fee invoice for all active students who do not yet have an invoice for that month.
            </p>

            <div class="input-field">
                <select name="billing_month" required>
                    <option value="2">February 2026</option>
                    <option value="3">March 2026</option>
                    <option value="4" selected>April 2026</option>
                    <option value="5">May 2026</option>
                    <option value="6">June 2026</option>
                    <option value="7">July 2026</option>
                    <option value="8">August 2026</option>
                    <option value="9">September 2026</option>
                    <option value="10">October 2026</option>
                    <option value="11">November 2026</option>
                    <option value="12">December 2026</option>
                </select>
                <label>Target Billing Month</label>
            </div>

            <div class="input-field">
                <input type="number" step="0.01" min="1" name="fee_amount" id="billingAmount" value="50.00" required>
                <label for="billingAmount" class="active">Fee Amount per Student (ZAR)</label>
            </div>
        </div>
        <div class="modal-footer" style="padding: 10px 25px; background: #fafafa;">
            <a href="#!" class="modal-close waves-effect waves-grey btn-flat">Cancel</a>
            <button type="submit" class="btn blue darken-3 waves-effect waves-light" style="border-radius: 6px; font-weight: 600;">
                <i class="material-icons left">check</i> Execute Run
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.tabs');
    M.Tabs.init(tabs);

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

    // Live filter for student fees table
    var searchInput = document.getElementById('studentFeeSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var filter = this.value.toLowerCase();
            var rows = document.querySelectorAll('#feeLedgerTable tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
            });
        });
    }

    // Auto-update student id & amount when fee is selected in manual payment modal
    var feeSelect = document.getElementById('manualFeeSelect');
    var studentIdHidden = document.getElementById('manualStudentId');
    var manualAmountInput = document.getElementById('manualAmount');

    if (feeSelect) {
        feeSelect.addEventListener('change', function() {
            var selectedOption = feeSelect.options[feeSelect.selectedIndex];
            var sid = selectedOption.getAttribute('data-student');
            var amt = selectedOption.getAttribute('data-amount');
            if (sid && studentIdHidden) {
                studentIdHidden.value = sid;
            }
            if (amt && manualAmountInput) {
                manualAmountInput.value = parseFloat(amt).toFixed(2);
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>

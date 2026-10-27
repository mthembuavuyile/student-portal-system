<?php
/**
 * Student PayFast Checkout Dispatcher
 * 
 * Creates a pending payment transaction record in fee_payments,
 * computes PayFast MD5 signature with passphrase, and auto-redirects
 * to PayFast secure hosted payment portal.
 */
require_once '../config.php';
require_once '../functions.php';

require_login(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/fees.php');
}

// CSRF validation
if (!validate_csrf_token('student/fees.php')) {
    exit();
}

// Fetch student profile
$student = null;
if (isset($_SESSION['student_id_number'])) {
    $student = Student::findByStudentNumber($pdo, $_SESSION['student_id_number']);
}

if (!$student) {
    $_SESSION['error_message'] = "Could not resolve your student profile.";
    redirect('student/fees.php');
}

$studentData = $student->toArray();
$studentId = (int)$studentData['id'];

// Determine payment scope: single fee or multi-month selection
$feeId = isset($_POST['fee_id']) ? (int)$_POST['fee_id'] : null;
$selectedFeeIds = isset($_POST['selected_fees']) && is_array($_POST['selected_fees']) ? array_map('intval', $_POST['selected_fees']) : [];

$totalAmount = 0.0;
$itemTitle = "Student School Fee";
$billingMonthStr = '';

if (!empty($selectedFeeIds)) {
    // Multi-month payment
    $placeholders = implode(',', array_fill(0, count($selectedFeeIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE fee_id IN ($placeholders) AND student_id = ? AND status != 'paid'");
    $params = array_merge($selectedFeeIds, [$studentId]);
    $stmt->execute($params);
    $feesToPay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($feesToPay)) {
        $_SESSION['error_message'] = "The selected fees have already been settled or could not be found.";
        redirect('student/fees.php');
    }

    $months = [];
    foreach ($feesToPay as $f) {
        $outstanding = (float)$f['amount'] - (float)$f['amount_paid'];
        $totalAmount += $outstanding;
        $months[] = date('M', mktime(0, 0, 0, (int)$f['billing_month'], 10));
    }
    $itemTitle = "School Fees (" . implode(', ', $months) . " " . ($feesToPay[0]['academic_year'] ?? date('Y')) . ")";
    $billingMonthStr = implode(',', array_column($feesToPay, 'billing_month'));
    $feeId = count($feesToPay) === 1 ? (int)$feesToPay[0]['fee_id'] : null;

} elseif ($feeId) {
    // Single fee payment
    $stmt = $pdo->prepare("SELECT * FROM student_fees WHERE fee_id = :id AND student_id = :sid");
    $stmt->execute([':id' => $feeId, ':sid' => $studentId]);
    $fee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fee || $fee['status'] === 'paid') {
        $_SESSION['error_message'] = "This school fee is already paid or invalid.";
        redirect('student/fees.php');
    }

    $totalAmount = (float)$fee['amount'] - (float)$fee['amount_paid'];
    $itemTitle = $fee['fee_title'];
    $billingMonthStr = (string)$fee['billing_month'];
} else {
    // Quick-pay next pending fee
    $summary = FeeService::getFeeSummary($pdo, $studentId);
    if (!$summary['next_unpaid_fee']) {
        $_SESSION['success_message'] = "You have no outstanding school fees at this time!";
        redirect('student/fees.php');
    }
    $nextFee = $summary['next_unpaid_fee'];
    $feeId = (int)$nextFee['fee_id'];
    $totalAmount = (float)$nextFee['amount'] - (float)$nextFee['amount_paid'];
    $itemTitle = $nextFee['fee_title'];
    $billingMonthStr = (string)$nextFee['billing_month'];
}

if ($totalAmount <= 0) {
    $_SESSION['error_message'] = "Invalid payment amount.";
    redirect('student/fees.php');
}

// 1. Create a pending transaction record in database
$initiation = FeeService::initiatePayment($pdo, $studentId, $totalAmount, $feeId, 'PayFast');
$transactionRef = $initiation['transaction_ref'];

// 2. Build PayFast service and checkout payload with MD5 signature
$payFastService = new PayFastService();
$checkoutData = $payFastService->prepareCheckoutData(
    $studentData,
    $totalAmount,
    $itemTitle,
    $transactionRef,
    [
        'fee_id'        => $feeId,
        'billing_month' => $billingMonthStr
    ]
);

$processUrl = $payFastService->getProcessUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connecting to PayFast Secure Gateway...</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .gateway-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-width: 480px;
            width: 100%;
            padding: 35px 30px;
            text-align: center;
        }
        .badge-sandbox {
            display: inline-block;
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .amount-display {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1565c0;
            margin: 10px 0;
        }
        .item-chip {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 6px 14px;
            border-radius: 8px;
            display: inline-block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 25px;
        }
        .spinner-wrapper {
            margin: 25px 0;
        }
    </style>
</head>
<body>

<div class="gateway-card">
    <?php if ($payFastService->isSandbox()): ?>
        <div class="badge-sandbox">
            <i class="material-icons tiny left" style="vertical-align: middle;">build</i> PayFast Sandbox Testing Mode
        </div>
    <?php endif; ?>

    <h5 style="margin: 0 0 8px 0; font-weight: 700; color: #1a237e;">Department of Forestry, Fisheries & the Environment</h5>
    <p class="grey-text" style="font-size: 0.88rem; margin: 0 0 15px 0;">Student Portal &bull; Official Fee Settlement</p>

    <div class="divider" style="margin-bottom: 20px;"></div>

    <div class="item-chip">
        <i class="material-icons tiny left" style="vertical-align: middle;">receipt_long</i>
        <?php echo htmlspecialchars($itemTitle); ?>
    </div>

    <div class="amount-display">
        R<?php echo number_format($totalAmount, 2); ?>
    </div>
    <p class="grey-text text-darken-1" style="font-size: 0.85rem; margin-top: 0;">
        Reference: <code><?php echo htmlspecialchars($transactionRef); ?></code>
    </p>

    <div class="spinner-wrapper">
        <div class="preloader-wrapper active">
            <div class="spinner-layer spinner-blue-only">
                <div class="circle-clipper left"><div class="circle"></div></div>
                <div class="gap-patch"><div class="circle"></div></div>
                <div class="circle-clipper right"><div class="circle"></div></div>
            </div>
        </div>
        <p style="font-weight: 500; margin-top: 15px; color: #546e7a;">
            Transferring securely to PayFast payment gateway...
        </p>
    </div>

    <!-- Hidden PayFast auto-submit form -->
    <form id="payfastForm" action="<?php echo htmlspecialchars($processUrl); ?>" method="POST">
        <?php foreach ($checkoutData as $name => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars((string)$value); ?>">
        <?php endforeach; ?>
        
        <noscript>
            <p>JavaScript is disabled in your browser. Please click the button below to proceed:</p>
            <button type="submit" class="btn blue darken-2 waves-effect waves-light">Proceed to PayFast</button>
        </noscript>
    </form>

    <div style="margin-top: 25px; font-size: 0.82rem; color: #78909c;">
        <i class="material-icons tiny" style="vertical-align: middle;">lock</i> 256-bit TLS Encrypted &bull; Instant EFT &bull; Visa &bull; Mastercard &bull; SnapScan
    </div>

    <div style="margin-top: 15px;">
        <a href="<?php echo BASE_URL; ?>student/fees.php" class="grey-text text-darken-1" style="font-size: 0.82rem; text-decoration: underline;">
            Cancel and return to Fee Portal
        </a>
    </div>
</div>

<script>
    // Automatically submit form after short delay to give user visual feedback
    window.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            document.getElementById('payfastForm').submit();
        }, 800);
    });
</script>

</body>
</html>

<?php
/**
 * PayFast Instant Transaction Notification (ITN) Webhook Receiver
 * 
 * Asynchronously processes server-to-server callbacks from PayFast:
 * 1. Validates incoming signature with passphrase
 * 2. Confirms payload authenticity with PayFast validate host
 * 3. Idempotently updates fee_payments to COMPLETE
 * 4. Settles the student_fees invoice
 */

// Disable output buffering & display of errors on webhook
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Log file for ITN requests
$logFile = __DIR__ . '/../payfast_itn.log';

function itn_log($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// 1. Verify that this is an HTTP POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
    http_response_code(400);
    itn_log("Rejected: Non-POST request or empty payload received.", $logFile);
    echo "Bad Request: Expected POST payload.";
    exit();
}

$postData = $_POST;
$rawBody = file_get_contents('php://input');

itn_log("Incoming ITN payload: " . json_encode($postData), $logFile);

// 2. Initialize PayFast service
$payFast = new PayFastService();

// 3. Verify cryptographic MD5 signature
if (!$payFast->validateITNSignature($postData)) {
    http_response_code(400);
    itn_log("Signature verification FAILED for transaction: " . ($postData['m_payment_id'] ?? 'unknown'), $logFile);
    echo "Signature mismatch.";
    exit();
}

// 4. In production or sandbox, verify payload directly with PayFast host
$isServerValid = $payFast->verifyWithPayFastServer($postData);
if (!$isServerValid && !PAYFAST_IS_SANDBOX) {
    http_response_code(400);
    itn_log("PayFast Server validation returned INVALID for transaction: " . ($postData['m_payment_id'] ?? 'unknown'), $logFile);
    echo "Server verification failed.";
    exit();
}

// 5. Check payment status
$transactionRef = $postData['m_payment_id'] ?? '';
$pfPaymentId    = $postData['pf_payment_id'] ?? '';
$paymentStatus  = strtoupper($postData['payment_status'] ?? '');
$signature      = $postData['signature'] ?? '';

if (empty($transactionRef)) {
    http_response_code(400);
    itn_log("Error: Missing m_payment_id in payload.", $logFile);
    echo "Missing payment reference.";
    exit();
}

// 6. Process completion
if ($paymentStatus === 'COMPLETE') {
    $reconciled = FeeService::completePayment(
        $pdo,
        $transactionRef,
        $pfPaymentId,
        $signature,
        json_encode($postData)
    );

    if ($reconciled) {
        itn_log("SUCCESS: Transaction {$transactionRef} reconciled. PayFast ID: {$pfPaymentId}", $logFile);
        http_response_code(200);
        echo "OK";
        exit();
    } else {
        itn_log("ERROR: Could not reconcile transaction {$transactionRef} in database.", $logFile);
        http_response_code(500);
        echo "Reconciliation error";
        exit();
    }
} else {
    // Record non-complete status (e.g. FAILED, CANCELLED)
    $stmt = $pdo->prepare("
        UPDATE fee_payments 
        SET payment_status = :status, 
            payfast_pf_payment_id = :pf_id,
            itn_raw_payload = :raw
        WHERE transaction_ref = :ref
    ");
    $stmt->execute([
        ':status' => $paymentStatus,
        ':pf_id'  => $pfPaymentId,
        ':raw'    => json_encode($postData),
        ':ref'    => $transactionRef
    ]);

    itn_log("NOTICE: Transaction {$transactionRef} updated with status: {$paymentStatus}", $logFile);
    http_response_code(200);
    echo "OK";
    exit();
}

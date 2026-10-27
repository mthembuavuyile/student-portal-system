<?php
/**
 * PayFast Payment Gateway Service
 * 
 * Handles payload generation, MD5 cryptographic signature hashing,
 * auto-submitting checkout forms, and ITN (Instant Transaction Notification) verification.
 */
class PayFastService {
    private string $merchantId;
    private string $merchantKey;
    private string $passphrase;
    private string $processUrl;
    private bool $isSandbox;

    public function __construct(
        ?string $merchantId = null,
        ?string $merchantKey = null,
        ?string $passphrase = null,
        ?string $processUrl = null
    ) {
        $this->merchantId = $merchantId ?? (defined('PAYFAST_MERCHANT_ID') ? PAYFAST_MERCHANT_ID : '10040819');
        $this->merchantKey = $merchantKey ?? (defined('PAYFAST_MERCHANT_KEY') ? PAYFAST_MERCHANT_KEY : 'usytx6dae3qlp');
        $this->passphrase = $passphrase ?? (defined('PAYFAST_PASSPHRASE') ? PAYFAST_PASSPHRASE : 'Bitcoinsha256');
        $this->processUrl = $processUrl ?? (defined('PAYFAST_URL') ? PAYFAST_URL : 'https://sandbox.payfast.co.za/eng/process');
        $this->isSandbox = (strpos($this->processUrl, 'sandbox') !== false);
    }

    public function getMerchantId(): string {
        return $this->merchantId;
    }

    public function getMerchantKey(): string {
        return $this->merchantKey;
    }

    public function getPassphrase(): string {
        return $this->passphrase;
    }

    public function getProcessUrl(): string {
        return $this->processUrl;
    }

    public function isSandbox(): bool {
        return $this->isSandbox;
    }

    /**
     * Generates a PayFast MD5 signature from an array of payment parameters.
     *
     * @param array $data Associative array of PayFast parameters
     * @param string|null $passphrase Optional override for merchant passphrase
     * @return string MD5 signature hash
     */
    public function generateSignature(array $data, ?string $passphrase = null): string {
        $passphraseToUse = $passphrase !== null ? $passphrase : $this->passphrase;
        
        // Remove existing signature if present
        unset($data['signature']);

        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '' && $val !== null) {
                $pfOutput .= $key . '=' . urlencode(trim((string)$val)) . '&';
            }
        }

        // Remove trailing ampersand
        $getString = substr($pfOutput, 0, -1);

        if (!empty($passphraseToUse)) {
            $getString .= '&passphrase=' . urlencode(trim($passphraseToUse));
        }

        return md5($getString);
    }

    /**
     * Prepares standard checkout data array for a student school fee payment.
     *
     * @param array $student Associative array or Student object
     * @param float $amount Payment amount in ZAR
     * @param string $itemTitle Title of the fee (e.g. "School Fee - April 2026")
     * @param string $transactionRef Internal unique transaction reference
     * @param array $extraCustom Custom tracking fields (fee_id, student_id, etc.)
     * @return array Ready-to-use parameter array with generated signature
     */
    public function prepareCheckoutData(
        array $student,
        float $amount,
        string $itemTitle,
        string $transactionRef,
        array $extraCustom = []
    ): array {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost/university_system/';
        
        $data = [
            // Merchant details
            'merchant_id'      => $this->merchantId,
            'merchant_key'     => $this->merchantKey,
            'return_url'       => $baseUrl . 'student/fees_return.php?ref=' . urlencode($transactionRef),
            'cancel_url'       => $baseUrl . 'student/fees_cancel.php?ref=' . urlencode($transactionRef),
            'notify_url'       => $baseUrl . 'api/payfast_itn.php',

            // Buyer details
            'name_first'       => $student['first_name'] ?? explode(' ', $student['full_name'] ?? 'Student')[0],
            'name_last'        => $student['surname'] ?? (explode(' ', $student['full_name'] ?? '')[1] ?? 'Student'),
            'email_address'    => $student['email'] ?? 'student@example.com',
            'cell_number'      => $student['phone'] ?? '',

            // Transaction details
            'm_payment_id'     => $transactionRef,
            'amount'           => number_format($amount, 2, '.', ''),
            'item_name'        => $itemTitle,
            'item_description' => 'Monthly School Fee payment for ' . ($student['student_number'] ?? ($student['student_id_number'] ?? 'Student')),
            
            // Custom tracking fields
            'custom_int1'      => (int)($student['id'] ?? 0),
            'custom_str1'      => (string)($extraCustom['fee_id'] ?? ''),
            'custom_str2'      => (string)($extraCustom['billing_month'] ?? ''),
            'custom_str3'      => (string)($student['student_number'] ?? ($student['student_id_number'] ?? '')),
        ];

        // Clean out empty optional parameters
        $data = array_filter($data, function ($v) {
            return $v !== '' && $v !== null;
        });

        // Compute and attach MD5 signature
        $data['signature'] = $this->generateSignature($data, $this->passphrase);

        return $data;
    }

    /**
     * Validates an incoming Instant Transaction Notification (ITN) payload.
     *
     * @param array $postData Raw $_POST received from PayFast
     * @return bool True if signature matches
     */
    public function validateITNSignature(array $postData): bool {
        if (!isset($postData['signature'])) {
            return false;
        }

        $receivedSignature = $postData['signature'];
        $calculatedSignature = $this->generateSignature($postData, $this->passphrase);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    /**
     * Verifies the ITN payload with PayFast's validation host.
     *
     * @param array $postData Raw POST data from PayFast
     * @return bool True if PayFast confirms transaction validity
     */
    public function verifyWithPayFastServer(array $postData): bool {
        $validateHost = $this->isSandbox 
            ? 'sandbox.payfast.co.za' 
            : 'www.payfast.co.za';
            
        $validateUrl = "https://{$validateHost}/eng/query/validate";

        // Convert data to parameter string
        $paramString = '';
        foreach ($postData as $key => $val) {
            $paramString .= $key . '=' . urlencode(trim((string)$val)) . '&';
        }
        $paramString = rtrim($paramString, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $validateUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("PayFast ITN Server Verification cURL error: " . $error);
            return false;
        }

        return trim((string)$response) === 'VALID';
    }
}

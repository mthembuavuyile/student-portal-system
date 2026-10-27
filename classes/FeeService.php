<?php
/**
 * FeeService
 * 
 * Manages student fee invoices, balances, billing cycles,
 * and payment transaction state transitions.
 */
class FeeService {

    /**
     * Retrieves all fee invoices for a student in a given academic year.
     */
    public static function getFeesForStudent(PDO $pdo, int $studentId, int $academicYear = 2026): array {
        $sql = "
            SELECT * FROM student_fees 
            WHERE student_id = :student_id AND academic_year = :year
            ORDER BY billing_month ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':year'       => $academicYear
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculates fee totals, outstanding balance, and next pending invoice.
     */
    public static function getFeeSummary(PDO $pdo, int $studentId, int $academicYear = 2026): array {
        $fees = self::getFeesForStudent($pdo, $studentId, $academicYear);
        
        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        $paidCount = 0;
        $unpaidCount = 0;
        $nextUnpaidFee = null;

        foreach ($fees as $fee) {
            $totalInvoiced += (float)$fee['amount'];
            $totalPaid += (float)$fee['amount_paid'];
            if ($fee['status'] === 'paid') {
                $paidCount++;
            } else {
                $unpaidCount++;
                if ($nextUnpaidFee === null) {
                    $nextUnpaidFee = $fee;
                }
            }
        }

        $balanceDue = max(0.0, $totalInvoiced - $totalPaid);
        $isFullyPaid = ($balanceDue <= 0 && count($fees) > 0);

        return [
            'total_invoiced'   => $totalInvoiced,
            'total_paid'       => $totalPaid,
            'balance_due'      => $balanceDue,
            'fee_count'        => count($fees),
            'paid_count'       => $paidCount,
            'unpaid_count'     => $unpaidCount,
            'next_unpaid_fee'  => $nextUnpaidFee,
            'is_fully_paid'    => $isFullyPaid
        ];
    }

    /**
     * Retrieves completed or pending payments history for a student.
     */
    public static function getPaymentHistory(PDO $pdo, int $studentId): array {
        $sql = "
            SELECT fp.*, sf.fee_title, sf.billing_month, sf.academic_year
            FROM fee_payments fp
            LEFT JOIN student_fees sf ON sf.fee_id = fp.fee_id
            WHERE fp.student_id = :student_id
            ORDER BY fp.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Initiates a new payment entry with a unique transaction reference.
     *
     * @return array Contains 'payment_id' and 'transaction_ref'
     */
    public static function initiatePayment(
        PDO $pdo,
        int $studentId,
        float $amount,
        ?int $feeId = null,
        string $method = 'PayFast'
    ): array {
        // Unique reference: e.g. PF-2026-ST1-A8F24C
        $randSuffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $transactionRef = "PF-" . date('Y') . "-S{$studentId}-" . $randSuffix;

        $sql = "
            INSERT INTO fee_payments (student_id, fee_id, transaction_ref, amount, payment_method, payment_status, created_at)
            VALUES (:student_id, :fee_id, :ref, :amount, :method, 'PENDING', NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':fee_id'     => $feeId,
            ':ref'        => $transactionRef,
            ':amount'     => $amount,
            ':method'     => $method
        ]);

        return [
            'payment_id'      => (int)$pdo->lastInsertId(),
            'transaction_ref' => $transactionRef
        ];
    }

    /**
     * Completes a payment, reconciling the transaction and marking the fee paid.
     */
    public static function completePayment(
        PDO $pdo,
        string $transactionRef,
        string $pfPaymentId,
        ?string $signature = null,
        ?string $rawPayload = null
    ): bool {
        try {
            $pdo->beginTransaction();

            // Locate the payment record
            $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE transaction_ref = :ref FOR UPDATE");
            $stmt->execute([':ref' => $transactionRef]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $pdo->rollBack();
                error_log("Payment reconciliation error: Transaction ref {$transactionRef} not found.");
                return false;
            }

            if ($payment['payment_status'] === 'COMPLETE') {
                $pdo->commit();
                return true; // Already processed idempotent notification
            }

            // Update payment transaction record
            $updatePayment = $pdo->prepare("
                UPDATE fee_payments 
                SET payment_status = 'COMPLETE',
                    payfast_pf_payment_id = :pf_id,
                    payfast_signature = :sig,
                    itn_raw_payload = :raw,
                    completed_at = NOW()
                WHERE transaction_ref = :ref
            ");
            $updatePayment->execute([
                ':pf_id' => $pfPaymentId,
                ':sig'   => $signature,
                ':raw'   => $rawPayload,
                ':ref'   => $transactionRef
            ]);

            // Reconcile student_fees invoice
            if (!empty($payment['fee_id'])) {
                $updateFee = $pdo->prepare("
                    UPDATE student_fees 
                    SET status = 'paid',
                        amount_paid = amount,
                        updated_at = NOW()
                    WHERE fee_id = :fee_id
                ");
                $updateFee->execute([':fee_id' => $payment['fee_id']]);
            } else {
                // If fee_id was not directly assigned, apply towards oldest unpaid fees
                self::applyAmountToOldestFees($pdo, (int)$payment['student_id'], (float)$payment['amount']);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to complete payment {$transactionRef}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Applies an amount to a student's oldest unpaid fees in order.
     */
    public static function applyAmountToOldestFees(PDO $pdo, int $studentId, float $amount): void {
        $stmt = $pdo->prepare("
            SELECT * FROM student_fees 
            WHERE student_id = :student_id AND status != 'paid' 
            ORDER BY academic_year ASC, billing_month ASC
        ");
        $stmt->execute([':student_id' => $studentId]);
        $unpaidFees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $amount;
        foreach ($unpaidFees as $fee) {
            if ($remaining <= 0) break;
            $outstandingOnFee = (float)$fee['amount'] - (float)$fee['amount_paid'];
            
            if ($remaining >= $outstandingOnFee) {
                // Fully settle this fee
                $upd = $pdo->prepare("UPDATE student_fees SET status = 'paid', amount_paid = amount WHERE fee_id = :id");
                $upd->execute([':id' => $fee['fee_id']]);
                $remaining -= $outstandingOnFee;
            } else {
                // Partially settle
                $newPaid = (float)$fee['amount_paid'] + $remaining;
                $upd = $pdo->prepare("UPDATE student_fees SET status = 'partially_paid', amount_paid = :amt WHERE fee_id = :id");
                $upd->execute([':amt' => $newPaid, ':id' => $fee['fee_id']]);
                $remaining = 0;
            }
        }
    }

    /**
     * Manual payment recording for administrators (Cash, EFT, Bursary).
     */
    public static function recordManualPayment(
        PDO $pdo,
        int $studentId,
        int $feeId,
        float $amount,
        string $method = 'Manual/EFT',
        string $notes = ''
    ): bool {
        try {
            $pdo->beginTransaction();

            $ref = "MAN-" . date('Ymd') . "-S{$studentId}-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

            $stmt = $pdo->prepare("
                INSERT INTO fee_payments (student_id, fee_id, transaction_ref, payfast_pf_payment_id, amount, payment_method, payment_status, itn_raw_payload, created_at, completed_at)
                VALUES (:student_id, :fee_id, :ref, 'MANUAL', :amount, :method, 'COMPLETE', :notes, NOW(), NOW())
            ");
            $stmt->execute([
                ':student_id' => $studentId,
                ':fee_id'     => $feeId,
                ':ref'        => $ref,
                ':amount'     => $amount,
                ':method'     => $method,
                ':notes'      => $notes
            ]);

            $upd = $pdo->prepare("
                UPDATE student_fees 
                SET status = 'paid', amount_paid = amount, notes = :notes 
                WHERE fee_id = :fee_id
            ");
            $upd->execute([
                ':notes'  => $notes ?: 'Admin manual settlement',
                ':fee_id' => $feeId
            ]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Manual payment error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Admin overview: fetches overall system financial metrics.
     */
    public static function getSystemFinancialOverview(PDO $pdo, int $academicYear = 2026): array {
        $sql = "
            SELECT 
                COUNT(DISTINCT student_id) as total_students_billed,
                SUM(amount) as total_invoiced,
                SUM(amount_paid) as total_collected,
                SUM(amount - amount_paid) as total_outstanding,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as total_paid_invoices,
                SUM(CASE WHEN status != 'paid' THEN 1 ELSE 0 END) as total_unpaid_invoices
            FROM student_fees
            WHERE academic_year = :year
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':year' => $academicYear]);
        $overview = $stmt->fetch(PDO::FETCH_ASSOC);

        $overview['total_invoiced'] = (float)($overview['total_invoiced'] ?? 0);
        $overview['total_collected'] = (float)($overview['total_collected'] ?? 0);
        $overview['total_outstanding'] = (float)($overview['total_outstanding'] ?? 0);
        $overview['collection_rate'] = $overview['total_invoiced'] > 0 
            ? round(($overview['total_collected'] / $overview['total_invoiced']) * 100, 1) 
            : 0;

        return $overview;
    }
}

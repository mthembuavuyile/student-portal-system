<?php
$page_title = "Payment Cancelled";
$allowed_roles = ['student'];
include '../includes/header.php';

$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
if (!empty($ref)) {
    // Optionally flag payment as cancelled
    $stmt = $pdo->prepare("UPDATE fee_payments SET payment_status = 'CANCELLED' WHERE transaction_ref = :ref AND payment_status = 'PENDING'");
    $stmt->execute([':ref' => $ref]);
}
?>

<div class="row" style="margin-top: 40px;">
    <div class="col s12 m8 offset-m2 l6 offset-l3">
        <div class="card white center-align" style="border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 35px 25px;">
            <div style="width: 80px; height: 80px; background: #fff3e0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="material-icons orange-text text-darken-3" style="font-size: 48px;">cancel</i>
            </div>

            <h4 style="margin: 0 0 10px 0; font-weight: 700; color: #e65100;">Payment Cancelled</h4>
            <p class="grey-text text-darken-1" style="font-size: 1.05rem; margin-bottom: 25px;">
                Your PayFast transaction was cancelled or interrupted. No funds were deducted from your account.
            </p>

            <?php if (!empty($ref)): ?>
                <p class="grey-text text-darken-1" style="font-size: 0.88rem; margin-bottom: 25px;">
                    Session Reference: <code><?php echo htmlspecialchars($ref); ?></code>
                </p>
            <?php endif; ?>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>student/fees.php" class="btn-large blue darken-3 waves-effect waves-light" style="border-radius: 8px; text-transform: none; font-weight: 600;">
                    <i class="material-icons left">refresh</i> Return to School Fees
                </a>
                <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="btn-large grey lighten-3 grey-text text-darken-3 waves-effect waves-grey" style="border-radius: 8px; text-transform: none; font-weight: 600;">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
$page_title = "Change Password";
$allowed_roles = ['admin', 'student'];
require_once __DIR__ . '/includes/header.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token('change_password.php')) {
        // validate_csrf_token will redirect on failure
        exit();
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $result = User::changePassword($pdo, (int)$_SESSION['user_id'], $current_password, $new_password, $confirm_password);

    if ($result['success']) {
        // Log security action
        $log_dir = __DIR__ . '/admin/security_audit.log';
        $log_msg = "User '" . ($_SESSION['username'] ?? 'unknown') . "' (Role: " . ($_SESSION['role'] ?? 'user') . ", ID: " . $_SESSION['user_id'] . ") successfully changed their password.";
        log_action($log_msg, $log_dir);

        $_SESSION['success_message'] = $result['message'];
        redirect('change_password.php');
    } else {
        $error_message = $result['message'];
    }
}

$dashboard_link = ($_SESSION['role'] === 'admin') ? BASE_URL . 'admin/dashboard.php' : BASE_URL . 'student/dashboard.php';
?>

<div class="row" style="margin-top: 30px;">
    <div class="col s12 m8 offset-m2 l6 offset-l3">
        <div class="card hoverable">
            <div class="card-content">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center;">
                        <i class="material-icons medium blue-text text-darken-3" style="margin-right: 12px;">lock</i>
                        <div>
                            <h5 style="margin: 0; font-weight: 600;">Change Password</h5>
                            <span class="grey-text text-darken-1" style="font-size: 0.9rem;">
                                Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="chip <?php echo $_SESSION['role'] === 'admin' ? 'blue darken-3' : 'green darken-2'; ?> white-text">
                            <?php echo ucfirst($_SESSION['role']); ?>
                        </span>
                    </div>
                </div>

                <div class="divider" style="margin-bottom: 20px;"></div>

                <?php display_session_message('success_message'); ?>

                <?php if (!empty($error_message)): ?>
                    <div class="card-panel red white-text" style="padding: 12px 16px; margin-bottom: 20px;">
                        <i class="material-icons left" style="margin-right: 8px;">error_outline</i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="change_password.php" id="changePasswordForm">
                    <?php csrf_token_field(); ?>
                    <!-- Current Password -->
                    <div class="row">
                        <div class="input-field col s12" style="position: relative;">
                            <i class="material-icons prefix">lock_outline</i>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                            <label for="current_password">Current Password</label>
                            <i class="material-icons toggle-pwd" data-target="current_password" 
                               style="position: absolute; right: 15px; top: 12px; cursor: pointer; color: #757575;" 
                               title="Toggle visibility">visibility</i>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="row">
                        <div class="input-field col s12" style="position: relative;">
                            <i class="material-icons prefix">vpn_key</i>
                            <input id="new_password" name="new_password" type="password" minlength="6" required autocomplete="new-password">
                            <label for="new_password">New Password</label>
                            <i class="material-icons toggle-pwd" data-target="new_password" 
                               style="position: absolute; right: 15px; top: 12px; cursor: pointer; color: #757575;" 
                               title="Toggle visibility">visibility</i>
                            <span class="helper-text" data-error="Minimum 6 characters required" data-success="">Must be at least 6 characters long</span>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="row">
                        <div class="input-field col s12" style="position: relative;">
                            <i class="material-icons prefix">check_circle_outline</i>
                            <input id="confirm_password" name="confirm_password" type="password" minlength="6" required autocomplete="new-password">
                            <label for="confirm_password">Confirm New Password</label>
                            <i class="material-icons toggle-pwd" data-target="confirm_password" 
                               style="position: absolute; right: 15px; top: 12px; cursor: pointer; color: #757575;" 
                               title="Toggle visibility">visibility</i>
                            <span id="password_match_feedback" class="helper-text"></span>
                        </div>
                    </div>

                    <!-- Password Security Advice -->
                    <div class="card-panel grey lighten-4 grey-text text-darken-2" style="padding: 10px 15px; font-size: 0.85rem; border-left: 4px solid #1976d2;">
                        <i class="material-icons tiny left blue-text">info</i>
                        Use a strong password with a mix of letters, numbers, and special characters. Never share your credentials with others.
                    </div>

                    <!-- Form Actions -->
                    <div class="row" style="margin-top: 25px; margin-bottom: 0;">
                        <div class="col s12" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <a href="<?php echo $dashboard_link; ?>" class="btn-flat waves-effect grey-text text-darken-2">
                                <i class="material-icons left">arrow_back</i>Dashboard
                            </a>
                            <button type="submit" class="btn waves-effect waves-light blue darken-3">
                                <i class="material-icons left">save</i>Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/Hide password toggle handlers
    var toggles = document.querySelectorAll('.toggle-pwd');
    toggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'visibility_off';
                this.style.color = '#1976d2';
            } else {
                input.type = 'password';
                this.textContent = 'visibility';
                this.style.color = '#757575';
            }
        });
    });

    // Real-time password match feedback
    var newPwd = document.getElementById('new_password');
    var confPwd = document.getElementById('confirm_password');
    var matchFeedback = document.getElementById('password_match_feedback');

    function checkPasswordMatch() {
        if (!confPwd.value) {
            matchFeedback.textContent = '';
            matchFeedback.className = 'helper-text';
            return;
        }
        if (newPwd.value === confPwd.value) {
            matchFeedback.textContent = 'Passwords match!';
            matchFeedback.className = 'helper-text green-text';
        } else {
            matchFeedback.textContent = 'Passwords do not match.';
            matchFeedback.className = 'helper-text red-text';
        }
    }

    if (newPwd && confPwd) {
        newPwd.addEventListener('input', checkPasswordMatch);
        confPwd.addEventListener('input', checkPasswordMatch);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

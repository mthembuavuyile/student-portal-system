<?php
require_once 'config.php';
require_once 'functions.php';

// If user is already logged in, redirect them to their respective dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'lecturer') {
        redirect('lecturer/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$error_message = '';
$selected_role = 'student'; // Default active tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username      = sanitize_input($_POST['username'] ?? '');
    $password      = $_POST['password'] ?? '';
    $selected_role = sanitize_input($_POST['selected_role'] ?? 'student');

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Find user by username or student ID number
            $sql = "SELECT id, username, password, role, student_id_number, lecturer_id 
                    FROM users 
                    WHERE username = :username OR student_id_number = :username2 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username, ':username2' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id']           = $user['id'];
                $_SESSION['username']          = $user['username'];
                $_SESSION['role']              = $user['role'];
                $_SESSION['student_id_number'] = $user['student_id_number'];
                $_SESSION['lecturer_id']       = $user['lecturer_id'];

                // Redirect based on actual verified role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } elseif ($user['role'] === 'lecturer') {
                    redirect('lecturer/dashboard.php');
                } else {
                    redirect('student/dashboard.php');
                }
            } else {
                $error_message = "Invalid username or password. Please verify your credentials.";
            }
        } catch (PDOException $e) {
            $error_message = "Database connection error: " . $e->getMessage();
        }
    }
}

$page_title = "Portal Login - Student Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Google Fonts: Inter & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 40%, #1e88e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 10px;
            box-sizing: border-box;
        }

        .login-card-wrapper {
            width: 100%;
            max-width: 480px;
            margin: auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .login-header {
            background: #ffffff;
            padding: 28px 24px 18px 24px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .login-logo {
            max-width: 105px;
            height: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
        }

        .login-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1a237e;
            margin: 0 0 4px 0;
        }

        .login-subtitle {
            font-size: 0.82rem;
            color: #757575;
            margin: 0;
        }

        /* Role Switcher Tabs */
        .role-switch-container {
            display: flex;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 10px;
            margin: 20px 24px 12px 24px;
            gap: 4px;
        }

        .role-tab-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 8px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .role-tab-btn i {
            font-size: 18px;
            margin-right: 6px;
        }

        .role-tab-btn:hover {
            color: #1e293b;
            background: rgba(255, 255, 255, 0.6);
        }

        .role-tab-btn.active[data-role="student"] {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 3px 8px rgba(2, 132, 199, 0.15);
        }

        .role-tab-btn.active[data-role="lecturer"] {
            background: #ffffff;
            color: #4f46e5;
            box-shadow: 0 3px 8px rgba(79, 70, 229, 0.15);
        }

        .role-tab-btn.active[data-role="admin"] {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.15);
        }

        .role-info-banner {
            margin: 0 24px 16px 24px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .banner-student { background: #e0f2fe; color: #0369a1; }
        .banner-lecturer { background: #ede9fe; color: #5b21b6; }
        .banner-admin { background: #f1f5f9; color: #1e293b; }

        .login-body {
            padding: 0 24px 24px 24px;
        }

        .input-field-custom {
            margin-bottom: 20px;
            position: relative;
        }

        .input-field-custom label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrap i.prefix-icon {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 20px;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            height: 44px;
            padding: 0 12px 0 42px !important;
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            font-size: 0.94rem !important;
            color: #1e293b !important;
            background: #f8fafc !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            transition: all 0.2s ease;
        }

        .input-wrap input:focus {
            border-color: #1565c0 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.15) !important;
            outline: none;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #94a3b8;
            font-size: 20px;
            user-select: none;
        }

        .btn-submit-login {
            width: 100%;
            height: 48px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.98rem;
            text-transform: none;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(21, 101, 192, 0.25);
        }

        .btn-submit-student { background-color: #0284c7 !important; }
        .btn-submit-lecturer { background-color: #4f46e5 !important; }
        .btn-submit-admin { background-color: #0f172a !important; }

        /* Demo credentials section */
        .demo-credentials-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 20px;
        }

        .demo-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .demo-chips-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .demo-chip-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 0.78rem;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
            user-select: none;
        }

        .demo-chip-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .demo-chip-btn strong {
            color: #0f172a;
        }

        .system-attribution {
            text-align: center;
            margin-top: 18px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.78rem;
        }
    </style>
</head>
<body>

<div class="login-card-wrapper">
    <div class="login-card">
        <!-- Brand Header -->
        <div class="login-header">
            <img src="assets/images/logo.png" alt="University System Logo" class="login-logo">
            <h1 class="login-title">Student Portal System</h1>
            <p class="login-subtitle">Department of Forestry, Fisheries &amp; the Environment</p>
        </div>

        <!-- Role Switcher -->
        <div class="role-switch-container" id="roleSwitcher">
            <button type="button" class="role-tab-btn <?php echo $selected_role === 'student' ? 'active' : ''; ?>" data-role="student">
                <i class="material-icons">school</i>Student
            </button>
            <button type="button" class="role-tab-btn <?php echo $selected_role === 'lecturer' ? 'active' : ''; ?>" data-role="lecturer">
                <i class="material-icons">menu_book</i>Lecturer
            </button>
            <button type="button" class="role-tab-btn <?php echo $selected_role === 'admin' ? 'active' : ''; ?>" data-role="admin">
                <i class="material-icons">admin_panel_settings</i>Admin
            </button>
        </div>

        <!-- Dynamic Role Context Banner -->
        <div id="roleBanner" class="role-info-banner banner-student">
            <i class="material-icons tiny" id="bannerIcon">school</i>
            <span id="bannerText">Student Self-Service &amp; AI Success Advisor</span>
        </div>

        <!-- Form Body -->
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
                <div class="card-panel red lighten-4 red-text text-darken-4" style="padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                    <i class="material-icons tiny">error</i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" id="loginForm">
                <input type="hidden" name="selected_role" id="selectedRoleInput" value="<?php echo htmlspecialchars($selected_role); ?>">

                <!-- Username Field -->
                <div class="input-field-custom">
                    <label for="username" id="usernameLabel">Student Number or Username</label>
                    <div class="input-wrap">
                        <i class="material-icons prefix-icon" id="usernameIcon">school</i>
                        <input id="username" type="text" name="username" placeholder="e.g. 10ha2304014" required autocomplete="username">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="input-field-custom">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="material-icons prefix-icon">lock</i>
                        <input id="password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="material-icons toggle-password" id="togglePassword">visibility_off</i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button class="btn waves-effect waves-light btn-submit-login btn-submit-student" type="submit" id="btnSubmit">
                    <span>Login to Student Portal</span>
                    <i class="material-icons right">arrow_forward</i>
                </button>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="demo-credentials-box">
                <div class="demo-title">
                    <i class="material-icons tiny">vpn_key</i> Quick Demo Credentials:
                </div>
                <div class="demo-chips-grid">
                    <div class="demo-chip-btn" data-role="student" data-user="10ha2304014" data-pass="bitcoin" title="Click to autofill student demo">
                        🎓 <strong>Student:</strong> 10ha2304014
                    </div>
                    <div class="demo-chip-btn" data-role="lecturer" data-user="lecturer" data-pass="password123" title="Click to autofill lecturer demo">
                        👨‍🏫 <strong>Lecturer:</strong> lecturer
                    </div>
                    <div class="demo-chip-btn" data-role="admin" data-user="admin" data-pass="admin123" title="Click to autofill admin demo">
                        🛡️ <strong>Admin:</strong> admin
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attribution -->
    <div class="system-attribution">
        Developed by Mr Avuyile Mthembu &bull; ICT Services: Infrastructure &amp; Security Support
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleButtons   = document.querySelectorAll('.role-tab-btn');
    const roleInput     = document.getElementById('selectedRoleInput');
    const roleBanner    = document.getElementById('roleBanner');
    const bannerIcon    = document.getElementById('bannerIcon');
    const bannerText    = document.getElementById('bannerText');
    const usernameLabel = document.getElementById('usernameLabel');
    const usernameIcon  = document.getElementById('usernameIcon');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn     = document.getElementById('btnSubmit');
    const togglePassBtn = document.getElementById('togglePassword');

    const roleConfigs = {
        student: {
            bannerClass: 'banner-student',
            bannerIcon: 'school',
            bannerText: 'Student Self-Service & AI Success Advisor',
            usernameLabel: 'Student Number or Username',
            usernameIcon: 'school',
            usernamePlaceholder: 'e.g. 10ha2304014',
            btnClass: 'btn-submit-student',
            btnText: 'Login to Student Portal'
        },
        lecturer: {
            bannerClass: 'banner-lecturer',
            bannerIcon: 'menu_book',
            bannerText: 'Educator Portal & Marksheet Management',
            usernameLabel: 'Educator Username or Email',
            usernameIcon: 'badge',
            usernamePlaceholder: 'e.g. lecturer or n.sithole',
            btnClass: 'btn-submit-lecturer',
            btnText: 'Login as Educator / Lecturer'
        },
        admin: {
            bannerClass: 'banner-admin',
            bannerIcon: 'admin_panel_settings',
            bannerText: 'Institutional Administration & Governance',
            usernameLabel: 'Administrator Username',
            usernameIcon: 'admin_panel_settings',
            usernamePlaceholder: 'e.g. admin',
            btnClass: 'btn-submit-admin',
            btnText: 'Login as Administrator'
        }
    };

    function applyRole(role) {
        const config = roleConfigs[role] || roleConfigs.student;

        // Update active tab buttons
        roleButtons.forEach(btn => {
            if (btn.getAttribute('data-role') === role) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update hidden input
        roleInput.value = role;

        // Update banner
        roleBanner.className = 'role-info-banner ' + config.bannerClass;
        bannerIcon.textContent = config.bannerIcon;
        bannerText.textContent = config.bannerText;

        // Update username inputs
        usernameLabel.textContent = config.usernameLabel;
        usernameIcon.textContent = config.usernameIcon;
        usernameInput.placeholder = config.usernamePlaceholder;

        // Update submit button
        submitBtn.className = 'btn waves-effect waves-light btn-submit-login ' + config.btnClass;
        submitBtn.querySelector('span').textContent = config.btnText;
    }

    // Role switcher click event
    roleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const role = this.getAttribute('data-role');
            applyRole(role);
        });
    });

    // Password visibility toggle
    if (togglePassBtn) {
        togglePassBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePassBtn.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                togglePassBtn.textContent = 'visibility_off';
            }
        });
    }

    // Quick demo credentials autofill
    document.querySelectorAll('.demo-chip-btn').forEach(chip => {
        chip.addEventListener('click', function() {
            const role = this.getAttribute('data-role');
            const user = this.getAttribute('data-user');
            const pass = this.getAttribute('data-pass');

            applyRole(role);
            usernameInput.value = user;
            passwordInput.value = pass;

            // Highlight input briefly
            usernameInput.focus();
            M.toast({ html: 'Autofilled ' + role + ' credentials!', displayLength: 2000, classes: 'blue' });
        });
    });

    // Initialize state
    applyRole(roleInput.value || 'student');
});
</script>
</body>
</html>
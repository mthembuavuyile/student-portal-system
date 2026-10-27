<?php
$page_title = "Student Dashboard";
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
} else {
    $_SESSION['error_message'] = "Could not identify your student profile.";
    redirect('logout.php');
}

$activeEnrollment = $student ? $student->getActiveEnrollment($pdo) : null;
$moduleResults = $student ? $student->getModuleResults($pdo) : [];

// Calculate cumulative average
$totalAvg = 0;
$avgCount = 0;
foreach ($moduleResults as $res) {
    if ($res['average'] !== null) {
        $totalAvg += (float)$res['average'];
        $avgCount++;
    }
}
$cumulativeAverage = $avgCount > 0 ? round($totalAvg / $avgCount, 2) : null;

// School Fee summary
$feeSummary = $student ? FeeService::getFeeSummary($pdo, (int)$student->getId()) : null;
?>

<div class="row">
    <div class="col s12">
        <h4>Welcome, <?php echo htmlspecialchars($student ? $student->getFirstName() : 'Student'); ?>!</h4>
        <p class="grey-text">Department of Forestry, Fisheries & the Environment &bull; Student Portal System</p>
        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<!-- Information Cards -->
<div class="row">
    <?php if ($student): ?>
    <div class="col s6 m6 l3">
        <div class="card-panel blue lighten-1 white-text center-align" style="padding: 20px 10px; border-radius: 12px;">
            <i class="material-icons medium">school</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">My Course</h6>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; font-weight: 500; word-break: break-word;">
                <?php echo htmlspecialchars($activeEnrollment['course_name'] ?? $student->getCourseOfStudy()); ?>
            </p>
            <span class="chip tiny white blue-text" style="margin-top: 5px;">
                <?php echo htmlspecialchars($activeEnrollment['duration'] ?? '3 Years'); ?>
            </span>
        </div>
    </div>
    <div class="col s6 m6 l3">
        <div class="card-panel <?php echo ($student->getAcademicStatus() === STATUS_ACTIVE) ? 'green' : 'orange'; ?> lighten-1 white-text center-align" style="padding: 20px 10px; border-radius: 12px;">
            <i class="material-icons medium">verified_user</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Status</h6>
            <p style="font-size: 1.1rem; margin: 5px 0 0 0; font-weight: 600;">
                <?php echo htmlspecialchars($student->getAcademicStatus()); ?>
            </p>
            <span class="chip tiny white green-text text-darken-2" style="margin-top: 5px;">1 Active Course</span>
        </div>
    </div>
    <div class="col s6 m6 l3">
        <div class="card-panel indigo lighten-1 white-text center-align" style="padding: 20px 10px; border-radius: 12px;">
            <i class="material-icons medium">assessment</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Average</h6>
            <p style="font-size: 1.4rem; margin: 5px 0 0 0; font-weight: 700;">
                <?php echo $cumulativeAverage !== null ? $cumulativeAverage . '%' : 'Pending'; ?>
            </p>
            <span class="chip tiny white indigo-text" style="margin-top: 5px;">
                <?php echo count($moduleResults); ?> Modules
            </span>
        </div>
    </div>
    <div class="col s6 m6 l3">
        <div class="card-panel teal lighten-1 white-text center-align" style="padding: 20px 10px; border-radius: 12px;">
            <i class="material-icons medium">event</i>
            <h6 style="margin: 5px 0 0 0; font-weight: 600;">Registered</h6>
            <p style="font-size: 0.85rem; margin: 5px 0 0 0; font-weight: 500;">
                <?php echo date("M d, Y", strtotime($student->getEnrollmentDate())); ?>
            </p>
            <span class="chip tiny white teal-text" style="margin-top: 5px;">
                <?php echo htmlspecialchars($student->getStudentNumber()); ?>
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="col s12">
        <div class="card-panel red white-text">
            There was an issue loading your details. Please contact an administrator.
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- School Fee Status Banner -->
<?php if ($feeSummary): ?>
<div class="row">
    <div class="col s12">
        <div class="card white" style="border-radius: 12px; border-left: 6px solid <?php echo $feeSummary['is_fully_paid'] ? '#2e7d32' : '#f57c00'; ?>; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <div class="card-content" style="padding: 18px 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 50px; height: 50px; border-radius: 10px; background: <?php echo $feeSummary['is_fully_paid'] ? '#e8f5e9' : '#fff3e0'; ?>; display: flex; align-items: center; justify-content: center;">
                            <i class="material-icons <?php echo $feeSummary['is_fully_paid'] ? 'green-text text-darken-2' : 'orange-text text-darken-3'; ?>" style="font-size: 28px;">
                                <?php echo $feeSummary['is_fully_paid'] ? 'verified' : 'account_balance_wallet'; ?>
                            </i>
                        </div>
                        <div>
                            <h6 style="margin: 0; font-weight: 700; color: #1a237e;">
                                Tuition Fees: <?php echo $feeSummary['is_fully_paid'] ? 'Up to date' : 'R' . number_format($feeSummary['balance_due'], 2) . ' Outstanding'; ?>
                            </h6>
                            <p class="grey-text text-darken-1" style="margin: 3px 0 0 0; font-size: 0.88rem;">
                                <?php echo $feeSummary['paid_count']; ?> of <?php echo $feeSummary['fee_count']; ?> installments settled
                                <?php if ($feeSummary['next_unpaid_fee']): ?>
                                    &bull; Next due: <?php echo htmlspecialchars($feeSummary['next_unpaid_fee']['fee_title']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <?php if ($feeSummary['next_unpaid_fee']): ?>
                            <form action="<?php echo BASE_URL; ?>student/pay_fee.php" method="POST" style="margin: 0;">
                                <?php csrf_token_field(); ?>
                                <input type="hidden" name="fee_id" value="<?php echo (int)$feeSummary['next_unpaid_fee']['fee_id']; ?>">
                                <button type="submit" class="btn blue darken-3 waves-effect waves-light" style="border-radius: 6px; text-transform: none; font-weight: 500; height: 38px; line-height: 38px; padding: 0 20px;">
                                    Pay
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>student/fees.php" class="btn-flat waves-effect" style="border: 1px solid #1565c0; color: #1565c0; border-radius: 6px; text-transform: none; font-weight: 500; height: 38px; line-height: 38px; padding: 0 16px;">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- AI Academic Success Advisor Banner -->
<div class="row">
    <div class="col s12">
        <div class="card ai-advisor-banner z-depth-3">
            <!-- Aurora ambient glowing mesh -->
            <div class="ai-aurora-glow"></div>
            <!-- Large subtle decorative watermark -->
            <i class="material-icons ai-watermark-icon">psychology</i>

            <div class="card-content" style="padding: 28px 32px; position: relative; z-index: 2;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
                    <div style="flex: 1 1 540px; min-width: 0;">
                        <!-- Category Badges & Live Status -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="ai-badge-engine">
                                <i class="material-icons">auto_awesome</i>Smart AI Engine
                            </span>
                            <span class="ai-badge-openrouter">
                                <i class="material-icons" style="font-size: 13px; color: #38bdf8;">bolt</i>OpenRouter Powered
                            </span>
                            <span class="ai-badge-status">
                                <span class="ai-pulse-dot"></span>24/7 Academic Coach Active
                            </span>
                        </div>

                        <!-- Hero Title with Gradient Accent -->
                        <h4 class="ai-hero-title">
                            Personalized AI Performance Advisor &amp; <span class="ai-gradient-text">Study Coach</span>
                        </h4>

                        <!-- Description -->
                        <p class="ai-hero-desc">
                            Get instant diagnostic feedback on your quarterly marks! Find out which modules need intervention, receive custom revision timetables, and chat live with an academic coach.
                        </p>

                        <!-- Micro-Feature Chips (Visual Hierarchy & Quick Highlights) -->
                        <div class="ai-features-grid">
                            <span class="ai-feature-chip">
                                <i class="material-icons purple-text text-lighten-3">insights</i>Diagnostic Gap Analysis
                            </span>
                            <span class="ai-feature-chip">
                                <i class="material-icons cyan-text text-accent-2">event_repeat</i>Custom Revision Timetables
                            </span>
                            <span class="ai-feature-chip">
                                <i class="material-icons amber-text text-accent-2">forum</i>Interactive Transcript Chat
                            </span>
                        </div>
                    </div>

                    <!-- Action Area -->
                    <div class="ai-actions-container" style="display: flex; flex-direction: column; gap: 12px; align-items: stretch; justify-content: center; min-width: 220px;">
                        <a href="results.php#aiCoachCard" class="ai-btn-primary waves-effect waves-light">
                            <i class="material-icons">psychology</i>Analyze Performance
                        </a>
                        <a href="results.php#aiCoachCard" class="ai-btn-secondary waves-effect waves-light">
                            <i class="material-icons" style="font-size: 18px; color: #818cf8;">chat</i>Chat with AI Coach
                        </a>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 5px; color: rgba(203, 213, 225, 0.75); font-size: 0.74rem; margin-top: 2px;">
                            <i class="material-icons" style="font-size: 13px; color: #34d399;">verified</i> Instant Academic Diagnostics
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links & Actions -->
<div class="row">
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title" style="font-weight: 600;"><i class="material-icons left blue-text">touch_app</i>Quick Actions</span>
                <div class="collection" style="margin-top: 15px;">
                    <a href="results.php#aiCoachCard" class="collection-item waves-effect" style="background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4338ca; font-weight: 600;">
                        <i class="material-icons left" style="color: #6366f1;">auto_awesome</i>AI Academic Advisor & Chat
                    </a>
                    <a href="profile.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">person</i>View My Profile
                    </a>
                    <a href="modules.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">book</i>My Enrolled Modules
                    </a>
                    <a href="results.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">assessment</i>Academic Results
                    </a>
                    <a href="registration_slip.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">receipt</i>Registration Slip
                    </a>
                    <a href="profile_report.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">description</i>Profile Summary
                    </a>
                    <a href="<?php echo BASE_URL; ?>change_password.php" class="collection-item waves-effect blue-text text-darken-2">
                        <i class="material-icons left">vpn_key</i>Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Snapshot -->
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title" style="font-weight: 600;">
                    <i class="material-icons left orange-text text-darken-3">stars</i>
                    Academic Snapshot
                </span>
                <?php if (!empty($moduleResults)): ?>
                    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                        <table class="dense-table striped" style="margin-top: 10px; font-size: 0.88rem;">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Credits</th>
                                    <th>Average</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moduleResults as $res): ?>
                                    <?php 
                                        $modRes = new ModuleResult($res);
                                        $grade = $modRes->getGradeStatus();
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($res['module_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($res['credits']); ?></td>
                                        <td><strong><?php echo $res['average'] !== null ? number_format($res['average'], 2) . '%' : 'Pending'; ?></strong></td>
                                        <td><span class="chip tiny <?php echo $grade['color']; ?> white-text"><?php echo $grade['status']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; text-align: right;">
                        <a href="results.php" class="btn-small blue darken-2 waves-effect" style="border-radius: 6px;">View Full Marks</a>
                    </div>
                <?php else: ?>
                    <p class="grey-text" style="margin-top: 20px;">No academic results recorded yet for the current academic session.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
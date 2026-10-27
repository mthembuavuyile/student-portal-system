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
        <div class="card hoverable z-depth-2" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); color: white; border-radius: 14px; overflow: hidden; position: relative;">
            <div style="position: absolute; top: -50%; right: -30%; width: 60%; height: 200%; background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%); pointer-events: none;"></div>
            <div class="card-content" style="padding: 24px; position: relative; z-index: 1;">
                <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap;">
                    <div class="col s12 m8">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: white;">
                                <i class="material-icons" style="font-size: 14px;">auto_awesome</i>Smart AI Engine
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.8);">OpenRouter Powered</span>
                        </div>
                        <h5 style="margin: 4px 0 8px 0; font-weight: 700; font-size: 1.25rem;">Personalized AI Performance Advisor & Study Coach</h5>
                        <p style="color: rgba(255,255,255,0.7); font-size: 0.88rem; margin: 0; line-height: 1.5;">
                            Get instant diagnostic feedback on your quarterly marks! Find out which modules need intervention, receive custom revision timetables, and chat live with an academic coach.
                        </p>
                    </div>
                    <div class="col s12 m4" style="text-align: right; margin-top: 15px;">
                        <a href="results.php#aiCoachCard" class="btn-large waves-effect waves-light z-depth-2" style="border-radius: 10px; font-weight: 700; background: linear-gradient(135deg, #6366f1, #4f46e5); text-transform: none; letter-spacing: 0.2px; box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);">
                            <i class="material-icons left">psychology</i>Analyze Performance
                        </a>
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
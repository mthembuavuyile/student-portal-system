<?php
$page_title = "Lecturer Dashboard";
$allowed_roles = ['lecturer'];
include '../includes/header.php';

// Fetch lecturer details
$lecturerId = $_SESSION['lecturer_id'] ?? null;
$lecturer = null;
$modules = [];
$students = [];
$results = [];
$detailedStudents = [];

try {
    if ($lecturerId) {
        $lecturer = Lecturer::findById($pdo, (int)$lecturerId);
    }

    if (!$lecturer && isset($_SESSION['user_id'])) {
        // Fallback: look up by username
        $user = User::findById($pdo, (int)$_SESSION['user_id']);
        if ($user && $user->getLecturerId()) {
            $lecturer = Lecturer::findById($pdo, $user->getLecturerId());
        }
    }

    if (!$lecturer) {
        echo '<div class="card-panel orange darken-2 white-text" style="border-radius: 8px; margin-top: 25px; padding: 24px;">
                <h5 style="margin-top: 0;"><i class="material-icons left">account_circle</i>Lecturer Profile Unlinked</h5>
                <p style="font-size: 1.05rem;">This login account is not linked to an educator record in the <code>lecturers</code> table (Lecturer ID missing).</p>
                <p>Please run the database sync SQL in InfinityFree phpMyAdmin to link this account.</p>
              </div>';
        include '../includes/footer.php';
        exit();
    }

    $modules = $lecturer->getModules($pdo);
    $students = $lecturer->getStudents($pdo);
    $results = $lecturer->getModuleResults($pdo);
    $detailedStudents = $lecturer->getStudentsDetailed($pdo);

} catch (Throwable $e) {
    echo '<div class="card-panel red darken-2 white-text z-depth-2" style="border-radius: 8px; margin-top: 25px; padding: 24px;">
            <h5 style="margin-top: 0;"><i class="material-icons left">storage</i>Database Configuration Required</h5>
            <p style="font-size: 1.05rem;"><strong>Error Detail:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p>The Educator / Lecturer portal requires the <code>lecturers</code> and <code>module_lecturers</code> tables to exist in the database.</p>
            <p>Please open <strong>phpMyAdmin</strong> on InfinityFree and execute the database migration script to create the missing tables.</p>
          </div>';
    include '../includes/footer.php';
    exit();
}

// Calculate metrics
$moduleCount = count($modules);
$studentCount = count($students);
$resultsCount = count($results);

$passedCount = 0;
$totalAverageSum = 0;
foreach ($results as $r) {
    if ($r['average'] !== null) {
        $avg = (float)$r['average'];
        $totalAverageSum += $avg;
        if ($avg >= 50) {
            $passedCount++;
        }
    }
}
$overallPassRate = $resultsCount > 0 ? round(($passedCount / $resultsCount) * 100, 1) : 0;
$classAverage = $resultsCount > 0 ? round($totalAverageSum / $resultsCount, 1) : 0;
?>

<div class="row" style="margin-top: 20px;">
    <div class="col s12">
        <div class="card indigo darken-3 white-text z-depth-2" style="border-radius: 8px;">
            <div class="card-content" style="padding: 24px;">
                <div class="row valign-wrapper" style="margin-bottom: 0; flex-wrap: wrap;">
                    <div class="col s12 m8">
                        <span class="chip indigo lighten-4 indigo-text text-darken-4 font-weight-bold">
                            <i class="material-icons left">verified_user</i>Educator Faculty Access
                        </span>
                        <h4 style="margin: 10px 0 5px 0; font-weight: 600;">Welcome, <?php echo htmlspecialchars($lecturer->getFullName()); ?></h4>
                        <p class="indigo-text text-lighten-3">
                            <i class="material-icons tiny">email</i> <?php echo htmlspecialchars($lecturer->getEmail()); ?> &nbsp;|&nbsp; 
                            <i class="material-icons tiny">badge</i> Staff ID: LEC-<?php echo str_pad($lecturer->getLecturerId(), 4, '0', STR_PAD_LEFT); ?>
                        </p>
                    </div>
                    <div class="col s12 m6 right-align" style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                        <a href="students.php" class="btn-large waves-effect waves-light indigo darken-1 z-depth-2" style="border-radius: 6px; font-weight: 600;">
                            <i class="material-icons left">people</i>My Students
                        </a>
                        <a href="results.php" class="btn-large waves-effect waves-light amber darken-3 z-depth-2" style="border-radius: 6px; font-weight: 600;">
                            <i class="material-icons left">edit_note</i>Assign Marks
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php display_session_message('success_message'); ?>
        <?php display_session_message('error_message', 'red'); ?>
    </div>
</div>

<!-- Metric Stat Cards -->
<div class="row">
    <div class="col s12 m6 l3">
        <a href="modules.php" style="text-decoration: none; color: inherit;">
            <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #283593; border-radius: 6px;">
                <div class="row valign-wrapper" style="margin-bottom: 0;">
                    <div class="col s3">
                        <i class="material-icons medium indigo-text text-darken-3">book</i>
                    </div>
                    <div class="col s9 right-align">
                        <h4 style="margin: 0; font-weight: 700; color: #283593;"><?php echo $moduleCount; ?></h4>
                        <span class="grey-text text-darken-1">My Modules</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col s12 m6 l3">
        <a href="students.php" style="text-decoration: none; color: inherit;">
            <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #00897b; border-radius: 6px;">
                <div class="row valign-wrapper" style="margin-bottom: 0;">
                    <div class="col s3">
                        <i class="material-icons medium teal-text text-darken-1">people</i>
                    </div>
                    <div class="col s9 right-align">
                        <h4 style="margin: 0; font-weight: 700; color: #00897b;"><?php echo $studentCount; ?></h4>
                        <span class="grey-text text-darken-1">Active Students</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col s12 m6 l3">
        <a href="results.php" style="text-decoration: none; color: inherit;">
            <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #ef6c00; border-radius: 6px;">
                <div class="row valign-wrapper" style="margin-bottom: 0;">
                    <div class="col s3">
                        <i class="material-icons medium orange-text text-darken-3">assessment</i>
                    </div>
                    <div class="col s9 right-align">
                        <h4 style="margin: 0; font-weight: 700; color: #ef6c00;"><?php echo $resultsCount; ?></h4>
                        <span class="grey-text text-darken-1">Marks Recorded</span>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col s12 m6 l3">
        <div class="card-panel white z-depth-1 hoverable" style="border-left: 5px solid #2e7d32; border-radius: 6px;">
            <div class="row valign-wrapper" style="margin-bottom: 0;">
                <div class="col s3">
                    <i class="material-icons medium green-text text-darken-3">trending_up</i>
                </div>
                <div class="col s9 right-align">
                    <h4 style="margin: 0; font-weight: 700; color: #2e7d32;"><?php echo $overallPassRate; ?>%</h4>
                    <span class="grey-text text-darken-1">Pass Rate</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modules Roster -->
<div class="row">
    <div class="col s12 l7">
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span class="card-title" style="font-weight: 600; margin: 0;">
                        <i class="material-icons left indigo-text">view_list</i>Assigned Teaching Modules
                    </span>
                    <a href="modules.php" class="btn-flat waves-effect indigo-text">View All <i class="material-icons right tiny">arrow_forward</i></a>
                </div>

                <?php if (!empty($modules)): ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Module Name</th>
                                <th>Role</th>
                                <th>Credits</th>
                                <th class="center-align">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $m): ?>
                                <tr>
                                    <td>
                                        <span class="chip indigo lighten-5 indigo-text font-weight-bold">
                                            <?php echo htmlspecialchars($m['module_code']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($m['module_name']); ?></strong></td>
                                    <td>
                                        <?php if ($m['is_primary']): ?>
                                            <span class="badge blue white-text" style="float:none;">Primary</span>
                                        <?php else: ?>
                                            <span class="badge grey white-text" style="float:none;">Co-Lecturer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['credits']); ?> Cr</td>
                                    <td class="center-align">
                                        <a href="results.php?module_id=<?php echo $m['module_id']; ?>" class="btn-small waves-effect waves-light amber darken-3" title="Record Marks for this module">
                                            <i class="material-icons left tiny">edit</i>Marks
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="grey-text center-align" style="padding: 20px;">No modules currently assigned to your profile. Please contact the administrator to assign curriculum modules.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Educator Notice & Guidelines -->
    <div class="col s12 l5">
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <span class="card-title" style="font-weight: 600;">
                    <i class="material-icons left orange-text text-darken-3">help_outline</i>Quarterly Grading Rules
                </span>
                <ul class="collection" style="border: none;">
                    <li class="collection-item avatar" style="border-bottom: 1px solid #f0f0f0;">
                        <i class="material-icons circle blue">looks_one</i>
                        <span class="title"><strong>Quarterly Breakdown</strong></span>
                        <p class="grey-text text-darken-1 font-size-sm">Marks are captured for Q1, Q2, Q3, and Q4 (each contributing 25% weight). Permitted range is 0 - 100.</p>
                    </li>
                    <li class="collection-item avatar" style="border-bottom: 1px solid #f0f0f0;">
                        <i class="material-icons circle green">functions</i>
                        <span class="title"><strong>Automated Running Average</strong></span>
                        <p class="grey-text text-darken-1 font-size-sm">Average is automatically calculated dynamically whenever marks are entered or updated.</p>
                    </li>
                    <li class="collection-item avatar">
                        <i class="material-icons circle amber darken-2">lock</i>
                        <span class="title"><strong>Educator Responsibility</strong></span>
                        <p class="grey-text text-darken-1 font-size-sm">Educators have direct rights to record, audit, and update academic outcomes for their assigned modules.</p>
                    </li>
                </ul>
                <div class="center-align" style="margin-top: 10px;">
                    <a href="results.php" class="btn waves-effect waves-light indigo darken-3" style="width: 100%;">
                        <i class="material-icons left">edit</i>Open Marksheet
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Enrolled Students Section -->
<div class="row">
    <div class="col s12">
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="card-title" style="font-weight: 600; margin: 0;">
                            <i class="material-icons left indigo-text">people</i>My Enrolled Students
                        </span>
                        <p class="grey-text" style="margin: 4px 0 0 0;">Students enrolled in curricula containing your assigned modules.</p>
                    </div>
                    <a href="students.php" class="btn-small waves-effect waves-light indigo darken-3">
                        <i class="material-icons left">groups</i>Manage All Students (<?php echo count($detailedStudents); ?>)
                    </a>
                </div>

                <?php if (!empty($detailedStudents)): ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Student Number</th>
                                <th>Course</th>
                                <th>Assigned Module</th>
                                <th>Latest Average</th>
                                <th>Marks Status</th>
                                <th class="center-align">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $dashboardStudents = array_slice($detailedStudents, 0, 6);
                            foreach ($dashboardStudents as $dst): 
                                $hasMark = ($dst['average'] !== null);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dst['full_name']); ?></strong></td>
                                    <td><span class="chip tiny grey lighten-3"><code><?php echo htmlspecialchars($dst['student_number'] ?: $dst['student_id_number']); ?></code></span></td>
                                    <td><small class="grey-text text-darken-2 font-weight-bold"><?php echo htmlspecialchars($dst['course_name']); ?></small></td>
                                    <td>
                                        <span class="chip tiny teal lighten-5 teal-text text-darken-3 font-weight-bold">
                                            <?php echo htmlspecialchars($dst['module_code']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #1565c0;">
                                            <?php echo $hasMark ? number_format($dst['average'], 2) . '%' : '<span class="grey-text">Pending</span>'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($hasMark): ?>
                                            <span class="chip green lighten-4 green-text text-darken-3 tiny">Recorded</span>
                                        <?php else: ?>
                                            <span class="chip orange lighten-4 orange-text text-darken-3 tiny">Awaiting Marks</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center-align" style="white-space: nowrap;">
                                        <a href="results.php?module_id=<?php echo $dst['module_id']; ?>&student_id=<?php echo $dst['student_id']; ?>" 
                                           class="btn-small waves-effect waves-light amber darken-3" 
                                           title="Assign Marks">
                                            <i class="material-icons tiny left">edit</i>Marks
                                        </a>
                                        <a href="student_profile.php?id=<?php echo $dst['student_id']; ?>" 
                                           class="btn-small waves-effect waves-light indigo darken-3" 
                                           title="View Profile">
                                            <i class="material-icons tiny">person</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="grey-text center-align" style="padding: 20px;">No students currently enrolled in courses offering your modules.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Marks Captured -->
<div class="row">
    <div class="col s12">
        <div class="card z-depth-1" style="border-radius: 8px;">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span class="card-title" style="font-weight: 600; margin: 0;">
                        <i class="material-icons left teal-text">assessment</i>Recent Student Marks Captured
                    </span>
                    <a href="results.php" class="btn-small waves-effect waves-light teal darken-2">
                        <i class="material-icons left">list</i>Full Marksheet
                    </a>
                </div>

                <?php if (!empty($results)): ?>
                    <table class="highlight responsive-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student Number</th>
                                <th>Module</th>
                                <th>Year</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Calculated Average</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent = array_slice($results, 0, 8);
                            foreach ($recent as $r): 
                                $modRes = new ModuleResult($r);
                                $grade = $modRes->getGradeStatus();
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['full_name']); ?></strong></td>
                                    <td><span class="chip tiny grey lighten-3"><?php echo htmlspecialchars($r['student_number'] ?: $r['student_id_number']); ?></span></td>
                                    <td>
                                        <span class="chip tiny teal lighten-5 teal-text font-weight-bold">
                                            <?php echo htmlspecialchars($r['module_code']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['academic_year']); ?></td>
                                    <td><?php echo $r['quarter1'] !== null ? number_format($r['quarter1'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $r['quarter2'] !== null ? number_format($r['quarter2'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $r['quarter3'] !== null ? number_format($r['quarter3'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td><?php echo $r['quarter4'] !== null ? number_format($r['quarter4'], 1) . '%' : '<span class="grey-text">-</span>'; ?></td>
                                    <td>
                                        <strong style="color: #1565c0; font-size: 1.05rem;">
                                            <?php echo $r['average'] !== null ? number_format($r['average'], 2) . '%' : 'Pending'; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="chip <?php echo $grade['color']; ?> white-text">
                                            <?php echo $grade['grade']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="grey-text center-align" style="padding: 20px;">No marks recorded yet for your assigned modules. Click "Assign Marks" to begin capturing marks.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

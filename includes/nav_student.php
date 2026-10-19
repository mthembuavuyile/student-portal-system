<?php // nav_student.php ?>
<nav class="blue darken-3" role="navigation">
    <div class="nav-wrapper">
        <a href="#" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        <a id="logo-container" href="<?php echo BASE_URL; ?>student/dashboard.php" class="brand-logo">
            <i class="material-icons">school</i>Student Portal
        </a>
        <ul class="right hide-on-med-and-down">
            <li><a href="<?php echo BASE_URL; ?>student/dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>student/profile.php"><i class="material-icons left">person</i>Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>student/modules.php"><i class="material-icons left">book</i>My Modules</a></li>
            <li><a href="<?php echo BASE_URL; ?>student/results.php"><i class="material-icons left">assessment</i>Academic Results</a></li>
            <li><a href="<?php echo BASE_URL; ?>student/registration_slip.php"><i class="material-icons left">receipt</i>Slip</a></li>
            <li><a href="<?php echo BASE_URL; ?>student/results.php#aiCoachCard" style="color: #ffd54f; font-weight: 600;"><i class="material-icons left" style="color: #ffd54f;">auto_awesome</i>AI Coach</a></li>
            <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons left">lock</i>Password</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
        </ul>
    </div>
</nav>

<ul id="nav-mobile" class="sidenav">
    <li><div class="user-view">
        <div class="background blue darken-2"></div>
        <a href="#user"><i class="material-icons" style="color:white;">account_circle</i></a>
        <a href="#name"><span class="white-text name">Student: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></span></a>
        <a href="#email"><span class="white-text email">Department of Forestry, Fisheries & the Environment</span></a>
    </div></li>
    <li><a href="<?php echo BASE_URL; ?>student/dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/results.php#aiCoachCard"><i class="material-icons amber-text text-darken-3">auto_awesome</i>AI Academic Coach</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/profile.php"><i class="material-icons">person</i>My Profile</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/modules.php"><i class="material-icons">book</i>My Enrolled Modules</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/results.php"><i class="material-icons">assessment</i>Quarterly Marks & Results</a></li>
    <li><div class="divider"></div></li>
    <li><a class="subheader">Official Documents</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/profile_report.php"><i class="material-icons">description</i>Profile Summary Report</a></li>
    <li><a href="<?php echo BASE_URL; ?>student/registration_slip.php"><i class="material-icons">receipt</i>Registration Slip</a></li>
    <li><div class="divider"></div></li>
    <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons">lock</i>Change Password</a></li>
    <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons">exit_to_app</i>Logout</a></li>
</ul>
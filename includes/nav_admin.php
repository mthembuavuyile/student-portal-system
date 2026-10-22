<?php // nav_admin.php ?>
<nav class="blue darken-3" role="navigation">
    <div class="nav-wrapper">
        <a href="#" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        <a id="logo-container" href="<?php echo BASE_URL; ?>admin/dashboard.php" class="brand-logo">
            <i class="material-icons">account_balance</i>SMS Admin
        </a>
        <ul class="right hide-on-med-and-down">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/student_list.php"><i class="material-icons left">people</i>Students</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/courses.php"><i class="material-icons left">school</i>Courses</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/modules.php"><i class="material-icons left">book</i>Modules</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/lecturers.php"><i class="material-icons left">supervisor_account</i>Lecturers</a></li>
            <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons left">lock</i>Password</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
        </ul>
    </div>
</nav>

<ul id="nav-mobile" class="sidenav">
    <li><div class="user-view">
        <div class="background blue darken-2"></div>
        <a href="#user"><i class="material-icons" style="color:white;">account_circle</i></a>
        <a href="#name"><span class="white-text name">Admin: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></span></a>
        <a href="#email"><span class="white-text email">Corporate Management Services</span></a>
    </div></li>
    <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/register_student.php"><i class="material-icons">person_add</i>Register Student</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/student_list.php"><i class="material-icons">people</i>Student Directory</a></li>
    <li><div class="divider"></div></li>
    <li><a class="subheader">Curriculum & Staff</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/courses.php"><i class="material-icons">school</i>Courses</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/modules.php"><i class="material-icons">book</i>Modules</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/lecturers.php"><i class="material-icons">supervisor_account</i>Lecturers</a></li>
    <li><div class="divider"></div></li>
    <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons">lock</i>Change Password</a></li>
    <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons">exit_to_app</i>Logout</a></li>
</ul>
<?php // nav_lecturer.php ?>
<nav class="indigo darken-3" role="navigation">
    <div class="nav-wrapper">
        <a href="#" data-target="nav-mobile" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        <a id="logo-container" href="<?php echo BASE_URL; ?>lecturer/dashboard.php" class="brand-logo">
            <i class="material-icons">menu_book</i>Lecturer Portal
        </a>
        <ul class="right hide-on-med-and-down">
            <li><a href="<?php echo BASE_URL; ?>lecturer/dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>lecturer/students.php"><i class="material-icons left">people</i>My Students</a></li>
            <li><a href="<?php echo BASE_URL; ?>lecturer/modules.php"><i class="material-icons left">book</i>My Modules</a></li>
            <li><a href="<?php echo BASE_URL; ?>lecturer/results.php"><i class="material-icons left">edit_note</i>Assign Marks</a></li>
            <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons left">lock</i>Password</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
        </ul>
    </div>
</nav>

<ul id="nav-mobile" class="sidenav">
    <li><div class="user-view">
        <div class="background indigo darken-2"></div>
        <a href="#user"><i class="material-icons" style="color:white;">account_circle</i></a>
        <a href="#name"><span class="white-text name">Educator: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Lecturer'); ?></span></a>
        <a href="#email"><span class="white-text email">Academic Faculty & Mark Assessment</span></a>
    </div></li>
    <li><a href="<?php echo BASE_URL; ?>lecturer/dashboard.php"><i class="material-icons">dashboard</i>Dashboard</a></li>
    <li><a href="<?php echo BASE_URL; ?>lecturer/students.php"><i class="material-icons">people</i>My Students</a></li>
    <li><a href="<?php echo BASE_URL; ?>lecturer/modules.php"><i class="material-icons">book</i>My Assigned Modules</a></li>
    <li><a href="<?php echo BASE_URL; ?>lecturer/results.php"><i class="material-icons">edit_note</i>Assign & Record Marks</a></li>
    <li><div class="divider"></div></li>
    <li><a href="<?php echo BASE_URL; ?>change_password.php"><i class="material-icons">lock</i>Change Password</a></li>
    <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="material-icons">exit_to_app</i>Logout</a></li>
</ul>

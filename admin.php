<?php
session_start();
?>
<!-- // test change 1 -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<header class="site-header">
    <nav>
        <ul class="nav-list">
            <li><a href="college.php">College</a></li>
            <li><a href="schedule.php">Schedule</a></li>
            <li><a href="orientation.php">Orientation</a></li>
            <li><a href="assessment.php">Assessment</a></li>
            <li><a href="udas_assessment.php">UDAS Assessment</a></li>
            <li><a href="report.php">Report</a></li>
            <li><a href="registration.php">Registration</a></li>
            <li class="btn"><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>
<div class="admin-content">
    <h1>Welcome to the Admin Panel</h1>
    <p>Select an option from the navigation bar to manage different sections.</p>
</div>
</body>
</html>

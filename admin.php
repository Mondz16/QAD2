<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>

        <div class="container">
            <div class="header">
                 <div class="headerLeft">
                    <div class=USePData>
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                    <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
                            </div>
                                <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>
                <div class="headerRight">
                    <a class="btn" href="logout.php">Log Out</a>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="admin-content">
            <h1>Welcome to the Admin Panel</h1>
            <p>Select an option from the navigation bar to manage different sections.</p>
        </div>
        <nav>
            <ul class="nav-list">
                <li><a href="college.php">College</a></li>
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="orientation.php">Orientation</a></li>
                <li><a href="assessment.php">Assessment</a></li>
                <li><a href="udas_assessment.php">UDAS Assessment</a></li>
                <li><a href="report.php">Report</a></li>
                <li><a href="registration.php">Registration</a></li>
                <li><a href="college_transfer.php">College Transfer</a></li>
            </ul>
        </nav>
    </div>
</body>
</html>

<!-- test admin -->
<?php
session_start();
require 'connection.php'; // Include your database connection file

// Fetch team leaders
$teamLeadersQuery = "SELECT id FROM team WHERE role = 'team leader'";
$teamLeadersResult = $conn->query($teamLeadersQuery);
$teamLeaders = $teamLeadersResult->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
        <link rel="stylesheet" href="css/sidebar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .check-symbol {
            color: green;
            font-size: 24px;
            margin-left: 10px;
        }

        .assessment-box {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            width: 800px;
            height: 350px;
            margin-bottom: 20px;
        }

        .assessment-box h2 {
            font-size: 18px;
            margin-bottom: 10px;
            text-align: end;
        }

        .assessment-college {
            text-align: left;
            font-size: 16px;
            width: 540px;
            margin-right: 10px;
        }

        .assessment-holder-1 {
            display: flex;
            justify-content: space-around;
        }

        .assessment-holder-2 {
            display: flex;
            justify-content: flex-start;
            margin-top: 10px;
        }

        .assessment-dateTime {
            text-align: left;
            width: 265px;
            margin-right: 12px;
        }

        .assessment-dateTime p {
            margin: 0;
        }
        
        .assessment-udas{
            text-align: left;
            width: 200px;
        }

        .assessment-udas .udas-button{
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: white;
            font-weight: bold;
            color: #006118;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button:hover{
            background-color: #D4FFDF;
            border: 1px solid #006118;
            color: #006118;
        }

        .assessment-udas .download-button{
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: #D4FFDF ;
            color: #006118;
            font-weight: bold;
            border: 1px solid #006118;
        }

        .assessment-level-applied p {
            margin-bottom: 10px;
            text-align: left;
        }

        .assessment-level-applied h3 {
            width: 200px;
            height: 140px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #FFF1C0;
            border-radius: 10px;
            font-size: 5rem;
            font-weight: bold;
            border: 1px solid #E6A33E;
        }

        .assessment-college p {
            margin: 0px;
        }

        .assessment-values {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin: 10px 0px;
            font-weight: bold;
        }

        .scrollable-container {
            max-height: 500px;
            max-width: 1200px;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            justify-content: center;
            margin-left: 55px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                    </svg>
                </button>
                <div class="sidebar-logo">
                    <a href="#">QAD</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="dashboard.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07"/>
                        </svg>
                        <span style="margin-left: 8px;">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="admin_sidebar.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                        </svg>
                        <span style="margin-left: 8px;">Admin Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="schedule.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-range" viewBox="0 0 16 16">
                            <path d="M9 7a1 1 0 0 1 1-1h5v2h-5a1 1 0 0 1-1-1M1 9h4a1 1 0 0 1 0 2H1z" />
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                        </svg>
                        <span style="margin-left: 8px;">Schedule</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="college.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mortarboard" viewBox="0 0 16 16">
                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.916l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.916zM8 8.46 1.758 5.965 8 3.052l6.242 2.913z" />
                            <path d="M4.166 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466zm-.068 1.873.22-.748 3.496 1.311a.5.5 0 0 0 .352 0l3.496-1.311.22.748L8 12.46z" />
                        </svg>
                        <span style="margin-left: 8px;">College</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="orientation.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-square-text" viewBox="0 0 16 16">
                            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                            <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                        </svg>
                        <span style="margin-left: 8px;">Orientation</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-check" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3.854 2.146a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 3.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 7.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0" />
                        </svg>
                        <span style="margin-left: 8px;">Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="udas_assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2-check" viewBox="0 0 16 16">
                            <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z" />
                            <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z" />
                            <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z" />
                        </svg>
                        <span style="margin-left: 8px;">UDAS Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="registration.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.716 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                        </svg>
                        <span style="margin-left: 8px;">Register Verification</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="college_transfer.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5"/>
                        </svg>
                        <span style="margin-left: 8px;">College Transfer</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                            <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z" />
                        </svg>
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer p-1">
                <a href="login.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z" />
                        <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z" />
                    </svg>
                    <span style="margin-left: 8px;">Logout</span>
                </a>
            </div>
        </aside>
        <div class="main">
            <div class="row top-bar"></div>
            <div class="row header">
                <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                    <img src="images/USePLogo.png" alt="USeP Logo">
                </div>
                <div class="col-6 col-md-4 d-flex align-items-start">
                    <div class="vertical-line"></div>
                    <div class="divider"></div>
                    <div class="text">
                        <span class="one">One</span>
                        <span class="datausep">Data.</span>
                        <span class="one">One</span>
                        <span class="datausep">USeP.</span><br>
                        <span>Quality Assurance Division</span>
                    </div>
                </div>
                <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
                </div>
                <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
                </div>
            </div>
            <div class="container text-center mt-4">
                <h1 class="mt-5 mb-5">ASSESSMENTS</h1>
                <div class="scrollable-container">

                    <?php
                    if (count($teamLeaders) > 0) {
                        $counter = 1; // Counter for numbering assessments
                        foreach ($teamLeaders as $leader) {
                            $teamLeaderId = $leader['id'];

                            // Fetch summaries for the team leader
                            $summariesQuery = "SELECT id, summary_file, team_id FROM summary WHERE team_id = '$teamLeaderId'";
                            $summariesResult = $conn->query($summariesQuery);
                            $summaries = $summariesResult->fetch_all(MYSQLI_ASSOC);

                            if (count($summaries) > 0) {
                                foreach ($summaries as $summary) {
                                    $teamId = $summary['team_id'];
                                    $summaryFile = $summary['summary_file'];
                                    $summaryId = $summary['id'];

                                    // Fetch schedule details for the team
                                    $scheduleQuery = "
                                SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
                                       c.college_name, p.program_name
                                FROM schedule s
                                JOIN college c ON s.college_code = c.code
                                JOIN program p ON s.program_id = p.id
                                WHERE s.id = (
                                    SELECT schedule_id FROM team WHERE id = '$teamId'
                                )
                            ";
                                    $scheduleResult = $conn->query($scheduleQuery);
                                    $schedule = $scheduleResult->fetch_assoc();

                                    if ($schedule) {
                                        // Check if the summary has been approved
                                        $approvedQuery = "SELECT id FROM approved_summary WHERE summary_id = '$summaryId'";
                                        $approvedResult = $conn->query($approvedQuery);
                                        $isApproved = $approvedResult->num_rows > 0;
                                        $scheduleDate = date("F j, Y", strtotime($schedule['schedule_date']));
                                        $scheduleTime = date("g:i A", strtotime($schedule['schedule_time']));

                                        echo "<div class='assessment-box'>";
                                        echo "<h2>#" . $counter . "</h2>";
                                        echo "<div class='assessment-details'>";
                                        echo "<div class='assessment-holder-1'><div class='assessment-college'><p>College: <br><div class='assessment-values'>" . $schedule['college_name'] . "</div>Program:<br> <div class='assessment-values'>" . $schedule['program_name'] . "</div></div> <div class='assessment-level-applied'><p> Level Applied: <br><h3>" . $schedule['level_applied'] . "</h3></div></p></div>";
                                        echo "<div class='assessment-holder-2'><div class='assessment-dateTime'><p>Date:<br><div class='assessment-values'>" . $scheduleDate . "</div> </div><div class='assessment-dateTime'><p>Time: <br><div class='assessment-values'>" . $scheduleTime . "</div></div></br></p>";

                                        if (!$isApproved) {
                                            echo "<div class='assessment-udas'><p>Download Summary File:<br><button href='" . $summaryFile . "' class='btn approve-btn download-button'>DOWNLOAD</button></div> </div>";
                                        } else {
                                            echo "<div class='assessment-udas'><p>Summary File:<br><button class='btn approve-btn udas-button' data-summary-file='$summaryFile'>Approve Summary</button></div> </div>";
                                        }

                                        echo "</div></div>";
                                        echo "</div>";

                                        $counter++; // Increment counter for next assessment
                                    }
                                }
                            }
                        }
                    } else {
                        echo "<p>No team leaders found.</p>";
                    }
                    ?>

                </div>
            </div>
        </div>


        <!-- Modal -->
        <div id="approvalModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Approve Summary</h2>
                <form id="approveForm" method="POST" action="approve_summary.php" enctype="multipart/form-data">
                    <label for="qadOfficerName">QAD Officer Name:</label>
                    <input type="text" id="qadOfficerName" name="qadOfficerName" required>
                    <label for="qadOfficerSignature">QAD Officer Signature (PNG only):</label>
                    <input type="file" id="qadOfficerSignature" name="qadOfficerSignature" accept="image/png" required>
                    <input type="hidden" id="summaryFile" name="summaryFile">
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
        <script>
            const hamBurger = document.querySelector(".toggle-btn");

            hamBurger.addEventListener("click", function () {
                document.querySelector("#sidebar").classList.toggle("expand");
            });

            // Get modal element
            var modal = document.getElementById("approvalModal");

            // Get the <span> element that closes the modal
            var span = document.getElementsByClassName("close")[0];

            // Get all approve buttons
            var approveBtns = document.getElementsByClassName("approve-btn");

            // Loop through approve buttons to add click event
            for (var i = 0; i < approveBtns.length; i++) {
                approveBtns[i].addEventListener("click", function() {
                    var summaryFile = this.getAttribute("data-summary-file");

                    document.getElementById("summaryFile").value = summaryFile;

                    modal.style.display = "block";
                });
            }

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = "none";
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
</body>

</html>
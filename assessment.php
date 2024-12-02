<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve full name of the logged-in user from the admin table without the prefix
$sql = "SELECT CONCAT(first_name, ' ', middle_initial, '. ', last_name) AS full_name FROM admin WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();

$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    $is_admin = true;
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'assessment.php') {
        header("Location: assessment.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal.php') {
            header("Location: internal.php");
            exit();
        }
    } elseif ($user_type_code === '22') {
        // External user
        if (basename($_SERVER['PHP_SELF']) !== 'external.php') {
            header("Location: external.php");
            exit();
        }
    } else {
        // Handle unexpected user type, redirect to login or error page
        header("Location: login.php");
        exit();
    }
}
$hasOpenAssessment = true;
$assessments = [];

// Fetch team leaders
$teamLeadersQuery = "
    SELECT t.id AS team_id, t.schedule_id, 
           CONCAT(iu.first_name, ' ', iu.middle_initial, '. ', iu.last_name) AS team_leader_name
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    WHERE t.role = 'team leader'
";
$teamLeadersResult = $conn->query($teamLeadersQuery);
$teamLeaders = $teamLeadersResult->fetch_all(MYSQLI_ASSOC);

if (count($teamLeaders) > 0) {
    $counter = 1; // Counter for numbering assessments
    foreach ($teamLeaders as $leader) {
        $teamId = $leader['team_id'];
        $scheduleId = $leader['schedule_id'];

        // Fetch schedule details
        $scheduleQuery = "
            SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
                   c.college_name, p.program_name
            FROM schedule s
            JOIN college c ON s.college_code = c.code
            JOIN program p ON s.program_id = p.id
            WHERE s.id = '$scheduleId' 
              AND (s.schedule_status = 'approved' OR s.schedule_status = 'pending')";
        $scheduleResult = $conn->query($scheduleQuery);
        $schedule = $scheduleResult->fetch_assoc();

        if ($schedule) {
            // Fetch team members
            $teamMembersQuery = "
                SELECT 
                    t.id AS team_id, 
                    CONCAT(iu.first_name, ' ', iu.middle_initial, '. ', iu.last_name) AS member_name
                FROM team t
                JOIN internal_users iu ON t.internal_users_id = iu.user_id
                WHERE t.schedule_id = '$scheduleId'
            ";
            $teamMembersResult = $conn->query($teamMembersQuery);
            $teamMembers = $teamMembersResult->fetch_all(MYSQLI_ASSOC);

            // Initialize arrays to collect NDA data
            $ndaIndividualFiles = [];
            $ndaInternalAccreditors = [];

            // Fetch NDA details for each team member's team_id
            foreach ($teamMembers as $teamMember) {
                $teamMemberId = $teamMember['team_id'];
                $ndaTeamMemberQuery = "
                    SELECT internal_accreditor, NDA_file 
                    FROM nda 
                    WHERE team_id = '$teamMemberId'
                ";
                $ndaTeamMemberResult = $conn->query($ndaTeamMemberQuery);
                $ndaTeamMembers = $ndaTeamMemberResult->fetch_all(MYSQLI_ASSOC);

                // Append NDA details
                $ndaIndividualFiles = array_merge($ndaIndividualFiles, array_column($ndaTeamMembers, 'NDA_file'));
                $ndaInternalAccreditors = array_merge($ndaInternalAccreditors, array_column($ndaTeamMembers, 'internal_accreditor'));
            }

            // Fetch NDA Compilation
            $ndaQuery = "SELECT NDA_compilation_file FROM nda_compilation WHERE team_id = '$teamId'";
            $ndaResult = $conn->query($ndaQuery);
            $ndaFile = $ndaResult->fetch_assoc();

            // Check if there's a summary for this team
            $summaryQuery = "SELECT id, summary_file FROM summary WHERE team_id = '$teamId'";
            $summaryResult = $conn->query($summaryQuery);
            $summary = $summaryResult->fetch_assoc();

            // Prepare assessment details
            $assessments[] = [
                'counter' => $counter++,
                'college_name' => $schedule['college_name'],
                'program_name' => $schedule['program_name'],
                'level_applied' => $schedule['level_applied'],
                'schedule_date' => date("F j, Y", strtotime($schedule['schedule_date'])),
                'schedule_time' => date("g:i A", strtotime($schedule['schedule_time'])),
                'summary_file' => $summary['summary_file'] ?? null,
                'nda_compilation_file' => $ndaFile['NDA_compilation_file'] ?? null,
                'nda_individual_files' => $ndaIndividualFiles,
                'nda_internal_accreditors' => $ndaInternalAccreditors,
                'team_leader' => $leader['team_leader_name'],
                'team_members' => array_column($teamMembers, 'member_name'),
                'is_approved' => $summary ? (bool)$conn->query("SELECT id FROM approved_summary WHERE summary_id = '{$summary['id']}'")->num_rows : false
            ];
        }
    }
}

// Query to count pending internal users
$sqlInternalPendingCount = "
    SELECT COUNT(*) AS internal_pending_count
    FROM internal_users i
    LEFT JOIN college c ON i.college_code = c.code
    WHERE i.status = 'pending' AND i.otp = 'verified'
";
$internalResult = $conn->query($sqlInternalPendingCount);
$internalPendingCount = $internalResult->fetch_assoc()['internal_pending_count'] ?? 0;

// Query to count pending external users
$sqlExternalPendingCount = "
    SELECT COUNT(*) AS external_pending_count
    FROM external_users e
    LEFT JOIN company c ON e.company_code = c.code
    WHERE e.status = 'pending'
";
$externalResult = $conn->query($sqlExternalPendingCount);
$externalPendingCount = $externalResult->fetch_assoc()['external_pending_count'] ?? 0;

// SQL query to count unique transfer requests based on bb-cccc part of user_id
$sqlTransferRequestCount = "
    SELECT COUNT(DISTINCT bb_cccc) AS transfer_request_count
    FROM (
        SELECT SUBSTRING(user_id, 4) AS bb_cccc
        FROM internal_users
        GROUP BY bb_cccc
        HAVING COUNT(*) > 1
    ) AS transfer_groups
";
$Tresult = $conn->query($sqlTransferRequestCount);
$transferRequestCount = $Tresult->fetch_assoc()['transfer_request_count'] ?? 0;

// Total pending users count
$totalPendingUsers = $internalPendingCount + $externalPendingCount - $transferRequestCount;

$sqlPendingSchedulesCount = "
    SELECT COUNT(*) AS total_pending_schedules
    FROM schedule s
    WHERE s.schedule_status ='pending'
";
$Sresult = $conn->query($sqlPendingSchedulesCount);
$Srow = $Sresult->fetch_assoc();
$totalPendingSchedules = $Srow['total_pending_schedules'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        .button-container {
            display: flex;
            width: 100%;
            margin-top: 20px;
            justify-content: flex-end;
        }

        .nameContainer {
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
            flex: 1;
        }

        .uploadContainer {
            display: flex;
            align-items: center;
            position: relative;
            padding: 12px 20px;
        }

        .upload-text {
            margin-left: auto;
            font-weight: bold;
            color: #575757;
            font-size: 16px;
            cursor: pointer;
        }

        .upload-icon {
            width: 20px;
            height: 20px;
            margin-left: auto;
            cursor: pointer;
        }

        .uploadInput {
            position: absolute;
            opacity: 0;
            right: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            display: block;
            position: fixed;
            z-index: 1;
            left: 50%;
            top: 30%;
            transform: translate(-50%, 0);
            width: 700px;
            height: 300px;
            padding: 20px;
            border-radius: 10px;
        }

        .modal-content .label-holder {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .modal-content .input-holder {
            display: flex;
            align-items: center;
            position: relative;
        }

        .modal-content .input-holder input {
            padding: 12px 20px;
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
        }

        .modal-content .input-holder input:first-child {
            padding-right: 40px;
            flex: 2;
            margin-right: 15px;
        }

        .modal-content .input-holder input:last-child {
            position: absolute;
            opacity: 0;
            right: 0;
            height: 100%;
            cursor: pointer;
            z-index: 2;
            flex: 1;
        }

        .modal-content .input-holder {
            border: none;
        }

        .modal-content h2 {
            text-align: center;
            margin: 20px 20px 40px 20px;
        }

        .check-symbol {
            color: green;
            font-size: 24px;
            margin-left: 10px;
        }

        .assessment-button-done {
            background-color: #76FA97;
            color: #006118;
            border: 1px solid #76FA97;
            font-weight: bold;
            height: 46px;
            width: 100%;
            margin: 10px 0;
            border-radius: 8px;

        }

        .assessment-box {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            width: 800px;
            height: 350px;
            margin-bottom: 30px;
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

        .assessment-udas {
            text-align: left;
            width: 200px;
            margin-left: 12px;
        }

        .assessment-udas .udas-button,
        .udas-button1 {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: #fff;
            font-weight: bold;
            color: #006118;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button {
            background-color: #46C556;
            color: #fff;
        }

        .udas-button1 {
            background-color: #fff;
        }

        .udas-button1 {
            padding-top: 9px;
        }

        .udas-button1:hover {
            background-color: #D4FFDF;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button:hover {
            background-color: #46C556;
            border: 1px solid #006118;
            color: #fff;
        }

        .assessment-udas .download-button {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: #D4FFDF;
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
            color: #575757;
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
            font-size: .95rem;
        }

        .scrollable-container {
            max-height: 650px;
            max-width: 1200px;
            overflow-y: auto;
            overflow-x: hidden;
            display: inline-block;
            margin-left: 30px;
        }

        .scrollable-container-holder {
            display: inline-block;
            width: fit-content;
            padding: 20px 20px 20px 0px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #f9f9f9;
        }

        .custom-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .custom-spinner {
            width: 40px;
            /* Size similar to Bootstrap's default spinner */
            height: 40px;
            /* Size similar to Bootstrap's default spinner */
            border-width: 5px;
            border-style: solid;
            border-radius: 50%;
            border-color: #B73033;
            /* Custom color for the spinner */
            border-right-color: transparent;
            /* Transparent border to create the spinning effect */
            animation: custom-spin 0.75s linear infinite;
            /* Bootstrap-like spinning animation */
        }

        .custom-spinner-hidden {
            display: none;
        }

        /* Custom spin animation similar to Bootstrap */
        @keyframes custom-spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .approve-cancel-button {
            background-color: #f9f9f9;
            border: 1px solid #AFAFAF;
            padding: 10px 25px;
            margin: 0px 5px;
            cursor: pointer;
            border-radius: 8px;
            font-size: 14px;
        }

        .approve-cancel-button:hover {
            background-color: #AFAFAF;
            color: white;
        }

        .approve-assessment-button {
            padding: 10px 25px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            width: 170px;
            background-color: #76FA97;
            color: black;
            border: 1px solid #AFAFAF;
        }

        .approve-assessment-button:hover {
            background-color: #43f770;
            color: black;
        }

        .notification-counter {
            color: #E6A33E;
            /* Text color */
        }

        .nda-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .nda-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 10px 30px 20px 30px;
            border: 1px solid #888;
            width: 80%;
            max-width: 550px;
            position: relative;
            border-radius: 10px;
        }

        .nda-modal-content h3 {
            margin: 20px 0;
        }

        .nda-close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            margin-right: 10px;
        }

        .nda-close-modal:hover,
        .nda-close-modal:focus {
            color: black;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="main">
            <div class="hair" style="height: 15px; background: #9B0303;"></div>
            <div class="container">
                <div class="header">
                    <div class="headerLeft">
                        <div class="USePData">
                            <img class="USeP" src="images/USePLogo.png" height="36">
                            <div style="height: 0px; width: 16px;"></div>
                            <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                            <div style="height: 0px; width: 16px;"></div>
                            <div class="headerLeftText">
                                <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                    <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                        <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                        <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                        <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span>
                                    </h>
                                </div>
                                <h>Accreditor Portal</h>
                            </div>
                        </div>
                    </div>

                    <div class="headerRight">
                        <div class="QAD">
                            <div class="headerRightText">
                                <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                            </div>
                            <div style="height: 0px; width: 16px;"></div>
                            <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                            <div style="height: 0px; width: 16px;"></div>
                            <img class="USeP" src="images/QADLogo.png" height="36">
                        </div>
                    </div>
                </div>
            </div>
            <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
            <nav id="sidebar">
                <ul class="sidebar-nav">
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Schedule</span>
                            <?php if ($totalPendingSchedules > 0): ?>
                                <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="dashboard.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View Schedule</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'schedule.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Add Schedule</span>
                                <?php if ($totalPendingSchedules > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingSchedules; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'orientation.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Orientation</span>
                            </a>
                            <a href="<?php echo $is_admin === false ? 'internal_orientation.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Request Orientation</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">College</span>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="college.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View College</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'add_college.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Add College</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link-active">
                            <span style="margin-left: 8px;">Assessment</span>
                            <?php if (count($assessments) > 0): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">View Assessments</span>
                                <?php if (count($assessments) > 0): ?>
                                    <span class="notification-counter">
                                        <?= count($assessments) ?> <!-- Display the count of assessments -->
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">UDAS Assessments</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Administrative</span>
                            <?php if ($totalPendingUsers > 0 || $transferRequestCount > 0): ?>
                                <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'area.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Area</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'registration.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Register Verification</span>
                                <?php if ($totalPendingUsers > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                                <?php endif; ?>                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>\
                                <?php if ($transferRequestCount > 0): ?>
                                    <span class="notification-counter"><?= $transferRequestCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Reports</span>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin === false ? 'internal_assigned_schedule.php' : 'reports_program_schedule.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;"><?php echo $is_admin === false ? 'View Assigned Schedule' : 'View Program Schedule'; ?></span></a>
                            <a href="reports_dashboard.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View Programs</span></a>
                            <a href="program_timeline.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View Timeline</span></a>
                            <a href="<?php echo $is_admin ? 'reports_member.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Accreditors</span></a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Account</span>
                        </a>

                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'admin_sidebar.php' : 'internal.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">Profile</span>
                            </a>
                            <a href="<?php echo $is_admin === false ? 'internal_notification.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Notifications</span>
                            </a>
                            <a href="logout.php" class="sidebar-link">
                                <span style="margin-left: 8px;">Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <div class="container text-center mt-4">
                <h1 class="mt-5 mb-5">ASSESSMENTS</h1>
                <div class="scrollable-container-holder">
                    <div class="scrollable-container">
                        <?php if (count($assessments) > 0): ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <div class="assessment-box">
                                    <h2>#<?= $assessment['counter']; ?></h2>
                                    <div class="assessment-details">
                                        <div class="assessment-holder-1">
                                            <div class="assessment-college">
                                                <p>COLLEGE:<br>
                                                <div class="assessment-values"><?= $assessment['college_name']; ?></div>
                                                </p>
                                                <p>PROGRAM:<br>
                                                <div class="assessment-values"><?= $assessment['program_name']; ?></div>
                                                </p>
                                            </div>
                                            <div class="assessment-level-applied">
                                                <p>LEVEL APPLIED:<br>
                                                <h3>
                                                    <?= ($assessment['level_applied'] === 'Not Accreditable') ? 'NA' : (($assessment['level_applied'] === 'Candidate') ? 'CAN' :
                                                        $assessment['level_applied']); ?>
                                                </h3>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="assessment-holder-2">
                                            <div class="assessment-dateTime">
                                                <p>DATE:<br>
                                                <div class="assessment-values"><?= $assessment['schedule_date']; ?></div>
                                                </p>
                                            </div>
                                            <div class="assessment-dateTime">
                                                <p>TIME:<br>
                                                <div class="assessment-values"><?= $assessment['schedule_time']; ?></div>
                                                </p>
                                            </div>
                                            <div class="assessment-udas">
                                                <p>DOWNLOADABLE:<br>
                                                    <?php if (!empty($assessment['summary_file'])): ?>
                                                        <a href="<?= $assessment['summary_file']; ?>" class="btn udas-button1" download>SUMMARY</a>
                                                    <?php else: ?>
                                                        <button class="btn udas-button1" disabled>SUMMARY</button>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="assessment-udas">
                                                <p>FILES:<br>
                                                    <button class="btn udas-button1 open-team-details-modal"
                                                        data-team-leader="<?= htmlspecialchars($assessment['team_leader'] ?? ''); ?>"
                                                        data-team-members="<?= htmlspecialchars(implode(',', $assessment['team_members'] ?? [])); ?>"
                                                        data-nda-compilation-file="<?= htmlspecialchars($assessment['nda_compilation_file'] ?? ''); ?>"
                                                        data-nda-individual-files="<?= htmlspecialchars(implode(',', $assessment['nda_individual_files'] ?? [])); ?>"
                                                        data-nda-internal-accreditors="<?= htmlspecialchars(implode(',', $assessment['nda_internal_accreditors'] ?? [])); ?>">
                                                        NDA
                                                    </button>
                                                </p>
                                            </div>
                                            <div class="assessment-udas">
                                                <p>APPROVE:<br>
                                                    <?php if (!empty($assessment['summary_file']) && $assessment['is_approved']): ?>
                                                        <button class="assessment-button-done">APPROVED</button>
                                                    <?php elseif (!empty($assessment['summary_file'])): ?>
                                                        <button class="btn approve-btn udas-button" data-summary-file="<?= $assessment['summary_file']; ?>">APPROVE</button>
                                                    <?php else: ?>
                                                        <button class="btn approve-btn udas-button" disabled>APPROVE</button>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-schedule-prompt">
                                <p>NO ASSESSMENT SUMMARY FOUND</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal HTML -->
        <div id="ndaModal" class="nda-modal">
            <div class="nda-modal-content">
                <span class="nda-close-modal">&times;</span>
                <h3>NDA Submission:</h3>
                <ul id="modal-team-members"></ul>
                <p><strong>Compilation NDA:</strong></p>
                <div id="modal-compilation-nda"></div>
                <p id="no-nda-message" style="display: none;">No NDA file available</p>
            </div>
        </div>


        <!-- Modal -->
        <div id="approvalModal" class="modal">
            <div class="modal-content">
                <h2>Approve Summary</h2>
                <form id="approveForm" method="POST" action="approve_summary.php" enctype="multipart/form-data">
                    <div class="label-holder">
                        <label for="qadOfficerName"><strong>QAD OFFICER NAME:</strong></label>
                        <label for="qadOfficerSignature" style="margin-right: 35px;"><strong>SIGNATURE (PNG ONLY):<span style="color: red;"> *<span></strong></label>
                    </div>
                    <div class="input-holder">
                        <input type="text" id="qadOfficerName" name="qadOfficerName" value="<?php echo $full_name; ?>" readonly>
                        <div class="nameContainer orientationContainer uploadContainer">
                            <span class="upload-text">UPLOAD</span>
                            <img id="upload-icon-nda" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                            <input class="uploadInput" type="file" id="qadOfficerSignature" name="qadOfficerSignature" accept="image/png" required="">
                        </div>
                    </div>
                    <input type="hidden" id="summaryFile" name="summaryFile">
                    <div class="button-container">
                        <button type="button" class="approve-cancel-button" onclick="closeApprovalModalPopup()">CANCEL</button>
                        <button type="submit" class="approve-assessment-button">SUBMIT</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
            <div class="custom-spinner"></div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
        <script>
            function closeApprovalModalPopup() {
                document.getElementById('approvalModal').style.display = 'none';
            }


            // Existing Modal Logic
            window.onclick = function(event) {
                var modals = [
                    document.getElementById('approvalModal'),
                    document.getElementById('ndaModal')
                ];

                modals.forEach(function(modal) {
                    if (modal && event.target == modal) {
                        modal.style.display = "none";
                    }
                });
            }

            // File change handler
            function handleFileChange(inputElement, iconElement) {
                inputElement.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        // Change icon to check mark if a file is selected
                        iconElement.src = 'images/success.png'; // Ensure this path is correct and the image exists
                    } else {
                        // Change icon back to download if no file is selected
                        iconElement.src = 'images/download-icon1.png';
                    }
                });
            }

            // Attach file change handler
            const qadOfficerSignature = document.getElementById('qadOfficerSignature');
            const uploadIconNda = document.getElementById('upload-icon-nda');
            if (qadOfficerSignature && uploadIconNda) {
                handleFileChange(qadOfficerSignature, uploadIconNda);
            }

            // Approval Modal Logic
            var modal = document.getElementById("approvalModal");
            var span = document.getElementsByClassName("close")[0];
            var approveBtns = document.getElementsByClassName("approve-btn");

            // Loop through approve buttons to add click event
            for (var i = 0; i < approveBtns.length; i++) {
                approveBtns[i].addEventListener("click", function() {
                    var summaryFile = this.getAttribute("data-summary-file");

                    var summaryFileInput = document.getElementById("summaryFile");
                    if (summaryFileInput) {
                        summaryFileInput.value = summaryFile;
                    }

                    if (modal) {
                        modal.style.display = "block";
                    }
                });
            }

            // When the user clicks on <span> (x), close the modal
            if (span) {
                span.onclick = function() {
                    if (modal) {
                        modal.style.display = "none";
                    }
                }
            }

            // Form submission loading overlay
            var approvalForm = document.querySelector('#approvalModal form');
            if (approvalForm) {
                approvalForm.addEventListener('submit', function() {
                    var loadingOverlay = document.getElementById('customLoadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('custom-spinner-hidden');
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                // NDA Modal Logic
                const ndaModal = document.getElementById('ndaModal');
                const closeNdaModalBtn = ndaModal ? ndaModal.querySelector('.nda-close-modal') : null;
                const modalTeamMembers = document.getElementById('modal-team-members');
                const noNdaMessage = document.getElementById('no-nda-message');

                // Function to open NDA modal
                function openNdaModal() {
                    if (ndaModal) {
                        ndaModal.style.display = 'block';
                    }
                }

                // Function to close NDA modal
                function closeNdaModal() {
                    if (ndaModal) {
                        ndaModal.style.display = 'none';
                    }
                }

                // Add close event listener to NDA modal close button
                if (closeNdaModalBtn) {
                    closeNdaModalBtn.addEventListener('click', closeNdaModal);
                }

                // Add event listeners to all NDA buttons
                document.querySelectorAll('.open-team-details-modal').forEach(button => {
                    button.addEventListener('click', function() {
                        // Get team details from data attributes
                        const teamLeaderString = this.getAttribute('data-team-leader') || '';
                        const teamMembersString = this.getAttribute('data-team-members') || '';
                        const teamMembers = teamMembersString ? teamMembersString.split(',') : [];
                        const ndaCompilationFile = this.getAttribute('data-nda-compilation-file');
                        const ndaIndividualFilesString = this.getAttribute('data-nda-individual-files') || '';
                        const ndaIndividualFiles = ndaIndividualFilesString ? ndaIndividualFilesString.split(',').filter(file => file.trim() !== '') : [];
                        const ndaInternalAccreditorsString = this.getAttribute('data-nda-internal-accreditors') || '';
                        const ndaInternalAccreditors = ndaInternalAccreditorsString ? ndaInternalAccreditorsString.split(',') : [];

                        // Populate team members with NDA download links or "No Submission"
                        if (modalTeamMembers) {
                            modalTeamMembers.innerHTML = ''; // Clear previous members
                            teamMembers.forEach((member, index) => {
                                if (member.trim()) {
                                    const li = document.createElement('li');
                                    if (member.trim() === teamLeaderString.trim()) {
                                        li.textContent = member.trim() + " (Leader)";
                                    } else {
                                        li.textContent = member.trim();
                                    }

                                    // Check if this member has a corresponding NDA file
                                    const ndaFileIndex = ndaInternalAccreditors.findIndex(acc => acc.trim() === member.trim());
                                    if (ndaFileIndex !== -1 && ndaIndividualFiles[ndaFileIndex]) {
                                        const downloadLink = document.createElement('a');
                                        downloadLink.href = ndaIndividualFiles[ndaFileIndex];
                                        downloadLink.textContent = 'Download NDA';
                                        downloadLink.className = 'btn udas-button1 ml-2';
                                        downloadLink.download = true;
                                        li.appendChild(downloadLink);
                                    } else {
                                        const noSubmissionText = document.createElement('span');
                                        noSubmissionText.textContent = ' - No Submission';
                                        noSubmissionText.className = 'text-muted ml-2';
                                        li.appendChild(noSubmissionText);
                                    }

                                    modalTeamMembers.appendChild(li);
                                }
                            });
                        }

                        // Handle compilation NDA file or "No Submission"
                        const compilationNdaContainer = document.getElementById('modal-compilation-nda');
                        if (compilationNdaContainer) {
                            compilationNdaContainer.innerHTML = ''; // Clear previous content
                            if (ndaCompilationFile) {
                                const compilationDownloadLink = document.createElement('a');
                                compilationDownloadLink.href = ndaCompilationFile;
                                compilationDownloadLink.textContent = 'Download Compilation NDA';
                                compilationDownloadLink.className = 'btn udas-button1';
                                compilationDownloadLink.download = true;
                                compilationNdaContainer.appendChild(compilationDownloadLink);
                            } else {
                                const noSubmissionText = document.createElement('span');
                                noSubmissionText.textContent = 'No Submission';
                                noSubmissionText.className = 'text-muted';
                                compilationNdaContainer.appendChild(noSubmissionText);
                            }
                        }

                        // Open NDA modal
                        openNdaModal();
                    });
                });
            });
        </script>
</body>

</html>
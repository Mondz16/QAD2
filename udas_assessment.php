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
    if (basename($_SERVER['PHP_SELF']) !== 'udas_assessment.php') {
        header("Location: udas_assessment.php");
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

// Query to count assessments
$countQuery = "
    SELECT COUNT(DISTINCT s.id) AS assessment_count
        FROM schedule s
        JOIN team t ON s.id = t.schedule_id
        WHERE s.schedule_status IN ('approved', 'pending')
";
$Aresult = $conn->query($countQuery);
$Arow = $Aresult->fetch_assoc();
$assessmentCount = $Arow['assessment_count'];

// Fetch approved schedules
$approvedSchedulesQuery = "
    SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
           c.college_name, p.program_name, ua.udas_assessment_file
    FROM schedule s
    JOIN college c ON s.college_code = c.code
    JOIN program p ON s.program_id = p.id
    LEFT JOIN udas_assessment ua ON s.id = ua.schedule_id
    WHERE s.schedule_status = 'approved'
";
$approvedSchedulesResult = $conn->query($approvedSchedulesQuery);
$approvedSchedules = $approvedSchedulesResult->fetch_all(MYSQLI_ASSOC);
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
        SELECT SUBSTRING(user_id, 4) AS bb_cccc, status
        FROM internal_users
        WHERE status = 'pending'
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
    <title>UDAS Assessment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="index.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <style>
        /* Additional CSS for numbered boxes */
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

        .assessment-udas {
            text-align: left;
            width: 200px;
        }

        .assessment-udas .udas-button {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: white;
            font-weight: bold;
            color: #006118;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button:hover {
            background-color: #D4FFDF;
            border: 1px solid #006118;
            color: #006118;
        }

        .assessment-udas .download-button {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: #D4FFDF;
            color: #006118;
            font-weight: bold;
            border: 1px solid #006118;
            padding-top: 9px;
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
        }

        
        .scrollable-container {
            max-height: 650px;
            max-width: 1200px;
            overflow-y: auto;
            overflow-x: hidden;
            display: inline-block;
            margin-left: 30px;
        }

        .scrollable-container-holder{
            display: inline-block;
            width: fit-content;
            padding: 20px 20px 20px 0px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #f9f9f9;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%; /* This ensures the overlay covers the full width */
            height: 100%; /* This ensures the overlay covers the full height */
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 535px; /* Set the width of the modal content */
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); /* Optional: add a subtle shadow */
            max-height: 850px; /* Limits the height of the modal content */
            overflow-y: auto; /* Enables vertical scrolling if content exceeds the height */

            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* This centers the modal */
        }




        .modal-content h2 {
            text-align: center;
            margin: 20px;
        }

        .assessment-group input {
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
            flex: 1;
        }

        .assessment-group input[type="text"], .assessment-group-college {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            text-align: left; /* Aligns the input text to the left */
        }

        .assessment-group-college, .assessment-group-program {
            height: 48px;
            font-size: 16px;
        }

        .assessment-group-college {
            margin-bottom: 20px;
        }

        .assessment-group-college, .assessment-group-program {
            height: 48px;
            font-size: 16px;
        }

        .assessment-group label {
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .name, .orientationname, .orientationname1, .assessmentname, .assessmentname1, .assessmentname2 {
            display: flex;
            gap: 10px;
        }

        .orientationname1 {
            width: 100%;
        }

        .nameContainer, .prefixContainer, .profilenameContainer, .form-group input, .assessment-group input {
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
            flex: 1;
        }

        .nameContainer {
            padding: 12px 20px;
        }

        .orientationContainer, .orientationContainer1 {
            text-align: center;
        }

        .orientationContainer1 {
            background-color: #FFEBA3;
        }

        .level, .time, .result, .area_evaluated {
            color: #575757;
            width: 100%;
            border: 0;
            resize: none;
            outline: 0;
            padding: 0;
            font-size: 16px;
            background: transparent;
            caret-color: #575757;
        }

        .titleContainer {
            flex: 1;
            padding-top: 20px;
            padding-bottom: 10px;
        }

        .upload {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .submit-button1, .cancel-button1, .approve-cancel-button, .orientation-button, .assessment-button, .assessment-button-done {
            padding: 10px 25px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .cancel-button, .cancel-button1 {
            color: #AFAFAF;
            border: 1px solid #AFAFAF;
        }

        .cancel-button1:hover {
            background-color: #FF6262;
            color: white;
            font-weight: bold;
        }
        .cancel-button1 {
            width: 150px;
        }

        .submit-button, .submit-button1, .export-button {
            color: #006118;
            border: 1px solid #006118;
            background-color: #D4FFDF;
        }

        .submit-button1 {
            width: 228px;
        }

        .submit-button:hover, .submit-button1:hover, .export-button:hover {
            background-color: #76FA97;
            border: 1px solid #76FA97;
            font-weight: bold;
        }

        .button-container, .e-sign-container {
            display: flex;
            width: 100%; /* Ensure the container takes full width */
            margin-top: 20px; /* Add some spacing from other elements */
        }

        .button-container {
            justify-content: flex-end;
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
            width: 40px; /* Size similar to Bootstrap's default spinner */
            height: 40px; /* Size similar to Bootstrap's default spinner */
            border-width: 5px;
            border-style: solid;
            border-radius: 50%;
            border-color: #B73033; /* Custom color for the spinner */
            border-right-color: transparent; /* Transparent border to create the spinning effect */
            animation: custom-spin 0.75s linear infinite; /* Bootstrap-like spinning animation */
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
        .notification-counter {
    color: #E6A33E; /* Text color */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Main Content -->
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
                            <?php if ($assessmentCount > 0): ?>
                                <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">View Assessments</span>
                                <?php if ($assessmentCount > 0): ?>
                                    <span class="notification-counter"><?= $assessmentCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">UDAS Assessments</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'assessment_history.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Assessment History</span>
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
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>
                                <?php if ($transferRequestCount > 0): ?>
                                    <span class="notification-counter"><?= $transferRequestCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'program_level.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Update Program Level</span>
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
                <h1 class="mt-5 mb-5">UDAS ASSESSMENTS</h1>
                <div class="scrollable-container-holder">
                    <div class="scrollable-container">
                        <?php
                        if (count($approvedSchedules) > 0) {
                            $counter = 1; // Counter for numbering assessments
                            foreach ($approvedSchedules as $schedule) {
                                $scheduleDate = date("F j, Y", strtotime($schedule['schedule_date']));
                                $scheduleTime = date("g:i A", strtotime($schedule['schedule_time']));
                                echo "<div class='assessment-box'>";
                                echo "<h2>#" . $counter . "</h2>";
                                echo "<div class='assessment-details'>";
                                echo "<div class='assessment-holder-1'><div class='assessment-college'><p>COLLEGE: <br><div class='assessment-values'>" . $schedule['college_name'] . "</div>PROGRAM:<br> <div class='assessment-values'>" . $schedule['program_name'] . "</div></div> <div class='assessment-level-applied'><p> LEVEL APPLIED: <br><h3>";

                                            // Display level applied with abbreviations
                                            switch ($schedule['level_applied']) {
                                                case "Not Accreditable":
                                                    echo "NA";
                                                    break;
                                                case "Candidate":
                                                    echo "CAN";
                                                    break;
                                                default:
                                                    echo $schedule['level_applied'];
                                                    break;
                                            }

                                            echo "</h3></p>
            </div>
          </div>";
                                echo "<div class='assessment-holder-2'><div class='assessment-dateTime'><p>DATE:<br><div class='assessment-values'>" . $scheduleDate . "</div> </div><div class='assessment-dateTime'><p>TIME: <br><div class='assessment-values'>" . $scheduleTime . "</div></div></br></p>";

                                if (!empty($schedule['udas_assessment_file'])) {
                                    echo "<div class='assessment-udas'><p>DOWNLOAD FILE:<br><a href='" . $schedule['udas_assessment_file'] . "' download class='btn download-button' data-schedule='" . json_encode($schedule) . "'>UDAS ASSESSMENT</a></div> </div>";
                                } else {
                                    echo "<div class='assessment-udas'><p>UDAS Assessment:<br><button class='btn open-modal udas-button' data-schedule='" . json_encode($schedule) . "'>START</button></div> </div>";
                                }

                                echo "</div></div>";
                                $counter++; // Increment counter for next assessment
                            }
                        } else {
                            echo "<div class='no-schedule-prompt'><p>NO APPROVED SCHEDULES FOUND.</p></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="udasModal" class="modal">
        <div class="modal-content">
            <h2>UDAS Assessment</h2>
            <form action="udas_assessment_process.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="schedule_id" name="schedule_id">
                <div class="assessment-group">
                    <label for="college">COLLEGE</label>
                    <input class="assessment-group-college" type="text" id="college" name="college" readonly>
                    <label for="program">PROGRAM</label>
                    <input class="assessment-group-program" type="text" id="program" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level_applied"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div style="height: 10px;"></div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="level_applied" name="level_applied" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="date" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="time" name="time" readonly>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="area"><strong>AREA<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="area" name="area" rows="10" placeholder="Add area" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="comments"><strong>COMMENTS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="comments" name="comments" rows="10" placeholder="Add comments" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="remarks"><strong>REMARKS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="remarks" name="remarks" rows="10" placeholder="Add remarks" required></textarea>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="current_datetime">CURRENT DATE AND TIME</label>
                    <input class="assessment-group-program" type="text" id="current_datetime" name="current_datetime" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="qad_officer"><strong>QAD OFFICER</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="qad_officer_signature"><strong>QAD Officer E-SIGN<span style="color: red;"> *<span></strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="qad_officer" name="qad_officer" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-officer" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="qad_officer_signature" name="qad_officer_signature" accept="image/png" required>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="qad_director">QAD DIRECTOR<span style="color: red;"> *<span></label>
                    <input class="assessment-group-program" type="text" id="qad_director" name="qad_director" required>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="closePopup()">CLOSE</button>
                    <button class="submit-button1" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        document.querySelector('#udasModal form').addEventListener('submit', function() {
            document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
        });

        // Get the modal
        var modal = document.getElementById("udasModal");

        // Get the button that opens the modal
        var btns = document.getElementsByClassName("open-modal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        for (let btn of btns) {
            btn.onclick = function() {
                var schedule = JSON.parse(this.getAttribute('data-schedule'));
                document.getElementById('schedule_id').value = schedule.id;
                document.getElementById('college').value = schedule.college_name;
                document.getElementById('program').value = schedule.program_name;
                document.getElementById('level_applied').value = schedule.level_applied;
                document.getElementById('date').value = new Date(schedule.schedule_date).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Fix the time parsing issue
                var timeParts = schedule.schedule_time.split(':');
                var hours = parseInt(timeParts[0]);
                var minutes = parseInt(timeParts[1]);
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                var formattedTime = hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ' ' + ampm;

                document.getElementById('time').value = formattedTime;

                // Set current date and time
                var now = new Date();
                var formattedNow = now.toLocaleString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
                document.getElementById('current_datetime').value = formattedNow;

                modal.style.display = "block";
            }
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function closePopup() {
            document.getElementById('udasModal').style.display = 'none';
        }

        function handleFileChange(inputElement, iconElement) {
            inputElement.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    // Change icon to check mark if a file is selected
                    iconElement.src = 'images/success.png'; // Ensure this path is correct and the image exists
                } else {
                    // Change icon back to download if no file is selected
                    iconElement.src = 'images/download-icon1.png';
                }
            });
        }

        handleFileChange(document.getElementById('qad_officer_signature'), document.getElementById('upload-icon-officer'));
    </script>
</body>

</html>
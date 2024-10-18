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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
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

        .assessment-udas .udas-button{
            background-color: #46C556;
            color: #fff;
        }

        .udas-button1 {
            background-color: #fff;
        }

        .udas-button1 {
            padding-top: 9px;
        }

        .udas-button1:hover{
            background-color: #D4FFDF;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button:hover{
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
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="dashboard.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View Schedule</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'schedule.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Add Schedule</span>
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
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">View Assessments</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">UDAS Assessments</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Administrative</span>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'area.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Area</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'registration.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Register Verification</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Reports</span>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin === false ? 'internal_assigned_schedule.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Assigned Schedule</span></a>
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
                    JOIN team t ON s.id = t.schedule_id
                    JOIN college c ON s.college_code = c.code
                    JOIN program p ON s.program_id = p.id
                    WHERE t.id = '$teamId' AND (s.schedule_status = 'approved' OR s.schedule_status = 'pending')
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

                                            // Fetch NDA Compilation
                                            $ndaQuery = "SELECT NDA_compilation_file FROM NDA_compilation WHERE team_id = '$teamId'";
                                            $ndaResult = $conn->query($ndaQuery);
                                            $ndaFile = $ndaResult->fetch_assoc()['NDA_compilation_file'];

                                            // Render assessment details
                                            echo "<div class='assessment-box'>";
                                            echo "<h2>#" . $counter . "</h2>";
                                            echo "<div class='assessment-details'>";
                                            echo "<div class='assessment-holder-1'>
                            <div class='assessment-college'>
                                <p> COLLEGE:  <br><div class='assessment-values'>" . $schedule['college_name'] . "</div></p>
                                <p> PROGRAM:  <br><div class='assessment-values'>" . $schedule['program_name'] . "</div></p>
                            </div>
                            <div class='assessment-level-applied'>
                                <p> LEVEL APPLIED:  <br><h3>";

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

                                            echo "<div class='assessment-holder-2'>
                            <div class='assessment-dateTime'>
                                <p> DATE:  <br><div class='assessment-values'>" . $scheduleDate . "</div></p>
                            </div>
                            <div class='assessment-dateTime'>
                                <p> TIME:  <br><div class='assessment-values'>" . $scheduleTime . "</div></p>
                            </div>
                            <div class='assessment-udas'>
                                <p> DOWNLOADABLE  <br><a href='$summaryFile' class='btn udas-button1' download>SUMMARY</a></p>
                            </div>
                            <div class='assessment-udas'>
                                <p> FILES:  <br><a href='$ndaFile' class='btn udas-button1' download>NDA</a></p>
                            </div>";

                                            // Show approve button or check symbol based on approval status
                                            if ($isApproved) {
                                                echo "<div class='assessment-udas'>
                                <p> Approve  <br><button class='assessment-button-done'>APPROVED</button></p>
                            </div>";
                                            } else {
                                                echo "<div class='assessment-udas'>
                                <p> Approve  <br><button class='btn approve-btn udas-button' data-summary-file='$summaryFile'>APPROVE</button></p>
                            </div>";
                                            }

                                            echo "</div>"; // Close assessment-holder-2
                                            echo "</div>"; // Close assessment-details
                                            echo "</div>"; // Close assessment-box

                                            $counter++; // Increment counter for next assessment
                                        }
                                    }
                                }
                            }

                            if($counter == 1){
                                echo "<div class='no-schedule-prompt'><p>NO ASSESSMENT SUMMARY FOUND</p></div>";
                            }
                        } else {
                            echo "<div class='no-schedule-prompt'><p>NO ASSESSMENT SUMMARY FOUND</p></div>";
                        }
                        ?>


                    </div>
                </div>
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
            window.onclick = function(event) {
                var modals = [
                    document.getElementById('approvalModal')
                ];

                modals.forEach(function(modal) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                });
            }

            function closeApprovalModalPopup() {
                document.getElementById('approvalModal').style.display = 'none';
            }

            document.querySelector('#approvalModal form').addEventListener('submit', function() {
                document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
            });

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

            handleFileChange(document.getElementById('qadOfficerSignature'), document.getElementById('upload-icon-nda'));

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
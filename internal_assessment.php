<?php
include 'connection.php';
session_start();

date_default_timezone_set('Asia/Manila');
$current_date = date('F j, Y'); // Format: "Month Day, Year"

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin' && basename($_SERVER['PHP_SELF']) !== 'admin_sidebar.php') {
    $is_admin = true;
    header("Location: admin_sidebar.php");
    exit();
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal_assessment.php') {
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

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql_user = "SELECT first_name, middle_initial, last_name, email, college_code, profile_picture FROM internal_users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_initial, $last_name, $email, $college_code, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();

// Fetch college name
$sql_college = "SELECT college_name FROM college WHERE code = ?";
$stmt_college = $conn->prepare($sql_college);
$stmt_college->bind_param("s", $college_code);
$stmt_college->execute();
$stmt_college->bind_result($user_college_name);
$stmt_college->fetch();
$stmt_college->close();

// Fetch notifications for the logged-in user
$sql_notifications = "
    SELECT COUNT(*) 
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    WHERE t.internal_users_id = ? AND t.status = 'pending' AND s.schedule_status NOT IN ('cancelled', 'finished')
";

$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->bind_result($notification_count);
$stmt_notifications->fetch();
$stmt_notifications->close();

$accreditor_type = (substr($user_id, 3, 2) == '11') ? 'Internal Accreditor' : 'External Accreditor';

// Fetch user details for displaying in the form
$sql_user_details = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
$stmt_user_details = $conn->prepare($sql_user_details);
$stmt_user_details->bind_param("s", $user_id);
$stmt_user_details->execute();
$stmt_user_details->bind_result($first_name, $middle_initial, $last_name);
$stmt_user_details->fetch();
$full_name = $first_name . ' ' . $middle_initial . '. ' . $last_name;
$stmt_user_details->close();

// Fetch schedule details for the logged-in user with status 'accepted'
$sql_schedules = "
    SELECT s.id AS schedule_id, c.college_name, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, 
        t.id AS team_id, t.role, GROUP_CONCAT(a.area_name SEPARATOR ', ') AS area_names
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    JOIN program p ON s.program_id = p.id
    JOIN college c ON s.college_code = c.code
    LEFT JOIN team_areas ta ON t.id = ta.team_id
    LEFT JOIN area a ON ta.area_id = a.id
    WHERE t.internal_users_id = ? 
    AND t.status = 'accepted'
    AND s.schedule_status NOT IN ('cancelled', 'finished')
    GROUP BY t.id, s.id
";

$stmt_schedules = $conn->prepare($sql_schedules);
$stmt_schedules->bind_param("s", $user_id);
$stmt_schedules->execute();
$stmt_schedules->store_result();
$stmt_schedules->bind_result($schedule_id, $college_name, $program_name, $level_applied, $schedule_date, $schedule_time, $schedule_status, $team_id, $role, $area_names);

$schedules = [];
while ($stmt_schedules->fetch()) {
    $schedules[] = [
        'schedule_id' => $schedule_id,
        'college_name' => $college_name,
        'program_name' => $program_name,
        'level_applied' => $level_applied,
        'schedule_date' => $schedule_date,
        'schedule_time' => $schedule_time,
        'schedule_status' => $schedule_status,
        'team_id' => $team_id,
        'role' => $role,
        'area' => $area_names // Use the concatenated area names here
    ];
}
$stmt_schedules->close();


// Fetch existing assessments and summaries for the user
$existing_assessments = [];
$existing_summaries = [];

$sql_assessments = "SELECT team_id FROM assessment WHERE team_id IN (SELECT id FROM team WHERE internal_users_id = ?)";
$stmt_assessments = $conn->prepare($sql_assessments);
$stmt_assessments->bind_param("s", $user_id);
$stmt_assessments->execute();
$stmt_assessments->bind_result($team_id);
while ($stmt_assessments->fetch()) {
    $existing_assessments[] = $team_id;
}
$stmt_assessments->close();

$sql_summaries = "SELECT team_id FROM summary WHERE team_id IN (SELECT id FROM team WHERE internal_users_id = ?)";
$stmt_summaries = $conn->prepare($sql_summaries);
$stmt_summaries->bind_param("s", $user_id);
$stmt_summaries->execute();
$stmt_summaries->bind_result($team_id);
while ($stmt_summaries->fetch()) {
    $existing_summaries[] = $team_id;
}
$stmt_summaries->close();

// Fetch approved assessments
$approved_assessments = [];
$sql_approved_assessments = "SELECT assessment_id FROM approved_assessment";
$result_approved_assessments = $conn->query($sql_approved_assessments);
while ($row = $result_approved_assessments->fetch_assoc()) {
    $approved_assessments[] = $row['assessment_id'];
}

// Fetch team members and their assessment status
$team_members = [];
$sql_team_members = "
    SELECT t.schedule_id, t.internal_users_id, iu.first_name, iu.middle_initial, iu.last_name, t.id AS team_id, 
    (SELECT a.id FROM assessment a WHERE a.team_id = t.id LIMIT 1) AS assessment_id, 
    (SELECT a.assessment_file FROM assessment a WHERE a.team_id = t.id LIMIT 1) AS assessment_file, t.role
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    WHERE t.schedule_id IN (SELECT schedule_id FROM team WHERE internal_users_id = ?)
";
$stmt_team_members = $conn->prepare($sql_team_members);
$stmt_team_members->bind_param("s", $user_id);
$stmt_team_members->execute();
$stmt_team_members->bind_result($team_schedule_id, $team_member_id, $team_member_first_name, $team_member_middle_initial, $team_member_last_name, $team_member_team_id, $team_member_assessment_id, $team_member_assessment_file, $team_member_role);
while ($stmt_team_members->fetch()) {
    $team_members[$team_schedule_id][] = [
        'user_id' => $team_member_id,
        'name' => $team_member_first_name . ' ' . $team_member_middle_initial . '. ' . $team_member_last_name,
        'team_id' => $team_member_team_id,
        'assessment_id' => $team_member_assessment_id,
        'assessment_file' => $team_member_assessment_file,
        'role' => $team_member_role
    ];
}
$stmt_team_members->close();

// Prepare to retrieve team members with areas assigned to them
$team_members_with_areas = [];
$sql_team_members_with_areas = "
    SELECT t.schedule_id, t.id AS team_member_id, t.role, iu.first_name, iu.middle_initial, iu.last_name,
        GROUP_CONCAT(ta.area_id) AS area_ids
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    LEFT JOIN team_areas ta ON t.id = ta.team_id
    WHERE t.schedule_id IN (
        SELECT schedule_id FROM team WHERE internal_users_id = ?
    )
    GROUP BY t.id
";

$stmt_team_members_with_areas = $conn->prepare($sql_team_members_with_areas);
$stmt_team_members_with_areas->bind_param("s", $user_id); // Use the user_id to get all schedules they are part of
$stmt_team_members_with_areas->execute();
$stmt_team_members_with_areas->bind_result($team_schedule_id, $team_member_id, $team_member_role, $team_member_first_name, $team_member_middle_initial, $team_member_last_name, $assigned_area_ids);

while ($stmt_team_members_with_areas->fetch()) {
    $team_members_with_areas[$team_schedule_id][] = [
        'team_member_id' => $team_member_id,
        'name' => $team_member_first_name . ' ' . $team_member_middle_initial . '. ' . $team_member_last_name,
        'role' => $team_member_role,
        'areas' => explode(',', $assigned_area_ids) // Convert the comma-separated area IDs into an array
    ];
}
$stmt_team_members_with_areas->close();

// Check NDA status for each schedule
$nda_signed_status = [];
foreach ($schedules as $schedule) {
    $sql_nda = "SELECT id FROM NDA WHERE team_id = ?";
    $stmt_nda = $conn->prepare($sql_nda);
    $stmt_nda->bind_param("i", $schedule['team_id']);
    $stmt_nda->execute();
    $stmt_nda->store_result();
    $nda_signed_status[$schedule['schedule_id']] = $stmt_nda->num_rows > 0;
    $stmt_nda->close();
}

// Function to convert an integer to Roman numeral
function intToRoman($num) {
    $map = [
        1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
        100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
        10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV',
        1 => 'I'
    ];
    $result = '';
    foreach ($map as $value => $roman) {
        while ($num >= $value) {
            $result .= $roman;
            $num -= $value;
        }
    }
    return $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Assessment</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .ndamodal1 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place even when scrolling */
            z-index: 1; /* Sit on top */
            left: 0; /* Start from the left */
            top: 0; /* Start from the top */
            width: 100%; /* Cover the full width */
            height: 100%; /* Cover the full height */
            overflow: auto; /* Enable scrolling if needed */
            background-color: rgba(0, 0, 0, 0.5); /* Background overlay */
        }

        .ndamodal-content1 {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #AFAFAF;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 560px;
            border-radius: 20px;
            overflow-y: auto; /* Enables vertical scrolling if content exceeds the height */

            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* This centers the modal */
        }
    </style>
</head>
<body>
    <div class="wrapper">
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
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
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
        <div style="height: 10px; width: 0px;"></div>
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
    <div class="container">
        <div style="height: 32px;"></div>
        <div class="orientation2">
            <?php if (!empty($schedules)): ?>
                <?php foreach ($schedules as $index => $schedule): ?>
                    <?php
                    // Initialize the $areas array for this schedule
                    $areas = [];

                    // Fetch areas dynamically based on level_applied and program_name
                    if ($schedule['level_applied'] == 1 || $schedule['level_applied'] == 2) {
                        // Level 1 and 2 areas
                        $sql_areas = "SELECT id, area_name FROM area WHERE area_name IN (
                            'Vision, Mission, Goals, and Objectives', 
                            'Faculty', 
                            'Curriculum and Instruction', 
                            'Support to Students', 
                            'Research', 
                            'Extension and Community Development', 
                            'Library', 
                            'Physical Plant and Facilities', 
                            'Laboratories', 
                            'Administration'
                        )";
                    } elseif ($schedule['level_applied'] == 3) {
                        // Level 3 areas
                        if (strpos($schedule['program_name'], 'Bachelor') === 0) {
                            // Program name starts with "Bachelor"
                            $sql_areas = "SELECT id, area_name FROM area WHERE area_name IN (
                                'Curriculum and Instruction', 
                                'Extension and Community Development', 
                                'Faculty Development', 
                                'Licensure Exam', 
                                'Consortia or linkages', 
                                'Library'
                            )";
                        } else {
                            // Program name does NOT start with "Bachelor"
                            $sql_areas = "SELECT id, area_name FROM area WHERE area_name IN (
                                'Curriculum and Instruction', 
                                'Research', 
                                'Faculty Development', 
                                'Licensure Exam', 
                                'Consortia or linkages', 
                                'Library'
                            )";
                        }
                    } elseif ($schedule['level_applied'] == 4) {
                        // Level 4 areas
                        $sql_areas = "SELECT id, area_name FROM area WHERE area_name IN (
                            'Research', 
                            'Curriculum and Instruction', 
                            'Extension and Community Development', 
                            'Consortia or linkages', 
                            'Administration'
                        )";
                    }

                    // Execute the query to get areas for this schedule
                    $result_areas = $conn->query($sql_areas);
                    if ($result_areas) {
                        while ($row = $result_areas->fetch_assoc()) {
                            $areas[$row['id']] = $row['area_name']; // Store area id and name
                        }
                    }
                    ?>
                    <div class="notification-list1">
                        <div class="orientation3">
                            <div class="container">
                                <div class="body4">
                                    <div class="bodyLeft2">
                                        <p>COLLEGE<br>
                                    <div style="height: 10px;"></div>
                                    <div class="orientationname">
                                        <div class="nameContainer">
                                            <?php echo htmlspecialchars($schedule['college_name']); ?>
                                        </div>
                                </div></p>
                                <div style="height: 20px;"></div>
                                    <p>PROGRAM <br>
                                <div style="height: 10px;"></div>
                                <div class="orientationname">
                                    <div class="nameContainer">
                                        <?php echo htmlspecialchars($schedule['program_name']); ?>
                                    </div>
                                </div></p>
                                <div class="orientationname">
                                    <div class="titleContainer">
                                        <p>LEVEL APPLIED</p>
                                    </div>
                                    <div class="titleContainer">
                                        <p>TEAM MEMBERS</p>
                                    </div>
                                </div>
                                
                                <div class="orientationname">
                                    <div class="nameContainer orientationContainer1">
                                        <?php echo htmlspecialchars($schedule['level_applied']); ?>
                                    </div>
                                    
                                    <button id="openModalBtn-<?php echo $index; ?>" class="view-membersContainer orientationContainer">View Team</button>
                                </div>

                                <div class="orientationname">
                                    <div class="titleContainer">
                                        <p>DATE</p>
                                    </div>
                                    <div class="titleContainer">
                                        <p>TIME</p>
                                    </div>
                                </div>

                                <div class="orientationname">
                                    <div class="nameContainer orientationContainer">
                                        <?php 
                                        $schedule_date = new DateTime($schedule['schedule_date']);
                                        echo $schedule_date->format('F j, Y'); // This will output the date as "Nov. 20, 2024"
                                        ?>
                                    </div>

                                    <div class="nameContainer orientationContainer">
                                        <?php
                                        $schedule_time = new DateTime($schedule['schedule_time']);
                                        echo $schedule_time->format('g:i A');
                                        ?>
                                    </div>
                                </div>

                                <!-- Team Members Modal -->
                                <div id="myModal-<?php echo $index; ?>" class="ndamodal1"> 
                                    <div class="ndamodal-content1">
                                        <span class="close-btn" id="closeModalBtn-<?php echo $index; ?>">&times;</span>
                                        <h2>Team Members</h2>
                                        <div style="height: 20px; width: 0px;"></div>
                                        <div class="modal-body">
                                            <?php 
                                            // Assuming the role "Leader" can be identified with the role variable
                                            $team_leader = null;
                                            $team_members_list = [];

                                            // Separate the team leader and members
                                            foreach ($team_members_with_areas[$schedule['schedule_id']] as $member) {
                                                if ($member['role'] === 'Team Leader') {
                                                    $team_leader = $member;
                                                } else {
                                                    $team_members_list[] = $member;
                                                }
                                            }
                                            ?>

                                            <p><strong>Team Leader:</strong> <?php echo $team_leader ? htmlspecialchars($team_leader['name']) : 'N/A'; ?></p>
                                            <div style="height: 10px; width: 0px;"></div>
                                            <p><strong>Team Members:</strong></p>
                                            <ul style="margin-left: 30px; padding-left: 0;">
                                                <?php foreach ($team_members_list as $member): ?>
                                                    <li><?php echo htmlspecialchars($member['name']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <div class="bodyRight2">
                        <?php if ($schedule['role'] === 'Team Leader'): ?>
                            <?php if ($schedule['schedule_status'] == 'done'): ?>
                                <!-- Logic for displaying "locked" message when schedule is done for Team Leader -->
                                <p>ASSESSMENT</p>
                                <div style="height: 10px;"></div>
                                <p class="pending-assessments">THIS SCHEDULE IS LOCKED.</p>
                            <?php elseif ($schedule['schedule_status'] == 'pending'): ?>
                                <p>SCHEDULE STATUS</p>
                                <div style="height: 10px;"></div>
                                <button class="assessment-button-done" style="background-color: #AFAFAF; color: black; border: 1px solid #AFAFAF; width: 441px;">WAIT FOR THE SCHEDULE TO BE APPROVED</button> 
                            <?php else: ?>
                                    <!-- Check if Areas are Assigned -->
                                    <?php
                                    // Check if all areas are assigned
                                    $all_areas_assigned = true;
                                    if (isset($team_members_with_areas[$schedule['schedule_id']])) {
                                        foreach ($team_members_with_areas[$schedule['schedule_id']] as $member) {
                                            // Check if non-team leaders have no areas assigned
                                            if ($member['role'] !== 'Team Leader' && empty($member['areas'][0])) {
                                                $all_areas_assigned = false;
                                                break;
                                            }
                                        }
                                    } else {
                                        $all_areas_assigned = false; // No members found for this schedule, so areas can't be assigned
                                    }
                                    ?>

                                    <?php if (!$all_areas_assigned): ?>
                                        <!-- Display Team Leader and Team Members with Area Input if not all areas are assigned -->
                                        <p>ASSIGN AREAS TO TEAM MEMBERS</p>
                                        <div style="height: 10px;"></div>
                                        <form method="post" action="assign_areas_process.php">
                                            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($schedule['schedule_id']); ?>">

                                            <?php 
                                            // Separate team leader and other members
                                            $team_leader = null;
                                            $other_members = [];

                                            foreach ($team_members_with_areas[$schedule['schedule_id']] as $member) {
                                                if ($member['role'] === 'Team Leader') {
                                                    $team_leader = $member; // Store the Team Leader
                                                } else {
                                                    $other_members[] = $member; // Store other members
                                                }
                                            }

                                            // Display Team Leader
                                            if ($team_leader): ?>
                                                <div class="add-area" id="team-leader-area" style="display: flex; flex-direction: column; margin-bottom: 10px;">
                                                    <label><?php echo htmlspecialchars($team_leader['name']); ?> (<?php echo htmlspecialchars($team_leader['role']); ?>)</label>
                                                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                                        <select class="area-select" name="area[<?php echo $team_leader['team_member_id']; ?>][]" required onchange="updateAreaOptions()">
                                                            <option value="" disabled selected>Select Area</option>
                                                            <?php foreach ($areas as $id => $area_name): ?>
                                                                <option value="<?php echo $id; ?>">
                                                                    Area <?php echo intToRoman($id); ?> - <?php echo htmlspecialchars($area_name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="button" onclick="addAreaDropdown('team-leader-area', '<?php echo $team_leader['team_member_id']; ?>')" style="border: none; background: none; cursor: pointer; padding-left: 8px;">
                                                            <i class="fa-solid fa-circle-plus" style="color: green; font-size: 25px;"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Other members -->
                                            <?php foreach ($other_members as $member): ?>
                                                <div class="add-area" id="member-area-<?php echo $member['team_member_id']; ?>" style="display: flex; flex-direction: column; margin-bottom: 10px;">
                                                    <label><?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['role']); ?>)</label>
                                                    <div style="display: flex; align-items: center; margin-bottom: 10px;"> <!-- Add margin here -->
                                                        <select class="area-select" name="area[<?php echo $member['team_member_id']; ?>][]" required onchange="updateAreaOptions()">
                                                            <option value="" disabled selected>Select Area</option>
                                                            <?php foreach ($areas as $id => $area_name): ?>
                                                                <option value="<?php echo $id; ?>">
                                                                    Area <?php echo intToRoman($id); ?> - <?php echo htmlspecialchars($area_name); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="button" onclick="addAreaDropdown('member-area-<?php echo $member['team_member_id']; ?>', '<?php echo $member['team_member_id']; ?>')" style="border: none; background: none; cursor: pointer; padding-left: 8px;">
                                                            <i class="fa-solid fa-circle-plus" style="color: green; font-size: 25px;"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <button type="submit" class="assessment-button1">ASSIGN AREAS</button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!$nda_signed_status[$schedule['schedule_id']]): ?>
                                            <p>NON-DISCLOSURE AGREEMENT</p>
                                            <div style="height: 10px;"></div>
                                            <button class="assessment-button" onclick="openNdaPopup('<?php echo $full_name; ?>', <?php echo $schedule['team_id']; ?>)">SIGN</button>
                                        <?php else: ?>
                                        <!-- Proceed with checking team members' submission and approval status -->
                                        <?php
                                        $team_member_count = 0;
                                        $submitted_count = 0;
                                        $approved_count = 0;
                                        foreach ($team_members[$schedule['schedule_id']] as $member) {
                                            if ($member['role'] !== 'Team Leader') {
                                                $team_member_count++;
                                                if ($member['assessment_file']) {
                                                    $submitted_count++;
                                                }
                                                if (in_array($member['assessment_id'], $approved_assessments)) {
                                                    $approved_count++;
                                                }
                                            }
                                        }
                                        ?>
                                        <p>MEMBER SUBMISSION STATUS</p>
                                            <div style="height: 10px;"></div>
                                            <div class="assessmentname2">
                                                <div class="nameContainer">
                                                    <p><?php echo $submitted_count; ?>/<?php echo $team_member_count; ?> SUBMITTED ASSESSMENTS</p>
                                                </div>
                                            </div>
                                            <div style="height: 20px;"></div>
                                            <p>TEAM MEMBERS ASSESSMENT</p>
                                            <div style="height: 10px;"></div>
                                            <ul style="list-style: none; font-size: 18px;">
                                                <?php foreach ($team_members[$schedule['schedule_id']] as $member): ?>
                                                    <?php if ($member['assessment_file'] && $member['role'] !== 'Team Leader'): ?>
                                                        <li>
                                                            <div class="assessmentname1">
                                                                <div class="titleContainer1">
                                                                    <?php echo htmlspecialchars($member['name']); ?>
                                                                </div>
                                                                <div class="titleContainer2">
                                                                    <a href="<?php echo htmlspecialchars($member['assessment_file']); ?>"><i class="bi bi-file-earmark-arrow-down"></i></a>
                                                                </div>
                                                                <div class="titleContainer3">
                                                                    <?php if (in_array($member['assessment_id'], $approved_assessments)): ?>
                                                                        <i class="fas fa-check approve1"></i>
                                                                    <?php else: ?>
                                                                        <button class="approve" onclick="approveAssessmentPopup(<?php echo htmlspecialchars(json_encode($member)); ?>)">APPROVE</button>
                                                                    <?php endif; ?>
                                                                </div>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php if (in_array($schedule['team_id'], $existing_summaries)): ?>
                                                <div style="height: 20px;"></div>
                                                <p>SUBMIT SUMMARY</p>
                                                <div style="height: 10px;"></div>
                                                <p class="assessment-button-done">ALREADY SUBMITTED</p>
                                            <?php elseif ($submitted_count < $team_member_count): ?>
                                            <?php elseif ($approved_count < $team_member_count): ?>
                                                <div style="height: 20px;"></div>
                                                <p>SUBMIT SUMMARY</p>
                                                <div style="height: 10px;"></div>
                                                <p class="pending-assessments">APPROVE ASSESSMENTS FIRST</p>
                                            <?php else: ?>
                                                <div style="height: 20px;"></div>
                                                <p>SUBMIT SUMMARY</p>
                                                <div style="height: 10px;"></div>
                                                <button class="assessment-button" onclick="SummaryopenPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">START SUMMARY</button>
                                            <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($schedule['schedule_status'] == 'pending'): ?>
                                <p>SCHEDULE STATUS</p>
                                <div style="height: 10px;"></div>
                                <button class="assessment-button-done" style="background-color: #AFAFAF; color: black; border: 1px solid #AFAFAF; width: 441px;">WAIT FOR THE SCHEDULE TO BE APPROVED</button>
                            <?php elseif ($schedule['schedule_status'] == 'done'): ?>
                                <!-- Logic for displaying "locked" message when schedule is done for non-Team Leader -->
                                <p>ASSESSMENT</p>
                                <div style="height: 10px;"></div>
                                <p class="pending-assessments">THIS SCHEDULE IS LOCKED.</p>
                            <?php else: ?>
                                <?php if (!$nda_signed_status[$schedule['schedule_id']]): ?>
                                    <p>NON-DISCLOSURE AGREEMENT</p>
                                    <div style="height: 10px;"></div>
                                    <button class="assessment-button" id="sign-button">SIGN</button>
                                <?php elseif ($schedule['area'] == ''): ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                    <p class="pending-assessments">YOUR TEAM LEADER SHOULD ASSIGN AREA FIRST</p>
                                <?php elseif (in_array($schedule['team_id'], $existing_assessments)): ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                    <p class="assessment-button-done">ALREADY SUBMITTED</p>
                                <?php else: ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                    <button class="assessment-button" onclick="openPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">START ASSESSMENT</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                        </div>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; font-size: 20px"><strong>NO SCHEDULED INTERNAL ACCREDITATION HAS BEEN ACCEPTED</strong></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- NDA Signing Popup -->
    <div class="ndamodal1" id="ndaPopup" style="display: none;">
        <div class="ndamodal-content1">
            <h2>NON-DISCLOSURE AGREEMENT</h2>
            <form action="internal_nda_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="team_id" id="nda_team_id">
                <input type="hidden" name="internal_accreditor" id="nda_internal_accreditor">
                <input type="hidden" name="date_added" id="date_added" value="<?php echo date('Y-m-d'); ?>">
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="internal_accreditor"><strong>INTERNAL ACCREDITOR</strong></label>
                    </div>
                    <div class="titleContainer" style="padding-left: 100px;">
                        <label for="internal_accreditor_signature"><strong>E-SIGNATURE</strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer" style="padding-right: 110px">
                        <input class="area_evaluated" type="text" id="internal_accreditor" name="internal_accreditor" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-nda" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="internal_accreditor_signature" name="internal_accreditor_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button" type="button" onclick="closeNdaPopup()">CANCEL</button>
                    <button class="submit-button" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

        <!-- Terms and Conditions Modal -->
        <div id="termsModal" class="popup2" style="display:none;">
            <div class="esign-popup-content">
                <div style="height: 20px; width: 0px;"></div>
                <h2>Electronic Signature<br>Usage Agreement</h2>
                <p>By agreeing to this statement, you consent to the following terms and conditions regarding the use of your electronic signature:<br><br>

                    1. You acknowledge and agree that your electronic signature will be used exclusively for internal accreditation purposes within our organization. This includes, but is not limited to, verifying and validating documents, authorizations, and other internal procedures.<br><br>

                    2. You understand and agree that your electronic signature will be encrypted using AES-256-CBC encryption. This ensures that your electronic signature is secure and protected against unauthorized access, tampering, and breaches.<br><br>

                    3. You consent to the secure storage of your electronic signature in our database, which is protected by advanced security measures. Access to this database is restricted to authorized personnel only, ensuring that your electronic signature is used appropriately and solely for the purposes outlined above.<br><br>

                    4. You agree that your electronic signature will be kept confidential and will not be shared, disclosed, or used for any purposes other than those specified in this agreement without your explicit consent.<br><br>

                    5. You acknowledge that it is your responsibility to ensure that your electronic signature is accurate and to safeguard any credentials or devices used to create your electronic signature.<br><br>

                    6. You understand that we reserve the right to update or modify these terms and conditions at any time. Any changes will be communicated to you, and your continued use of your electronic signature for internal accreditation purposes will constitute your acceptance of the revised terms.<br><br>

                    If you have any questions or concerns regarding the use of your electronic signature or these terms and conditions, please contact us at usepqad@gmail.com.<br><br>

                    By clicking "Agree," you consent to the use of your electronic signature as described above and agree to the security measures implemented for its protection.</p><br><br>
                <label>
                    <input type="checkbox" id="agreeTermsCheckbox"> I agree to the terms and conditions
                </label><br><br>
                <div class="e-sign-container">
                    <button class="cancel-button1" id="closeTermsBtn" type="button">CLOSE</button>
                    <button class="approve-assessment-button" id="acceptTerms" onclick="openNdaPopup('<?php echo $full_name; ?>', <?php echo $schedule['team_id']; ?>)" disabled>SUBMIT</button>
                </div>
            </div>
        </div>

    <!-- Popup Form for Team Member -->
    <div class="assessmentmodal" id="popup">
        <div class="assessmentmodal-content">
            <h2>ASSESSMENT FORM</h2>
            <form action="internal_assessment_process.php" method="POST" enctype="multipart/form-data">
                <div class="assessment-group">
                    <input type="hidden" name="schedule_id" id="modal_schedule_id">
                    <label for="college">COLLEGE</label>
                    <input class="assessment-group-college" type="text" id="college" name="college" readonly>
                    <label for="program">PROGRAM</label>
                    <input class="assessment-group-program" type="text" id="program" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="level" name="level" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="date" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="time" name="time" readonly>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="result"><strong>RESULT<span style="color: red;"> *<span></strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="area_evaluated"><strong>AREA EVALUATED</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer">
                        <select style="cursor: pointer;" class="result" id="result" name="result" required>
                            <option value="">SELECT RESULT</option>
                            <option value="Ready">Ready</option>
                            <option value="Needs Improvement">Needs Improvement</option>
                            <option value="Revisit">Revisit</option>
                        </select>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="area_evaluated" name="area_evaluated" readonly>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="findings"><strong>FINDINGS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="findings" name="findings" rows="10" placeholder="Add a comment" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="recommendations"><strong>RECOMMENDATIONS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="recommendations" name="recommendations" rows="10" placeholder="Add a comment" required></textarea>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="evaluator"><strong>EVALUATOR</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="evaluator_signature"><strong>EVALUATOR E-SIGN<span style="color: red;"> *<span></strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-evaluator" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="closePopup()">CLOSE</button>
                    <button class="submit-button1" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Team Leader -->
    <div class="Summarymodal" id="Summarypopup">
        <div class="Summarymodal-content">
            <h2>SUMMARY FORM</h2>
            <form action="internal_summary_assessment_process.php" method="POST" enctype="multipart/form-data">
                <div class="assessment-group">
                    <input type="hidden" name="schedule_id" id="Summarymodal_schedule_id">
                    <label for="college">COLLEGE</label>
                    <input type="text" id="Summarycollege" name="college" readonly>
                    <div style="height: 20px;"></div>
                    <label for="program">PROGRAM</label>
                    <input type="text" id="Summaryprogram" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="Summarylevel" name="level" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="Summarydate" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="Summarytime" name="time" readonly>
                    </div>
                </div>
                <div id="result-section" style="display: none;">
                    <div class="orientationname1">
                        <div class="titleContainer">
                            <label for="result"><strong>RESULT (TEAM LEADER)<span style="color: red;"> *<span></strong></label>
                        </div>
                    </div>
                    <div class="orientationname1">
                        <div class="nameContainer orientationContainer" id="result-container">
                            <!-- Result dropdown will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="results"><strong>RESULTS (TEAM MEMBERS)</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px; font-size: 16px;" id="results" name="results" rows="10" readonly></textarea>
                    <div style="height: 20px;"></div>
                    <label for="areas"><strong>AREAS EVALUATED</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px; font-size: 16px;" id="areas" name="areas" rows="10" readonly></textarea>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="evaluator"><strong>EVALUATOR</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="Summaryevaluator_signature"><strong>TEAM LEADER E-SIGN<span style="color: red;"> *<span></strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="Summaryevaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-team-evaluator" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="Summaryevaluator_signature" name="evaluator_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="SummaryclosePopup()">Close</button>
                    <button class="submit-button1" type="submit">Submit Summary</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Approving Assessment -->
    <div class="approvalmodal" id="approveAssessmentPopup">
        <div class="approvalmodal-content">
            <h2>APPROVE ASSESSMENT</h2>
            <form id="approveAssessmentForm" action="internal_approve_assessment_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="team_id" id="approve_team_id">
                <input type="hidden" name="assessment_file" id="approve_assessment_file">
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="team_leader"><strong>TEAM LEADER</strong></label>
                    </div>
                    <div class="titleContainer" style="padding-left: 100px;">
                        <label for="team_leader_signature"><strong>TEAM LEADER E-SIGN<span style="color: red;"> *<span></strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer" style="padding-right: 110px">
                        <input class="area_evaluated" type="text" id="team_leader" name="team_leader" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-leader" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="team_leader_signature" name="team_leader_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="approve-cancel-button" type="button" onclick="closeApproveAssessmentPopup()">CANCEL</button>
                    <button class="approve-assessment-button" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <div id="logoutModal" class="modal1">
        <div class="modal-content1">
            <h4 id="confirmationMessage" style="font-size: 20px;">Are you sure you want to logout?</h4>
            <div class="button-container">
                <button type="button" class="accept-back-button" id="backButton" onclick="cancelLogout()">NO</button>
                <button type="button" class="accept-confirm-button" id="confirmButton" onclick="confirmLogout()">YES</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block'; // Show the modal
        }

        function confirmLogout() {
            window.location.href = 'logout.php'; // Redirect to logout.php
        }

        function cancelLogout() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        document.addEventListener('click', function(event) {
            var modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        document.getElementById('approveAssessmentForm').addEventListener('submit', function(event) {
            // Show the loading spinner
            document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
        });

        document.querySelector('#popup form').addEventListener('submit', function() {
            document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
        });

        document.querySelector('#Summarypopup form').addEventListener('submit', function() {
            document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
        });

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

        handleFileChange(document.getElementById('internal_accreditor_signature'), document.getElementById('upload-icon-nda'));
        handleFileChange(document.getElementById('evaluator_signature'), document.getElementById('upload-icon-evaluator'));

        handleFileChange(document.getElementById('Summaryevaluator_signature'), document.getElementById('upload-icon-team-evaluator'));
        handleFileChange(document.getElementById('team_leader_signature'), document.getElementById('upload-icon-leader'));

        function toggleNotifications() {
            var dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            // Format the date to include short month format
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short', // Short month (e.g., "Jan")
                day: 'numeric',
            });
            // Add a dot to the month abbreviation if it's not already there
            return formattedDate.replace(/(\b\w{3}\b)(?=\s\d{1,2},\s\d{4})/, '$1.');
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(hours, minutes);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true,
            });
        }

        function openPopup(schedule) {
            document.getElementById('modal_schedule_id').value = schedule.schedule_id;
            document.getElementById('college').value = schedule.college_name;
            document.getElementById('program').value = schedule.program_name;
            document.getElementById('level').value = schedule.level_applied;

            // Format the date and time
            document.getElementById('date').value = formatDate(schedule.schedule_date);
            document.getElementById('time').value = formatTime(schedule.schedule_time);
            document.getElementById('area_evaluated').value = schedule.area;

            document.getElementById('popup').style.display = 'block';
        }

        function SummaryopenPopup(schedule) {
            document.getElementById('Summarymodal_schedule_id').value = schedule.schedule_id;
            document.getElementById('Summarycollege').value = schedule.college_name;
            document.getElementById('Summaryprogram').value = schedule.program_name;
            document.getElementById('Summarylevel').value = schedule.level_applied;

            // Format the date and time
            document.getElementById('Summarydate').value = formatDate(schedule.schedule_date);
            document.getElementById('Summarytime').value = formatTime(schedule.schedule_time);

            // Fetch team members' areas
            fetchTeamAreas(schedule.schedule_id);

            // Fetch team members' results
            fetchTeamResults(schedule.schedule_id);

            var resultSection = document.getElementById('result-section');
            var resultContainer = document.getElementById('result-container');

            // Remove any existing result dropdown
            if (resultContainer.firstChild) {
                resultContainer.removeChild(resultContainer.firstChild);
            }

            // Check if the team leader's area is blank
            if (schedule.area && schedule.area.trim() !== '') {
                // If the area is not blank, show the result section and add the dropdown
                resultSection.style.display = 'block';

                // Create and add the result dropdown
                var resultDropdown = document.createElement('select');
                resultDropdown.setAttribute('style', 'cursor: pointer;');
                resultDropdown.setAttribute('class', 'result');
                resultDropdown.setAttribute('id', 'result');
                resultDropdown.setAttribute('name', 'result');
                resultDropdown.setAttribute('required', 'true');

                var options = [
                    { value: '', text: 'SELECT RESULT' },
                    { value: 'Ready', text: 'Ready' },
                    { value: 'Needs Improvement', text: 'Needs Improvement' },
                    { value: 'Revisit', text: 'Revisit' }
                ];

                options.forEach(function(optionData) {
                    var option = document.createElement('option');
                    option.value = optionData.value;
                    option.textContent = optionData.text;
                    resultDropdown.appendChild(option);
                });

                resultContainer.appendChild(resultDropdown);
            } else {
                // If the area is blank, hide the result section and do not add the dropdown
                resultSection.style.display = 'none';
            }

            document.getElementById('Summarypopup').style.display = 'block';
        }

        function fetchTeamAreas(schedule_id) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_team_areas.php?schedule_id=' + schedule_id, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var areas = JSON.parse(xhr.responseText);
                    document.getElementById('areas').value = areas.join('\n');

                    console.log("Areas: ", areas); // Debugging
                }
            };
            xhr.send();
        }

        function fetchTeamResults(schedule_id) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_team_results.php?schedule_id=' + schedule_id, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var results = JSON.parse(xhr.responseText);
                    document.getElementById('results').value = results.join('\n');

                    console.log("Results: ", results); // Debugging
                }
            };
            xhr.send();
        }

        function SummaryclosePopup() {
            document.getElementById('Summarypopup').style.display = 'none';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        function openNdaPopup(fullName, teamId) {
            document.getElementById('nda_internal_accreditor').value = fullName;
            document.getElementById('nda_team_id').value = teamId;
            document.getElementById('ndaPopup').style.display = 'block';
            document.getElementById('termsModal').style.display = 'none';
        }

        function closeNdaPopup() {
            document.getElementById('ndaPopup').style.display = 'none';
        }

        function approveAssessmentPopup(member) {
            document.getElementById('approve_team_id').value = member.team_id;
            document.getElementById('approve_assessment_file').value = member.assessment_file;

            document.getElementById('approveAssessmentPopup').style.display = 'block';
        }

        function closeApproveAssessmentPopup() {
            document.getElementById('approveAssessmentPopup').style.display = 'none';
        }

        // Close modals when clicking outside of them
        window.onclick = function(event) {
            var modals = [
                document.getElementById('popup'),
                document.getElementById('Summarypopup'),
                document.getElementById('approveAssessmentPopup'),
                document.getElementById('ndaPopup')
            ];

            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            <?php foreach ($schedules as $index => $schedule): ?>
                var modal<?php echo $index; ?> = document.getElementById('myModal-<?php echo $index; ?>');
                var btn<?php echo $index; ?> = document.getElementById('openModalBtn-<?php echo $index; ?>');
                var span<?php echo $index; ?> = document.getElementById('closeModalBtn-<?php echo $index; ?>');
                
                // Open modal
                btn<?php echo $index; ?>.onclick = function() {
                    modal<?php echo $index; ?>.style.display = "block";
                }

                // Close modal
                span<?php echo $index; ?>.onclick = function() {
                    modal<?php echo $index; ?>.style.display = "none";
                }
            <?php endforeach; ?>
        });

        function addAreaDropdown(divId, teamMemberId) {
    var container = document.getElementById(divId);
    var newDiv = document.createElement('div');
    newDiv.classList.add('dropdown-container');
    newDiv.style.display = 'flex';
    newDiv.style.alignItems = 'center';
    newDiv.style.marginBottom = '10px'; // Adds space between dropdowns

    var newSelect = document.createElement('select');
    newSelect.name = 'area[' + teamMemberId + '][]'; // Ensure array format
    newSelect.classList.add('area-select');
    newSelect.required = true;
    newSelect.onchange = updateAreaOptions;

    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select Area';
    defaultOption.disabled = true;
    defaultOption.selected = true;
    newSelect.appendChild(defaultOption);

    <?php foreach ($areas as $id => $area_name): ?>
        var option = document.createElement('option');
        option.value = '<?php echo $id; ?>';
        option.text = 'Area <?php echo intToRoman($id); ?> - <?php echo htmlspecialchars($area_name); ?>';
        newSelect.appendChild(option);
    <?php endforeach; ?>

    newDiv.appendChild(newSelect);

    var removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.style.border = 'none';
    removeButton.style.background = 'none';
    removeButton.style.cursor = 'pointer';
    removeButton.style.paddingLeft = '8px';
    
    var removeIcon = document.createElement('i');
    removeIcon.classList.add('fa-solid', 'fa-circle-minus');
    removeIcon.style.color = 'red';
    removeIcon.style.fontSize = '25px';
    
    removeButton.appendChild(removeIcon);
    removeButton.onclick = function() {
        container.removeChild(newDiv);
        updateAreaOptions();
    };

    newDiv.appendChild(removeButton);
    container.appendChild(newDiv);

    // Update area options
    updateAreaOptions();
}


        function updateAreaOptions() {
            // Get all dropdowns with the class 'area-select'
            var selects = document.querySelectorAll('.area-select');

            // Get all selected values
            var selectedValues = Array.from(selects).map(function(select) {
                return select.value;
            });

            // Loop through each dropdown
            selects.forEach(function(select) {
                // Loop through each option in the dropdown
                Array.from(select.options).forEach(function(option) {
                    // Disable the option if it's already selected in another dropdown, but allow it if it's selected in the current dropdown
                    if (selectedValues.includes(option.value) && option.value !== select.value && option.value !== '') {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }
        
        document.getElementById('agreeTermsCheckbox').addEventListener('change', function() {
            var acceptButton = document.getElementById('acceptTerms');
            if (this.checked) {
                acceptButton.disabled = false;
                acceptButton.classList.remove('disabled');
            } else {
                acceptButton.disabled = true;
                acceptButton.classList.add('disabled');
            }
        });

        document.getElementById('closeTermsBtn').addEventListener('click', function() {
                document.getElementById('termsModal').style.display = 'none';
            });

            document.getElementById('sign-button').addEventListener('click', function() {
                document.getElementById('agreeTermsCheckbox').checked = false;
                document.getElementById('termsModal').style.display = 'block';
            });
    </script>
</body>
</html>

<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin') {

    $is_admin = true;

    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php') {
        header("Location: admin_sidebar.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php') {
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

$year = isset($_GET['year']) ? $_GET['year'] : '';
$college = isset($_GET['college']) ? $_GET['college'] : '';

// Fetch list of colleges
$colleges = $conn->query("SELECT DISTINCT college_name FROM college ORDER BY college_name");

// Fetch schedule data
$sql = "SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, c.college_name, p.program_name 
        FROM schedule s 
        JOIN college c ON s.college_code = c.code
        JOIN program p ON s.program_id = p.id
        WHERE s.schedule_status NOT IN ('pending')";
$whereClauses = [];
if ($year) {
    $whereClauses[] = "YEAR(s.schedule_date) = $year";
}
if ($college) {
    $whereClauses[] = "c.college_name = '$college'";
}
if (!empty($whereClauses)) {
    $sql .= " AND " . implode(' AND ', $whereClauses);
}
$sql .= " ORDER BY 
            CASE 
                WHEN s.schedule_status = 'finished' THEN 1 
                ELSE 0 
            END, 
            s.schedule_date, 
            s.schedule_time";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
</head>
<style>
    .hidden {
        display: none;
    }

    .schedule-holder {
        display: flex;
        justify-content: space-around;
        margin-top: 10px;
    }

    .schedule-holder div {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .schedule-holder div svg {
        margin: 0px 5px;
    }

    .schedule-holder div:first-child {
        flex: 2;
    }

    .no-schedule-prompt {
        display: flex;
        height: 600px;
        justify-content: center;
        align-items: center;
        font-size: 1.5rem;
        font-weight: bolder;
        color: #E5E5E5;
    }

    .schedule-modal-container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 2px solid !important;
        border-color: #AFAFAF !important;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: left;
    }

    .result-schedule-modal-container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 2px solid !important;
        border-color: #AFAFAF !important;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: left;
        opacity: 50%;
        transition: all .3s ease;
    }

    .result-schedule-modal-container:hover {
        opacity: 100%;
    }

    .level-applied-holder {
        width: max-content;
        padding: 5px 15px;
        background-color: #FFF1C0;
        border-radius: 5px;
        font-weight: 500;
        margin-top: 10px;
        color: #575757;
    }

    .result-level-applied-holder {
        width: max-content;
        padding: 5px 15px;
        border-radius: 5px;
        font-weight: 500;
        margin-top: 10px;
        color: #575757;
    }

    .result-passed-color {
        background-color: #b5ffc8;
    }

    .result-failed-color {
        background-color: #ffdee3;
    }

    .level-status-holder {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .finished-buttons {
        display: flex;
        align-items: center;
    }

    .finished-schedule {
        border: 1px solid !important;
        border-color: #AFAFAF !important;
    }

    .finished-buttons button {
        padding: 5px 10px;
        margin: 0 5px;
        text-align: center;
        height: fit-content;
        border-radius: 5px;
        font-weight: bold;
    }


    #retain-button {
        color: #B73033;
        border: 1px solid #B73033;
        transition: background-color .3s ease;
    }

    #retain-button:hover {
        color: #fff;
        background-color: #B73033;
        border: 1px solid #DC7171;
    }

    #pass-button {
        color: #006118;
        border: 1px solid #006118;
        background-color: #D4FFDF;
        transition: background-color .3s ease;
    }

    #pass-button:hover {
        border: 1px solid #006118;
        background-color: #76FA97;
        color: #006118;
    }

    #retain-button-active {
        color: #fff;
        background-color: #B73033;
        border: 1px solid #DC7171;
    }

    #pass-button-active {
        border: 1px solid #006118;
        background-color: #76FA97;
        color: #006118;
    }

    .hide-result {
        display: none;
    }

    .result-schedule {
        border: 1px solid !important;
        opacity: 50%;
        border-color: #AFAFAF !important;
    }

    .status-holder {
        display: inline-block;
        color: #B73033;
        border: 1px solid #B73033;
        padding: 5px 10px;
        margin: 0;
        text-align: center;
        height: fit-content;
        border-radius: 5px;
        font-weight: bold;
    }

    .schedule-wrapper {
        width: 400px;
        padding: 20px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 20px;
        margin: 0px 5px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .schedule-wrapper h3 {
        font-size: 1.25rem;
        margin-bottom: 10px;
    }

    .hidden-status-holder {
        display: none;
    }

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
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 400px;
        border-radius: 10px;
        text-align: center;
    }

    .modal-buttons {
        display: flex;
        justify-content: end;
        margin-top: 20px;
    }

    .modal-buttons button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color .3s ease;
    }

    .modal-buttons .yes-btn {
        background-color: #e74c3c;
        color: white;
    }

    .modal-buttons .positive {
        background-color: rgb(49, 204, 85) !important;
        color: white;
    }

    .modal-buttons .positive:hover {
        background-color: rgb(40, 167, 69) !important;
        color: white;
    }

    .modal-buttons .no-btn {
        color: #575757;
        background-color: white;
    }

    .modal-buttons .yes-btn:hover {
        background-color: #c9302c;
    }

    .modal-buttons .no-btn:hover {
        background-color: white;
        text-decoration: underline;
    }
</style>

<body>
    <div class="wrapper">
        <!-- Main Content -->
        <div class="main bg-white">
            <div class="hair" style="height: 15px; background: #973939;"></div>
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
                        <a href="#" class="sidebar-link-active">
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
                        <a href="#" class="sidebar-link">
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
            <div class="container text-center ps-5">
                <p class="fw-bold mt-5 mb-5" style="font-size: 1.5rem">SCHEDULE LIST</p>
                <div class="row d-flex justify-content-center">
                    <div class="col-12 bg-white">
                        <div class="filter text-end mx-4">
                            <form method="get" action="">
                                <label for="year">Sort by Year:</label>
                                <select class="rounded-2 p-2" id="year" name="year" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <?php
                                    $years = $conn->query("SELECT DISTINCT YEAR(schedule_date) AS year FROM schedule ORDER BY year DESC");
                                    while ($row = $years->fetch_assoc()) {
                                        $selected = ($year == $row['year']) ? 'selected' : '';
                                        echo "<option value='{$row['year']}' $selected>{$row['year']}</option>";
                                    }
                                    ?>
                                </select>

                                <label for="college">Sort by College:</label>
                                <select class="rounded-2 p-2" id="college" name="college" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <?php
                                    while ($row = $colleges->fetch_assoc()) {
                                        $selected = ($college == $row['college_name']) ? 'selected' : '';
                                        echo "<option value='{$row['college_name']}' $selected>{$row['college_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                        <div class="row row justify-content-center mt-3">
                            <div class="schedule-wrapper col-md-4">
                                <h3>UPCOMING</h3>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $schedule_date = date("F j, Y", strtotime($row['schedule_date'])); // Format the date as "Month Day, Year"
                                        $schedule_time = date("g:i A", strtotime($row['schedule_time']));  // Format the time as "hour:minute AM/PM"

                                        if ($row['schedule_status'] == 'approved') {
                                            $approved_class = ($row['schedule_status'] == 'approved') ? 'status-holder' : 'hidden-status-holder';
                                            echo "<div class='schedule-modal-container'>
                                                <div>
                                                    <div>{$row['college_name']}</div>
                                                    <div>
                                                        <h5 class='fw-bold'>{$row['program_name']}</h5>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class='level-status-holder'>
                                                        <div class='level-applied-holder'>
                                                            <label>Level Applied:</label> 
                                                            {$row['level_applied']}
                                                        </div>
                                                        <div class='$approved_class'>
                                                            <label>UPCOMING</label> 
                                                        </div>
                                                    </div>
                                                    <div class='schedule-holder'>
                                                        <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-calendar-week' viewBox='0 0 16 16'>
                                                            <path d='M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z'/>
                                                            <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z'/>
                                                            </svg> {$schedule_date}</div>
                                                        <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clock' viewBox='0 0 16 16'>
                                                            <path d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/>
                                                            <path d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/>
                                                            </svg> {$schedule_time}</div>
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                    }
                                } else {
                                    echo "<p class='no-schedule-prompt'>NO UPCOMING SCHEDULE</p>";
                                }
                                ?>
                            </div>
                            <div class="schedule-wrapper col-md-4">
                                <h3>FINISHED</h3>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0); // Reset result pointer to the beginning
                                    while ($row = $result->fetch_assoc()) {
                                        $schedule_date = date("F j, Y", strtotime($row['schedule_date'])); // Format the date as "Month Day, Year"
                                        $schedule_time = date("g:i A", strtotime($row['schedule_time']));  // Format the time as "hour:minute AM/PM"

                                        if ($row['schedule_status'] == 'finished') {
                                            echo "<div class='schedule-modal-container finished-schedule' data-schedule-id='{$row['id']}'>
                                                    <div>
                                                        <div>{$row['college_name']}</div>
                                                        <div>
                                                            <h5 class='fw-bold'>{$row['program_name']}</h5>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class='level-status-holder'>
                                                            <div class='level-applied-holder'>
                                                                <label>Level Applied:</label> 
                                                                {$row['level_applied']}
                                                            </div>";

                                            // Show 'finished-buttons' only if $is_admin is true
                                            if ($is_admin) {
                                                echo "<div class='finished-buttons'>
                                                            <button type='button' id='retain-button'>FAIL</button>
                                                            <button type='button' id='pass-button'>PASS</button>
                                                </div>";
                                            }

                                            echo "</div>
                                                <div class='schedule-holder'>
                                                    <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-calendar-week' viewBox='0 0 16 16'>
                                                    <path d='M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z'/>
                                                        <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z'/>
                                                        </svg> {$schedule_date}</div>
                                                    <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clock' viewBox='0 0 16 16'>
                                                            <path d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/>
                                                            <path d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/>
                                                        </svg> {$schedule_time}</div>
                                                </div>
                                            </div>";
                                        }
                                    }
                                } else {
                                    echo "<p class='no-schedule-prompt'>NO FINISHED SCHEDULE</p>";
                                }
                                ?>
                            </div>
                            <div class="schedule-wrapper col-md-4">
                                <h3>RESULT</h3>
                                <!-- Add your result content here -->
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0); // Reset result pointer to the beginning
                                    while ($row = $result->fetch_assoc()) {
                                        $schedule_date = date("F j, Y", strtotime($row['schedule_date'])); // Format the date as "Month Day, Year"
                                        $schedule_time = date("g:i A", strtotime($row['schedule_time']));  // Format the time as "hour:minute AM/PM"

                                        if ($row['schedule_status'] == 'failed' || $row['schedule_status'] == 'passed') {
                                            $isPassed = true;
                                            if ($row['schedule_status'] == 'failed')
                                                $isPassed = false;

                                            $hideFail = $isPassed == false ? '' : 'hide-result';
                                            $hidePass = $isPassed == true ? '' : 'hide-result';
                                            $color = $isPassed == true ? 'result-passed-color' : 'result-failed-color';

                                            echo "<div class='result-schedule-modal-container  $color'>
                                                    <div>
                                                        <div>{$row['college_name']}</div>
                                                        <div>
                                                            <h5 class='fw-bold'>{$row['program_name']}</h5>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class='level-status-holder'>
                                                            <div class='result-level-applied-holder'>
                                                                <label>Level Applied:</label> 
                                                                {$row['level_applied']}
                                                            </div>";

                                            // Only show 'finished-buttons' if $is_admin is true
                                            if ($is_admin) {
                                                echo "<div class='finished-buttons'>
                                                    <button type='none' id='retain-button-active' class='$hideFail' disabled='disabled'>FAILED</button>
                                                    <button type='none' id='pass-button-active' class='$hidePass' disabled='disabled'>PASSED</button>
                                                </div>";
                                            }

                                            echo "</div>
                                                <div class='schedule-holder'>
                                                    <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-calendar-week' viewBox='0 0 16 16'>
                                                            <path d='M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z'/>
                                                            <path d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z'/>
                                                        </svg> {$schedule_date}</div>
                                                    <div><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clock' viewBox='0 0 16 16'>
                                                            <path d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/>
                                                            <path d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/>
                                                        </svg> {$schedule_time}</div>
                                                </div>
                                            </div>";
                                        }
                                    }
                                } else {
                                    echo "<p class='no-schedule-prompt'>NO RESULT SCHEDULE</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Approve Modal -->
        <div id="approveModal" class="modal">
            <div class="modal-content">
                <h4 id="modalText">Are you sure you want to approve this registration?</h4>
                <form id="approveForm" action="dashboard_update_schedule_status.php" method="post">
                    <input type="hidden" name="id" id="approveScheduleId">
                    <input type="hidden" name="status" id="approveStatus">
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeApproveModal()">NO</button>
                        <button type="submit" class="yes-btn positive">YES</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.finished-buttons button').forEach(function(button) {
                button.addEventListener('click', function() {
                    const scheduleContainer = this.closest('.schedule-modal-container');
                    if (scheduleContainer) {
                        const scheduleId = scheduleContainer.dataset.scheduleId;
                        const status = this.id === 'retain-button' ? 'failed' : 'passed';
                        const actionText = status === 'failed' ? 'fail' : 'pass';

                        // Set modal text and form data
                        document.getElementById('modalText').textContent = `Are you sure you want to ${actionText} this program?`;
                        document.getElementById('approveScheduleId').value = scheduleId;
                        document.getElementById('approveStatus').value = status;

                        // Open the modal
                        document.getElementById('approveModal').style.display = 'block';
                    } else {
                        console.error('Schedule container not found');
                    }
                });
            });
        });

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }
    </script>

</body>

</html>
<?php
$conn->close();
?>
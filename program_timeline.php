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
    if (basename($_SERVER['PHP_SELF']) !== 'program_timeline.php') {
        header("Location: program_timeline.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'program_timeline.php') {
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

// Fetch colleges
$colleges = [];
$sql = "SELECT code, college_name FROM college";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['college_code'])) {
        // Fetch distinct programs for a specific college
        $college_code = $_POST['college_code'];

        $sql = "SELECT DISTINCT program_name FROM program WHERE college_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $college_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $options = "";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $options .= "<div class='select-item' data-value='" . htmlspecialchars($row['program_name']) . "'>" . htmlspecialchars($row['program_name']) . "</div>";
            }
        } else {
            $options .= "<div class='select-item'>No programs available</div>";
        }

        echo $options;
        $stmt->close();
        exit;  // Exit to prevent further HTML output
    }

    if (isset($_POST['program_names'])) {
        // Fetch program level history for specific programs
        $program_names = json_decode($_POST['program_names'], true);
        $events = []; // Array to store events for the timeline

        foreach ($program_names as $program_name) {
            $sql = "SELECT plh.program_level, plh.date_received 
                    FROM program_level_history plh
                    JOIN program p ON plh.program_id = p.id
                    WHERE p.program_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $program_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $programEvents = [];
                while ($row = $result->fetch_assoc()) {
                    $programEvents[] = [
                        'label' => $row['program_level'],
                        'date' => $row['date_received']
                    ];
                }
                $events[$program_name] = $programEvents; // Store events per program
            }
            $stmt->close();
        }

        // Return events data as JSON
        echo json_encode($events);
        exit;  // Exit to prevent further HTML output
    }
}

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

// SQL query to count the number of open assessments (accepted status, excluding 'cancelled' and 'finished' schedules)
$sql_assessment_count = "
    SELECT COUNT(*) 
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    WHERE t.internal_users_id = ? 
    AND t.status = 'accepted'
    AND s.schedule_status NOT IN ('cancelled', 'finished')
";

$stmt_assessment_count = $conn->prepare($sql_assessment_count);
$stmt_assessment_count->bind_param("s", $user_id);
$stmt_assessment_count->execute();
$stmt_assessment_count->bind_result($assessment_count);
$stmt_assessment_count->fetch();
$stmt_assessment_count->close();
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="index.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .program-history {
            margin-bottom: 20px;
        }

        .custom-select {
            width: 300px;
            position: relative;
            display: inline-block;
        }

        .select-items {
            position: absolute;
            background-color: #f9f9f9;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 99;
            border: 1px solid #ccc;
            border-radius: 8px;
            max-height: 150px;
            overflow-y: auto;
            display: none;
        }

        .select-item {
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .select-item:hover {
            background: #9B0303;
            color: white;
        }

        .same-as-selected {
            background: #9B0303;
            color: white;
        }

        /* Styles for legends */
        .legend-container {
            display: flex;
            justify-content: flex-end;
            /* Align items to the right */
            align-items: center;
            margin-bottom: 10px;
            position: relative;
            /* Needed for positioning the tooltip */
        }

        .legend-lines {
            display: flex;
            gap: 5px;
            /* Space between lines */
            margin-left: 10px;
            /* Space between legend text and lines */
        }

        .legend-line,
        .legend-text,
        .info-icon {
            position: relative;
            height: 20px;
            /* Height of the line, same as the LEGENDS text */
            border-radius: 5px;
            /* Rounded corners */
            cursor: pointer;
        }

        .legend-line,
        .legend-text {
            width: 50px;
        }

        .legend-text {
            width: auto;
            margin-right: 5px;
        }

        .red-line {
            background-color: #B73033;
            /* Color for 'Not Accreditable' */
        }

        .green-line {
            background-color: #76FA97;
            /* Color for 'Candidate' */
        }

        .grey-line {
            background-color: #CCCCCC;
            /* Color for 'PSV' */
        }

        .yellow-line {
            background-color: #FDC879;
            /* Color for Levels 1-4 */
        }

        .info-icon {
            background: transparent;
        }

        .info-icon img {
            width: 20px;
            height: 20px;
        }

        /* Tooltip styles for entire legend */
        .legend-tooltip {
            visibility: hidden;
            width: 350px;
            color: #fff;
            text-align: left;
            border-radius: 20px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            top: 150%;
            /* Position above the element */
            right: 0;
            opacity: 0;
            transition: opacity 0.3s;
            background-color: #FFFFFF;
            border: 1px solid #575757;
        }

        /* Show tooltip on hover over the legend-container */
        .legend-container:hover .legend-tooltip {
            visibility: visible;
            opacity: 1;
        }

        /* Tooltip content lines */
        .tooltip-line,
        .tooltip-line1 {
            display: flex;
            align-items: center;
            color: black;
            padding: 10px;
        }

        .tooltip-line {
            margin-bottom: 10px;
        }

        .tooltip-color {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 40px;
            border-radius: 5px;
            margin-right: 5px;
            color: #000;
            font-weight: bold;
        }

        .red-tooltip {
            background-color: #B73033;
        }

        .green-tooltip {
            background-color: #76FA97;
        }

        .grey-tooltip {
            background-color: #CCCCCC;
        }

        .yellow-tooltip {
            background-color: #FDC879;
        }

        .notification-counter {
            color: #E6A33E;
            /* Text color */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="main bg-white">
            <div class="hair"></div>
            <div class="container">
                <div class="header">
                    <div class="headerLeft">
                        <div class="USePData">
                            <img class="USeP" src="images/USePLogo.png" height="36">
                            <div style="height: 0px; width: 16px;"></div>
                            <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                            <div style="height: 0px; width: 16px;"></div>
                            <div class="headerLeftText">
                                <div class="onedata">
                                    <h><span class="one">One</span>
                                        <span class="datausep">Data.</span>
                                        <span class="one">One</span>
                                        <span class="datausep">USeP.</span>
                                    </h>
                                </div>
                                <h>Accreditor Portal</h>
                            </div>
                        </div>
                    </div>

                    <div class="headerRight">
                        <div class="QAD">
                            <div class="headerRightText">
                                <h>Quality Assurance Division</h>
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
                            <?php if ($totalPendingSchedules > 0 && $is_admin): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
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
                                <?php if ($totalPendingSchedules > 0 && $is_admin): ?>
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
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Assessment</span>
                            <?php if ($assessmentCount > 0 && $is_admin): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                            <?php if ($assessment_count > 0): ?>
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
                                <?php if ($assessmentCount > 0 && $is_admin): ?>
                                    <span class="notification-counter"><?= $assessmentCount; ?></span>
                                <?php endif; ?>
                                <?php if ($assessment_count > 0): ?>
                                    <span class="notification-counter"><?php echo $assessment_count; ?></span>
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
                            <?php if (($transferRequestCount > 0) && $is_admin || ($totalPendingUsers > 0 && $is_admin)): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
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
                                <?php if ($totalPendingUsers > 0 && $is_admin): ?>
                                    <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>
                                <?php if ($transferRequestCount > 0 && $is_admin): ?>
                                    <span class="notification-counter"><?= $transferRequestCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link-active">
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
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'admin_sidebar.php' : 'internal.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">Profile</span>
                            </a>
                            <a href="<?php echo $is_admin === false ? 'internal_notification.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Notifications</span>
                                <?php if ($notification_count > 0): ?>
                                    <span class="notification-counter"><?php echo $notification_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="logout.php" class="sidebar-link">
                                <span style="margin-left: 8px;">Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <div style="height: 10px; width: 0px;"></div>
            <div class="container">
                <p style="text-align: center; font-size: 30px"><strong>PROGRAM LEVEL HISTORY TIMELINE</strong></p>
                <div style="height: 30px;"></div>
                <div class="college-program">
                    <div class="college-program-history">
                        <select id="collegeSelect" onchange="loadPrograms(this.value)">
                            <option value="">SELECT COLLEGE</option>
                            <?php
                            foreach ($colleges as $college) {
                                echo "<option value='" . $college['code'] . "'>" . htmlspecialchars($college['college_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="college-program-history">
                        <div class="select-selected">SELECT PROGRAM/S</div>
                        <div class="select-items">
                            <!-- Options will be populated based on selected college -->
                        </div>
                        <div class="custom-select">
                        </div>
                    </div>
                </div>
                <div class="legend-container">
                    <div class="legend-text">
                        <p><strong>LEGENDS</strong></p>
                    </div>
                    <div class="legend-lines">
                        <div class="legend-line red-line"></div>
                        <div class="legend-line green-line"></div>
                        <div class="legend-line grey-line"></div>
                        <div class="legend-line yellow-line"></div>
                        <div class="info-icon">
                            <img src="images/info-circle.png" alt="Info">
                        </div>
                    </div>
                    <div class="legend-tooltip">
                        <div class="tooltip-line">
                            <div class="tooltip-color red-tooltip" style="margin-right: 30px;">NA</div><strong>NOT ACCREDITABLE</strong>
                        </div>
                        <div class="tooltip-line">
                            <div class="tooltip-color green-tooltip" style="margin-right: 30px;">CAN</div><strong>CANDIDATE</strong>
                        </div>
                        <div class="tooltip-line">
                            <div class="tooltip-color grey-tooltip" style="margin-right: 30px;">PSV</div><strong>PRE-SURVEY VISIT</strong>
                        </div>
                        <div class="tooltip-line1">
                            <div class="tooltip-color yellow-tooltip" style="margin-right: 30px;">LVL 1-4</div><strong>LEVEL ACCREDITED</strong>
                        </div>
                    </div>
                </div>
                <div class="orientation5" id="chartContainer">
                    <p style="text-align: center; font-size: 20px"><strong>PLEASE SELECT COLLEGE AND PROGRAM/S</strong></p>
                </div>
            </div>
            <button class="export-button" id="exportPdfButton"><span style="font-weight: bold; color: #575757; font-size: 16px; cursor: pointer;">EXPORT</span><img style="margin-left: 5px;" src="images/export.png"></button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>


    <script>
        function loadPrograms(collegeCode) {
            if (collegeCode === "") {
                document.querySelector('.select-items').innerHTML = "<div>Select programs</div>";
                document.getElementById('programHistory').innerHTML = "";
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true); // Send POST request to the same page
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.querySelector('.select-items').innerHTML = xhr.responseText;
                    setupCustomSelect();
                }
            };
            xhr.send("college_code=" + encodeURIComponent(collegeCode));
        }

        function setupCustomSelect() {
            const selectItems = document.querySelector('.select-items');
            const selectedDiv = document.querySelector('.select-selected');
            const items = selectItems.getElementsByClassName('select-item');
            const maxSelection = 5; // Maximum number of selectable programs

            Array.from(items).forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    item.classList.toggle('same-as-selected');

                    // Get selected values
                    let selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function(selectedItem) {
                        return selectedItem.dataset.value;
                    });

                    // Limit selection to maxSelection
                    if (selectedValues.length > maxSelection) {
                        alert(`You can only select up to ${maxSelection} programs.`);
                        item.classList.remove('same-as-selected');
                        return;
                    }

                    // Update display
                    if (selectedValues.length > 0) {
                        const displayedText = selectedValues.length > 1 ? `${selectedValues[0]} and ${selectedValues.length - 1} more` : selectedValues[0];
                        selectedDiv.textContent = displayedText;
                    } else {
                        selectedDiv.textContent = 'Select programs';
                    }

                    loadProgramHistories(selectedValues);
                });
            });

            // Toggle dropdown
            selectedDiv.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllSelect();
                selectItems.style.display = selectItems.style.display === 'block' ? 'none' : 'block';
            });
        }


        function loadProgramHistories(selectedPrograms) {
            if (selectedPrograms.length === 0) {
                document.getElementById('chartContainer').innerHTML = "";
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true); // Send POST request to the same page
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    try {
                        const allEvents = JSON.parse(xhr.responseText);
                        renderTimelineCharts(allEvents, selectedPrograms);
                    } catch (e) {
                        console.error("Failed to parse JSON response", e);
                        document.getElementById('programHistory').innerHTML = xhr.responseText;
                    }
                }
            };
            xhr.send("program_names=" + encodeURIComponent(JSON.stringify(selectedPrograms)));
        }

        function renderTimelineCharts(eventsGroupedByProgram, selectedPrograms) {
            const chartContainer = document.getElementById('chartContainer');

            // Clear previous charts
            chartContainer.innerHTML = '';

            Object.keys(eventsGroupedByProgram).forEach((programName, programIndex) => {
                const events = eventsGroupedByProgram[programName];

                // Create a container div for each program
                const programContainer = document.createElement('div');
                programContainer.classList.add('orientation4');

                // Create a label for each program
                const programLabel = document.createElement('h3');
                programLabel.textContent = `${programName}`;
                programContainer.appendChild(programLabel);

                // Create a new canvas element for each program
                const canvas = document.createElement('canvas');
                canvas.id = `timelineChart${programIndex}`;
                canvas.style.height = '200px';
                canvas.style.width = '100%';
                programContainer.appendChild(canvas);

                // Append the program container to the chart container
                chartContainer.appendChild(programContainer);

                // Create a chart for each canvas
                if (events.length === 0) {
                    return;
                }

                // Convert date strings to Date objects
                events.forEach(event => {
                    event.x = new Date(event.date);
                    event.y = 0;
                    event.label = `${event.label}; ${new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
                });

                // Determine the timeline range
                const dates = events.map(event => event.x);
                const minDate = new Date(Math.min.apply(null, dates));
                const maxDate = new Date(Math.max.apply(null, dates));
                minDate.setFullYear(minDate.getFullYear() - 1);
                maxDate.setFullYear(maxDate.getFullYear() + 6);

                // Prepare the dataset for Chart.js
                const dataset = {
                    datasets: [{
                        label: 'Program Timeline',
                        data: events,
                        backgroundColor: 'blue',
                        pointRadius: 0, // Hide the scatter points
                        pointHoverRadius: 0,
                        showLine: false
                    }]
                };

                // Get the context of the new canvas element
                const ctx = canvas.getContext('2d');

                // Define colors for each level
                const levelColors = {
                    'Not Accreditable': ' #B73033', // Red
                    'Candidate': '#76FA97', // Green
                    'PSV': '#CCCCCC', // Grey
                    '1': '#FDC879', // Yellow
                    '2': '#FDC879', // Yellow
                    '3': '#FDC879', // Yellow
                    '4': '#FDC879' // Yellow
                };

                // Define abbreviations for each level
                const levelAbbreviations = {
                    'Not Accreditable': 'NA',
                    'Candidate': 'CAN',
                    'PSV': 'PSV',
                    '1': 'LVL 1',
                    '2': 'LVL 2',
                    '3': 'LVL 3',
                    '4': 'LVL 4'
                };

                // Function to draw rounded rectangles with border
                function drawRoundedRectWithBorder(ctx, x, y, width, height, radius, borderColor) {
                    ctx.beginPath();
                    ctx.moveTo(x + radius, y);
                    ctx.lineTo(x + width - radius, y);
                    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                    ctx.lineTo(x + width, y + height - radius);
                    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                    ctx.lineTo(x + radius, y + height);
                    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                    ctx.lineTo(x, y + radius);
                    ctx.quadraticCurveTo(x, y, x + radius, y);
                    ctx.closePath();

                    // Fill the rectangle
                    ctx.fill();

                    // Draw the border
                    ctx.strokeStyle = borderColor;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }

                // Define a plugin to draw vertical lines with labels and borders
                const verticalLinePlugin = {
                    id: 'verticalLinePlugin',
                    beforeDraw: chart => {
                        const {
                            ctx,
                            chartArea: {
                                top,
                                bottom
                            },
                            scales: {
                                x,
                                y
                            }
                        } = chart;
                        ctx.save();

                        events.forEach(event => {
                            const level = event.label.split(';')[0]; // Extract the level from the label
                            ctx.fillStyle = levelColors[level] || 'black'; // Use black as default color if not found

                            const xPosition = x.getPixelForValue(event.x);
                            const lineWidth = 60;
                            const lineHeight = bottom - y.getPixelForValue(0);
                            const lineTop = y.getPixelForValue(0);

                            // Define margins
                            const marginBetweenLines = 10; // Space between the first and second vertical lines
                            const marginBelowSecondLine = 5; // Space below the second vertical line

                            // Calculate the offset for the first rectangle
                            const offset = lineHeight / 2 + marginBetweenLines; // Increase offset to move the first rectangle up

                            // Draw the first rectangle for the level, adjusted upward, with border
                            ctx.fillStyle = levelColors[level] || 'black';
                            drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop - offset, lineWidth, 70, 5, '#AFAFAF');

                            // Draw the level abbreviation inside the first rectangle
                            ctx.fillStyle = 'black'; // Text color
                            ctx.font = 'bold 16px Arial'; // Bold font style
                            ctx.textAlign = 'center'; // Center the text
                            ctx.textBaseline = 'middle';
                            ctx.fillText(levelAbbreviations[level] || '', xPosition, lineTop - offset + 35); // Centered at 35 pixels

                            const secondRectOffset = -5; // Increase or decrease this value to move the rectangle

                            // Draw the second rectangle for the date with margin below, with border
                            const dateHeight = lineHeight * 0.6; // Increase the height of the second vertical line
                            ctx.fillStyle = '#FFFFFF'; // White background
                            drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset, lineWidth, dateHeight - marginBelowSecondLine, 5, '#AFAFAF');

                            // Draw the date inside the second rectangle, centered vertically
                            ctx.fillStyle = 'black'; // Text color
                            ctx.font = 'bold 14px Arial'; // Bold font style for date
                            ctx.textBaseline = 'middle'; // Center the text vertically
                            ctx.fillText(
                                new Date(event.date).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric'
                                }),
                                xPosition,
                                lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset + (dateHeight - marginBelowSecondLine) / 2
                            );
                        });

                        ctx.restore();
                    }
                };

                // Render the chart on the new canvas
                new Chart(ctx, {
                    type: 'scatter',
                    data: dataset,
                    options: {
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'year', // Set unit to year
                                    displayFormats: {
                                        year: 'YYYY' // Format the year
                                    }
                                },
                                min: minDate,
                                max: maxDate,
                                title: {
                                    display: true,
                                    text: 'Year'
                                }
                            },
                            y: {
                                display: false,
                                min: -1,
                                max: 1
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false // Disable default tooltips
                            },
                            datalabels: {
                                display: false // Hide default data labels
                            }
                        }
                    },
                    plugins: [verticalLinePlugin] // Add the vertical line plugin
                });
            });
        }








        function closeAllSelect() {
            var selectItems = document.querySelector('.select-items');
            selectItems.style.display = 'none';
        }

        document.addEventListener('click', closeAllSelect);

        document.getElementById('exportPdfButton').addEventListener('click', function() {
            const charts = document.querySelectorAll('canvas');
            const programNames = document.querySelectorAll('#chartContainer h3');

            const images = [];
            let count = 0;

            charts.forEach((chart, index) => {
                html2canvas(chart).then(canvas => {
                    images.push({
                        name: programNames[index].innerText,
                        data: canvas.toDataURL('image/png')
                    });
                    count++;
                    if (count === charts.length) {
                        console.log('All charts captured, sending to server');
                        sendImagesToServer(images);
                    }
                }).catch(err => console.error('Error capturing canvas:', err));
            });
        });

        function sendImagesToServer(images) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'program_timeline_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        console.log('PDF generated:', xhr.responseText);
                        downloadFile(xhr.responseText);
                    } else {
                        console.error('Failed to generate PDF:', xhr.statusText);
                    }
                }
            };
            xhr.send(JSON.stringify(images));
        }

        function downloadFile(fileName) {
            const link = document.createElement('a');
            link.href = 'program_timeline_download.php?file=' + encodeURIComponent(fileName);
            link.download = 'program_history.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>
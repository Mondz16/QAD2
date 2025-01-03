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
    if (isset($_POST['college_codes'])) {
        $collegeCodes = json_decode($_POST['college_codes'], true);
        $options = "";
        $programsGroupedByCollege = [];
    
        if (!empty($collegeCodes)) {
            $placeholders = implode(',', array_fill(0, count($collegeCodes), '?'));
            $sql = "SELECT p.program_name, p.college_code, plh.program_level, plh.date_received
                    FROM program p
                    LEFT JOIN program_level_history plh ON p.id = plh.program_id
                    WHERE p.college_code IN ($placeholders)
                    ORDER BY p.college_code ASC, p.program_name ASC, plh.date_received ASC";
            $stmt = $conn->prepare($sql);
    
            $types = str_repeat('s', count($collegeCodes));
            $stmt->bind_param($types, ...$collegeCodes);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $programName = htmlspecialchars($row['program_name']);
                    $collegeCode = $row['college_code'];
                    $programLevel = htmlspecialchars($row['program_level'] ?? 'N/A');
                    $dateReceived = htmlspecialchars($row['date_received'] ?? 'N/A');
    
                    // Populate the program dropdown options
                    $options .= "<div class='select-item' data-value='{$programName}'>{$programName}</div>";
    
                    // Group programs by college code
                    if (!isset($programsGroupedByCollege[$collegeCode])) {
                        $programsGroupedByCollege[$collegeCode] = [];
                    }
                    $programsGroupedByCollege[$collegeCode][] = [
                        'program_name' => $programName,
                        'program_level' => $programLevel,
                        'date_received' => $dateReceived
                    ];
                }
            } else {
                $options .= "<div class='select-item'>No programs available</div>";
            }
    
            $stmt->close();
        }
    
        echo json_encode([
            'options' => $options,
            'programs' => $programsGroupedByCollege
        ]);
        exit;
    }
    

    if (isset($_POST['program_names'])) {
        // Existing code to fetch program level history
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

// Fetch the total count of missing udas_assessment_file
$sqlMissingAssessmentsCount = "
    SELECT COUNT(*) AS total_missing_assessments
    FROM schedule s
    LEFT JOIN udas_assessment ua ON s.id = ua.schedule_id
    WHERE s.schedule_status = 'approved' 
      AND (ua.udas_assessment_file IS NULL OR ua.udas_assessment_file = '')
";
$Dresult = $conn->query($sqlMissingAssessmentsCount);
$Drow = $Dresult->fetch_assoc();
$totalMissingAssessments = $Drow['total_missing_assessments'];

$sqlPendingOrientationsCount = "
        SELECT COUNT(*) AS total_pending_orientations
        FROM orientation o
        WHERE o.orientation_status = 'pending'
    ";

    $Qresult = $conn->query($sqlPendingOrientationsCount);
    $Qrow = $Qresult->fetch_assoc();
    $totalPendingOrientations = $Qrow['total_pending_orientations'];
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

        .select-items, .college-select-items {
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

        .select-item, .college-select-item {
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .select-item:hover, .college-select-item:hover {
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
                            <?php if ($totalPendingSchedules > 0 || $totalPendingOrientations > 0 && $is_admin): ?>
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
                                <?php if ($totalPendingOrientations > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingSchedules; ?></span>
                                <?php endif; ?>
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
                                <?php if ($totalMissingAssessments > 0): ?>
                                    <span class="notification-counter"><?= $totalMissingAssessments; ?></span>
                                <?php endif; ?>
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
                            <a href="<?php echo $is_admin ? 'program_level.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Update Program Level</span>
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
                    <div class="college-select-selected">SELECT COLLEGE/S</div>
                        <div class="college-select-items">
                            <?php
                            foreach ($colleges as $college) {
                                echo "<div class='college-select-item' data-value='" . $college['code'] . "'>" . htmlspecialchars($college['college_name']) . "</div>";
                            }
                            ?>
                        </div>
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
                        <div class="legend-line green-line"></div>
                        <div class="legend-line grey-line"></div>
                        <div class="legend-line yellow-line"></div>
                        <div class="info-icon">
                            <img src="images/info-circle.png" alt="Info">
                        </div>
                    </div>
                    <div class="legend-tooltip">
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
        let currentSelectionType = 'college';
    // Function to generate acronym from a full program name
    function getAcronym(fullName) {
    const smallWords = ['in', 'and', 'with', 'of', 'the', 'for', 'at', 'by'];
    const acronym = fullName
        .split(/\s+/) // Split by any whitespace
        .filter(word => !smallWords.includes(word.toLowerCase()))
        .map(word => word.replace(/[^A-Za-z]/g, '').charAt(0).toUpperCase()) // Remove non-letters and get first letter
        .join('');
    console.log(`Program: ${fullName}, Acronym: ${acronym}`); // Debug log
    return acronym;
}


    
    // Function to handle multi-select for colleges
function setupCollegeMultiSelect() {
    const selectItems = document.querySelector('.college-program-history .college-select-items');
    const selectedDiv = document.querySelector('.college-program-history .college-select-selected');
    const items = selectItems.getElementsByClassName('college-select-item');
    const programDropdown = document.querySelector('.college-program-history .select-selected'); // Program dropdown element

    Array.from(items).forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.stopPropagation();
            item.classList.toggle('same-as-selected');

            // Get selected values and names
            let selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function (selectedItem) {
                return {
                    code: selectedItem.dataset.value, // College code
                    name: selectedItem.textContent.trim() // College name
                };
            });

            // Update display
            if (selectedValues.length > 0) {
                const displayedText = selectedValues.length > 1 
                    ? `${selectedValues[0].name} and ${selectedValues.length - 1} more` 
                    : selectedValues[0].name;
                selectedDiv.textContent = displayedText;
            } else {
                selectedDiv.textContent = 'SELECT COLLEGE/S';
            }

            // Disable program dropdown if more than one college is selected
            if (selectedValues.length > 1) {
                programDropdown.classList.add('disabled'); // Add a class to indicate it's disabled
                programDropdown.style.pointerEvents = 'none'; // Disable user interaction
                programDropdown.style.opacity = '0.5'; // Make it visually appear disabled
            } else {
                programDropdown.classList.remove('disabled'); // Remove the disabled class
                programDropdown.style.pointerEvents = 'auto'; // Enable user interaction
                programDropdown.style.opacity = '1'; // Restore opacity
            }

            // Extract only the college codes for backend processing
            const selectedCollegeCodes = selectedValues.map(college => college.code);

            // Load programs for selected colleges and pass selectedValues
            loadProgramsForColleges(selectedCollegeCodes, selectedValues);
        });
    });

    // Toggle dropdown
    selectedDiv.addEventListener('click', function (e) {
        e.stopPropagation();
        closeAllSelect();
        selectItems.style.display = selectItems.style.display === 'block' ? 'none' : 'block';
    });
}



function createTimeline(programsGroupedByCollege, selectedColleges) {
    const chartContainer = document.getElementById('chartContainer');

    // Clear previous content in chartContainer
    chartContainer.innerHTML = '';

    // Iterate through each selected college
    selectedColleges.forEach(selectedCollege => {
        const collegeCode = selectedCollege.code;
        const collegeName = selectedCollege.name;
        const programs = programsGroupedByCollege[collegeCode] || [];

        // Create a section for the college
        const collegeSection = document.createElement('div');
        collegeSection.classList.add('college-timeline-section');
        collegeSection.style.marginBottom = '40px'; // Add space between sections

        // Add the college name as a heading
        const collegeHeading = document.createElement('h2');
        collegeHeading.textContent = `${collegeName}`;
        collegeHeading.style.textAlign = 'center';
        collegeHeading.style.marginBottom = '20px';
        collegeSection.appendChild(collegeHeading);

        // Create a canvas for the timeline chart
        const canvas = document.createElement('canvas');
        canvas.id = `timelineChart-${collegeCode}`;
        collegeSection.appendChild(canvas);

        chartContainer.appendChild(collegeSection);

        // Prepare data for the timeline chart
        const acronymsSet = new Set();
        const dataPoints = [];
        const datesSet = new Set();

        programs.forEach(program => {
            const acronym = getAcronym(program.program_name); // Generate acronym
            const level = program.program_level;
            const date = new Date(program.date_received);
            const year = date.getFullYear();
            const month = date.getMonth(); // 0-based (0 = January)
            const fractionalYear = year + (month + 1) / 12; // Proper fractional year for proportional spacing

            acronymsSet.add(acronym);
            dataPoints.push({
                x: fractionalYear, // Use fractional year for X-axis
                y: acronym,        // Use acronym directly for Y-axis
                level: level,
                formattedDate: `${date.toLocaleString('default', { month: 'short' })} ${date.getDate()}`
            });

            datesSet.add(fractionalYear);
        });

        const sortedAcronyms = Array.from(acronymsSet).sort();
        const sortedFractionalYears = Array.from(datesSet).sort((a, b) => a - b);

        const mappedDataPoints = dataPoints.map(point => {
            const yIndex = sortedAcronyms.indexOf(point.y);
            return {
                x: point.x,
                y: yIndex,
                level: point.level,
                formattedDate: point.formattedDate
            };
        });

        // Render the timeline chart for the college
        new Chart(canvas.getContext('2d'), {
            type: 'scatter',
            data: {
                labels: sortedAcronyms,
                datasets: [
                    {
                        label: `Timeline for ${collegeName}`,
                        data: mappedDataPoints,
                        pointBackgroundColor: 'blue',
                        borderColor: 'blue',
                        showLine: false,
                        pointRadius: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                layout: {
                    padding: {
                        top: 20, // Add padding to the top
                        bottom: 40, // Add padding to the bottom
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {
                            display: true,
                            text: 'Year',
                        },
                        ticks: {
                            stepSize: 1,
                            callback: function (value) {
                                return Math.floor(value); // Display integer years only
                            },
                            min: Math.floor(Math.min(...sortedFractionalYears)) - 0.1, // Add padding before the first year
                            max: Math.ceil(Math.max(...sortedFractionalYears)) + 0.1, // Add padding after the last year
                            padding: 10, // Add spacing for X-axis labels
                        },
                    },
                    y: {
                        type: 'category',
                        title: {
                            display: false,
                        },
                        labels: sortedAcronyms,
                        ticks: {
                            padding: 10, // Add spacing for Y-axis labels
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: false,
                    },
                },
                animation: false,
            },
            plugins: [
                {
                    id: 'customBoxes',
                    afterDraw: (chart) => {
                        const ctx = chart.ctx;
                        const xAxis = chart.scales.x;
                        const yAxis = chart.scales.y;

                        chart.data.datasets.forEach((dataset) => {
                            dataset.data.forEach((dataPoint) => {
                                const x = xAxis.getPixelForValue(dataPoint.x);
                                const y = yAxis.getPixelForValue(dataPoint.y);

                                // Determine the level short code and color
                                let levelShort = '';
                                let levelColor = '';

                                switch (dataPoint.level.toUpperCase()) {
                                    case 'CANDIDATE':
                                        levelShort = 'CAN';
                                        levelColor = '#76FA97';
                                        break;
                                    case 'PSV':
                                        levelShort = 'PSV';
                                        levelColor = '#CCCCCC';
                                        break;
                                    case '1':
                                        levelShort = 'LVL 1';
                                        levelColor = '#FDC879';
                                        break;
                                    case '2':
                                        levelShort = 'LVL 2';
                                        levelColor = '#FDC879';
                                        break;
                                    case '3':
                                        levelShort = 'LVL 3';
                                        levelColor = '#FDC879';
                                        break;
                                    case '4':
                                        levelShort = 'LVL 4';
                                        levelColor = '#FDC879';
                                        break;
                                    default:
                                        levelShort = 'UNK';
                                        levelColor = '#000000';
                                }

                                // Define box dimensions
                                const boxWidth = 60;
                                const boxHeight = 40;
                                const borderRadius = 10;

                                // Calculate box top-left corner
                                const boxX = x - boxWidth / 2;
                                const boxY = y - boxHeight / 2;

                                // Draw the top half with rounded corners
                                ctx.beginPath();
                                ctx.moveTo(boxX + borderRadius, boxY);
                                ctx.lineTo(boxX + boxWidth - borderRadius, boxY);
                                ctx.quadraticCurveTo(boxX + boxWidth, boxY, boxX + boxWidth, boxY + borderRadius);
                                ctx.lineTo(boxX + boxWidth, boxY + boxHeight / 2);
                                ctx.lineTo(boxX, boxY + boxHeight / 2);
                                ctx.lineTo(boxX, boxY + borderRadius);
                                ctx.quadraticCurveTo(boxX, boxY, boxX + borderRadius, boxY);
                                ctx.closePath();

                                ctx.fillStyle = levelColor;
                                ctx.fill();
                                ctx.strokeStyle = '#000000';
                                ctx.stroke();

                                // Draw the bottom half (rectangular)
                                ctx.beginPath();
                                ctx.rect(boxX, boxY + boxHeight / 2, boxWidth, boxHeight / 2);
                                ctx.fillStyle = '#FFFFFF';
                                ctx.fill();
                                ctx.strokeStyle = '#000000';
                                ctx.stroke();

                                // Add level text
                                ctx.fillStyle = 'white';
                                ctx.font = '12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(levelShort, x, boxY + boxHeight / 4);

                                // Add date text
                                ctx.fillStyle = 'black';
                                ctx.font = '12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(dataPoint.formattedDate, x, boxY + (3 * boxHeight) / 4);
                            });
                        });
                    },
                },
            ],
        });
    });
}


function loadProgramsForColleges(collegeCodes, selectedValues) {
    if (collegeCodes.length === 0) {
        document.querySelector('.select-items').innerHTML = "<div>Select programs</div>";
        document.getElementById('chartContainer').innerHTML =
            "<p style='text-align: center; font-size: 20px'><strong>PLEASE SELECT COLLEGE AND PROGRAM/S</strong></p>";
        return;
    }

    const xhr1 = new XMLHttpRequest();
    xhr1.open("POST", "", true);
    xhr1.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xhr1.onreadystatechange = function () {
        if (xhr1.readyState === 4 && xhr1.status === 200) {
            try {
                const response = JSON.parse(xhr1.responseText);
                
                // Create a Set to store unique program names
                const uniquePrograms = new Set();
                
                // Collect all unique program names across all colleges
                Object.values(response.programs).forEach(collegePrograms => {
                    collegePrograms.forEach(program => {
                        uniquePrograms.add(program.program_name);
                    });
                });
                
                // Generate new options HTML with unique programs while maintaining custom structure
                let uniqueOptionsHtml = Array.from(uniquePrograms)
                    .sort() // Optional: sort alphabetically
                    .map(programName => `<div class="select-item" data-value="${programName}">${programName}</div>`)
                    .join('');

                // If no programs are found, show a default message
                if (uniquePrograms.size === 0) {
                    uniqueOptionsHtml = "<div>Select programs</div>";
                }
                
                // Update the dropdown while maintaining custom structure
                document.querySelector('.select-items').innerHTML = uniqueOptionsHtml;

                const chartContainer = document.getElementById('chartContainer');
                chartContainer.innerHTML = ''; // Clear previous content

                // Rest of your existing code remains unchanged
                selectedValues.forEach(selectedCollege => {
                    const collegeCode = selectedCollege.code;
                    const collegeName = selectedCollege.name;
                    const programs = response.programs[collegeCode] || [];

                    let collegeSection = document.createElement('div');
                    collegeSection.classList.add('college-section');

                    let collegeHeading = document.createElement('h3');
                    collegeHeading.classList.add('college-name');
                    collegeHeading.textContent = collegeName;
                    collegeSection.appendChild(collegeHeading);

                    if (programs.length > 0) {
                        let programsList = document.createElement('ul');

                        programs.forEach(program => {
                            const acronym = getAcronym(program.program_name);
                            let listItem = document.createElement('li');
                            listItem.innerHTML = `
                                <strong>${acronym}</strong>
                                <br><em>Level: ${program.program_level}</em>
                                <br>Date: ${program.date_received}
                            `;
                            listItem.title = program.program_name;
                            programsList.appendChild(listItem);
                        });

                        collegeSection.appendChild(programsList);
                    } else {
                        let noProgramsMsg = document.createElement('p');
                        noProgramsMsg.textContent = 'No programs available.';
                        collegeSection.appendChild(noProgramsMsg);
                    }

                    chartContainer.appendChild(collegeSection);
                });

                // Call the function to generate the timeline chart
                createTimeline(response.programs, selectedValues);

                // Reinitialize any custom dropdown functionality
                setupCustomSelect();
            } catch (e) {
                console.error('Failed to parse JSON response:', e, xhr1.responseText);
            }
        }
    };

    // Send selected college codes to the backend
    xhr1.send("college_codes=" + encodeURIComponent(JSON.stringify(collegeCodes)));
}



    // Function to close all select dropdowns
    function closeAllSelect() {
        const selectItems = document.querySelectorAll('.college-select-items, .select-items');
        selectItems.forEach(items => (items.style.display = 'none'));
    }
    

    // Initialize multi-select for colleges and programs
    document.addEventListener('DOMContentLoaded', function () {
        setupCollegeMultiSelect();
        setupCustomSelect(); // Already existing for programs
    });

    // Function to handle multi-select for programs
    function setupCustomSelect() {
    const selectItems = document.querySelector('.select-items');
    const selectedDiv = document.querySelector('.select-selected');
    const items = selectItems.getElementsByClassName('select-item');

    Array.from(items).forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.stopPropagation();
            item.classList.toggle('same-as-selected');

            // Get selected values
            let selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function (selectedItem) {
                return selectedItem.dataset.value;
            });

            // **Removed the maxSelection check here**

            // Update display
            if (selectedValues.length > 0) {
                const displayedText = selectedValues.length > 1 
                    ? `${selectedValues[0]} and ${selectedValues.length - 1} more` 
                    : selectedValues[0];
                selectedDiv.textContent = displayedText;
            } else {
                selectedDiv.textContent = 'Select programs';
            }

            // Load program histories based on selected programs
            loadProgramHistories(selectedValues);
        });
    });

    // Update the currentSelectionType to 'program' on dropdown click
    selectedDiv.addEventListener('click', function (e) {
        e.stopPropagation();
        closeAllSelect();
        selectItems.style.display = selectItems.style.display === 'block' ? 'none' : 'block';

        // Update the current selection type to 'program'
        currentSelectionType = 'program';
        console.log('Selection type set to: program'); // Debug log
    });
}



    // Function to load program histories and render timelines
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
                    document.getElementById('chartContainer').innerHTML = xhr.responseText;
                }
            }
        };
        xhr.send("program_names=" + encodeURIComponent(JSON.stringify(selectedPrograms)));
    }

    // Function to render timeline charts using Chart.js
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

            // Convert date strings to Date objects and prepare data for Chart.js
            const chartData = events.map(event => ({
                x: new Date(event.date),
                y: 0, // y-axis is not used meaningfully here
                label: event.label
            }));

            // Determine the timeline range
            const dates = chartData.map(event => event.x);
            const minDate = new Date(Math.min.apply(null, dates));
            const maxDate = new Date(Math.max.apply(null, dates));
            minDate.setFullYear(minDate.getFullYear() - 1);
            maxDate.setFullYear(maxDate.getFullYear() + 1); // Adjust as needed

            // Prepare the dataset for Chart.js
            const dataset = {
                datasets: [{
                    label: 'Program Timeline',
                    data: chartData,
                    backgroundColor: 'blue',
                    pointRadius: 0, // Hide the scatter points
                    pointHoverRadius: 0,
                    showLine: false
                }]
            };

            // Get the context of the new canvas element
            const ctx = canvas.getContext('2d');

            // Define colors and abbreviations for each level
            const levelColors = {
                'No Graduates Yet': '#B73033', // Red
                'Candidate': '#76FA97', // Green
                'PSV': '#CCCCCC', // Grey
                '1': '#FDC879', // Yellow
                '2': '#FDC879',
                '3': '#FDC879',
                '4': '#FDC879'
            };

            const levelAbbreviations = {
                'No Graduates Yet': 'NA',
                'Candidate': 'CAN',
                'PSV': 'PSV',
                '1': 'LVL 1',
                '2': 'LVL 2',
                '3': 'LVL 3',
                '4': 'LVL 4'
            };

            // Function to draw rounded rectangles with borders
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

            // Define a plugin to draw custom vertical lines with labels and borders
            const verticalLinePlugin = {
                id: 'verticalLinePlugin',
                beforeDraw: chart => {
                    const {
                        ctx,
                        chartArea: { top, bottom },
                        scales: { x, y }
                    } = chart;
                    ctx.save();

                    chartData.forEach(event => {
                        const level = event.label; // Assuming label contains the program level
                        ctx.fillStyle = levelColors[level] || 'black'; // Default color

                        const xPosition = x.getPixelForValue(event.x);
                        const lineWidth = 60;
                        const lineHeight = bottom - y.getPixelForValue(0);
                        const lineTop = y.getPixelForValue(0);

                        // Define margins
                        const marginBetweenLines = 10; // Space between the first and second vertical lines
                        const marginBelowSecondLine = 5; // Space below the second vertical line

                        // Calculate the offset for the first rectangle
                        const offset = lineHeight / 2 + marginBetweenLines; // Adjust as needed

                        // Draw the first rectangle for the level, adjusted upward, with border
                        ctx.fillStyle = levelColors[level] || 'black';
                        drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop - offset, lineWidth, 70, 5, '#AFAFAF');

                        // Draw the level abbreviation inside the first rectangle
                        ctx.fillStyle = 'black'; // Text color
                        ctx.font = 'bold 16px Arial'; // Bold font style
                        ctx.textAlign = 'center'; // Center the text
                        ctx.textBaseline = 'middle';
                        ctx.fillText(levelAbbreviations[level] || '', xPosition, lineTop - offset + 35); // Centered at 35 pixels

                        const secondRectOffset = -5; // Adjust to move the rectangle

                        // Draw the second rectangle for the date with margin below, with border
                        const dateHeight = lineHeight * 0.6; // Adjust as needed
                        ctx.fillStyle = '#FFFFFF'; // White background
                        drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset, lineWidth, dateHeight - marginBelowSecondLine, 5, '#AFAFAF');

                        // Draw the date inside the second rectangle, centered vertically
                        ctx.fillStyle = 'black'; // Text color
                        ctx.font = 'bold 14px Arial'; // Bold font style for date
                        ctx.textBaseline = 'middle'; // Center the text vertically
                        ctx.fillText(
                            new Date(event.x).toLocaleDateString('en-US', {
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

    document.addEventListener('click', closeAllSelect);

// Export charts to PDF
function sendImagesToServer(images, selectionType) {
    console.log("Sending images with selectionType:", selectionType); // Debug log

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'program_timeline_pdf.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log('PDF generated:', xhr.responseText);
            downloadFile(xhr.responseText);
        } else if (xhr.readyState === 4) {
            console.error('Failed to generate PDF:', xhr.statusText);
        }
    };

    const payload = {
        images: images,
        selectionType: selectionType
    };
    console.log("Payload:", payload); // Debug log
    xhr.send(JSON.stringify(payload));
}


// Update the event listener to pass the selection type
document.getElementById('exportPdfButton').addEventListener('click', function () {
    const charts = document.querySelectorAll('canvas'); // All chart elements
    const chartSections = document.querySelectorAll('#chartContainer > div'); // Each section containing a chart

    const images = [];
    let count = 0;

    charts.forEach((chart, index) => {
        html2canvas(chart).then(canvas => {
            const section = chartSections[index];
            const collegeName = section.querySelector('h2') && section.querySelector('h2').innerText.trim() !== '' 
                ? section.querySelector('h2').innerText.trim() 
                : null;
            const programName = section.querySelector('h3') && section.querySelector('h3').innerText.trim() !== '' 
                ? section.querySelector('h3').innerText.trim() 
                : null;

            if (collegeName || programName) {
                const chartName = collegeName && programName 
                    ? `${collegeName} - ${programName}` 
                    : programName || collegeName;

                images.push({
                    name: chartName,
                    data: canvas.toDataURL('image/png')
                });
            }

            count++;
            if (count === charts.length) {
                if (images.length === 0) {
                    alert('No valid charts to export. Ensure all required data is selected.');
                } else {
                    console.log('All charts captured, sending to server');
                    sendImagesToServer(images, currentSelectionType);
                }
            }
        }).catch(err => console.error('Error capturing canvas:', err));
    });
});


// Function to trigger file download
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
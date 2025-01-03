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
    if (basename($_SERVER['PHP_SELF']) !== 'area.php') {
        header("Location: college.php");
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

// Updated SQL query to fetch area information and count of parameters
$sql = "SELECT 
            area.id AS area_code, 
            area.area_name, 
            COUNT(parameters.id) AS parameter_count 
        FROM area 
        LEFT JOIN parameters ON area.id = parameters.area_id
        GROUP BY area.id, area.area_name";

$result = $conn->query($sql);
$areas = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link href="css/navbar.css" rel="stylesheet">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link href="college_style.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">

    <style>
        .hidden {
            display: none;
        }

        .loading-spinner .spinner-border {
            width: 40px;
            height: 40px;
            border-width: 5px;
            border-color: #B73033 !important;
            /* Enforce the custom color */
            border-right-color: transparent !important;
        }

        #loadingSpinner.spinner-hidden {
            display: none;
        }

        .loading-spinner {
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

        .scrollable-container {
            max-height: 650px;
            overflow-y: auto;
        }

        .modal-content {
            top: 30%;
            width: 80%;
            max-width: 1000px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
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
                            <?php if ($totalPendingSchedules > 0 || $totalPendingOrientations > 0): ?>
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
                            <?php if ($assessmentCount > 0): ?>
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
                                <?php if ($assessmentCount > 0): ?>
                                    <span class="notification-counter"><?= $assessmentCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">UDAS Assessments</span>
                                <?php if ($totalMissingAssessments > 0): ?>
                                    <span class="notification-counter"><?= $totalMissingAssessments; ?></span>
                                <?php endif; ?>
                            </a>                            <a href="<?php echo $is_admin ? 'assessment_history.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Assessment History</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link-active">
                            <span style="margin-left: 8px;">Administrative</span>
                            <?php if ($totalPendingUsers > 0 || $transferRequestCount > 0): ?>
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
                                <?php if ($totalPendingUsers > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                                <?php endif; ?> </a>
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
                <h1 class="mb-5 mt-5">AREAS</h1>
                <div class="custom-btn-group">
                    <div class="col-12 d-flex justify-content-between" style="background: white;">
                        <div class="d-flex">
                            <button class="btn-import" onclick="openImportModal()">IMPORT
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download ms-2" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
                                </svg>
                            </button>
                            <button class="btn-add-schedule" onclick="location.href='add_area.php'">ADD AREA
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus ms-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                                </svg>
                            </button>
                        </div>
                        <button class="btn-add-schedule" onclick="openStandardModal()">STANDARD
                            <i class="bi bi-pencil-square" style="font-size: 20px; margin-left: 10px;"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-3 scrollable-container">
                    <div class="table-responsive col-12">
                        <table id="areaTable" class="custom-table table">
                            <thead>
                                <tr>
                                    <th>AREA CODE</th>
                                    <th>AREA NAME</th>
                                    <th>AREA PARAMETERS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($areas as $area) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($area['area_code']); ?></td>
                                        <td><?php echo htmlspecialchars($area['area_name']); ?></td>
                                        <td><?php echo htmlspecialchars($area['parameter_count']); ?></td>
                                        <td>
                                            <button class="view-button" onclick="showParameters('<?php echo $area['area_code']; ?>', '<?php echo htmlspecialchars($area['area_name']); ?>')">VIEW</button>
                                            <button class="edit-button" onclick="location.href='edit_area.php?code=<?php echo $area['area_code']; ?>'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div id="viewModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Parameters</h2>
                <table id="parametersTable">
                    <thead>
                        <tr>
                            <th>Parameter Name</th>
                            <th>Parameter Description</th>
                        </tr>
                    </thead>
                    <tbody id="parametersBody">
                    </tbody>
                </table>
            </div>
        </div>

        <div id="standardModal" class="modal">
            <div class="modal-content">
                <div class="existing-standards">
                    <h3>ACCREDITATION STANDARDS</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Standard</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="standards-table-body">
                            <?php
                            // Include database connection
                            include 'connection.php';

                            // Fetch data from the accreditation_standard table
                            $sql = "SELECT id, Level, Standard FROM accreditation_standard";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                // Output data for each row
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr data-id='{$row['id']}'>
                                    <td class='level-value'>" . htmlspecialchars($row['Level']) . "</td>
                                    <td class='standard-value'>" . htmlspecialchars($row['Standard']) . "</td>
                                    <td class='action-buttons'>
                                        <button class='edit-btn' onclick='makeEditable(this)'>Edit</button>
                                    </td>
                                </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>No standards available</td></tr>";
                            }

                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                    <div class="add-new-standard">
                        <button id="add-btn" onclick="addNewStandard()">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="importModal" class="modal">
            <div class="import-modal-content">
                <h2>IMPORT AREA</h2>
                <form action="add_area_import.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">Upload Excel File:</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn-cancel" onclick="closeImportModal()">CANCEL</button>
                        <button type="submit" class="btn-add-program">IMPORT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="loadingSpinner" class="loading-spinner spinner-hidden">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        window.onclick = function(event) {
            var modals = [
                document.getElementById('programModal'),
                document.getElementById('importModal'),
                document.getElementById('viewModal')
            ];

            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const importForm = document.querySelector('#importModal form');
            const loadingSpinner = document.getElementById('loadingSpinner');

            importForm.addEventListener('submit', function() {
                // Show the loading spinner
                loadingSpinner.classList.remove('spinner-hidden');
            });

            // Reference span elements for modals after DOM is fully loaded
            var spanView = document.getElementsByClassName("close")[0];
            var viewModal = document.getElementById("viewModal");

            // Close the viewModal when the span is clicked
            spanView.onclick = function() {
                viewModal.style.display = "none"; // Correctly reference viewModal
            }
        });

        function showTable(tableId, buttonId) {
            const tables = document.querySelectorAll('.table');
            tables.forEach(table => {
                if (table.id === tableId) {
                    table.classList.remove('hidden');
                } else {
                    table.classList.add('hidden');
                }
            });
        }

        function openImportModal() {
            importModal.style.display = "block";
        }

        function closeImportModal() {
            importModal.style.display = "none";
        }

        function showParameters(area_code, area_name) {
            console.log("Displaying modal for area_code: " + area_code);
            fetchParameters(area_code);

            // Update modal header with the actual area name
            var modalHeader = document.querySelector("#viewModal h2");
            modalHeader.textContent = area_name; // Set the area name here

            var modal = document.getElementById("viewModal");
            if (modal) {
                modal.style.display = "block"; // Show the modal
                console.log("Modal displayed.");
            } else {
                console.error("Modal element not found!");
            }
        }

        // Fetch parameters via AJAX
        function fetchParameters(area_code) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "fetch_parameters.php?area_code=" + area_code, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var parameters = JSON.parse(xhr.responseText);
                    var parametersBody = document.getElementById("parametersBody");
                    parametersBody.innerHTML = ""; // Clear existing content

                    parameters.forEach(function(param) {
                        var row = "<tr>";
                        row += "<td>" + param.parameter_name + "</td>";
                        row += "<td><a href='" + param.parameter_description + "' target='_blank'>" + param.parameter_description + "</a></td>";
                        row += "</tr>";
                        parametersBody.innerHTML += row;
                    });
                }
            };
            xhr.send();
        }

        function openStandardModal() {
            var standardModal = document.getElementById("standardModal");
            standardModal.style.display = "block";
        }

        // Function to close the STANDARD modal
        function closeStandardModal() {
            var standardModal = document.getElementById("standardModal");
            standardModal.style.display = "none";
        }

        // Close the modal when clicking outside of the modal content
        window.onclick = function(event) {
            var standardModal = document.getElementById("standardModal");
            if (event.target == standardModal) {
                standardModal.style.display = "none";
            }
        }

        function makeEditable(button) {
            const row = button.closest('tr');
            const levelCell = row.querySelector('.level-value');
            const standardCell = row.querySelector('.standard-value');
            const actionCell = row.querySelector('.action-buttons');

            const levelValue = levelCell.textContent.trim();
            const standardValue = standardCell.textContent.trim();

            levelCell.innerHTML = `<input type="text" value="${levelValue}" />`;
            standardCell.innerHTML = `
        <input 
            type="number" 
            step="0.05" 
            min="1.00" 
            max="5.00" 
            value="${standardValue}" 
            list="standard-options" 
            oninput="validateInputValue(this)" />
        <datalist id="standard-options">
            ${generateDropdownOptions()}
        </datalist>
    `;

            actionCell.innerHTML = `
        <button class='save-btn' onclick='saveChanges(this)'>Save</button>
        <button class='cancel-btn' onclick='cancelChanges(this, "${levelValue}", "${standardValue}")'>Cancel</button>
    `;
        }

        function generateDropdownOptions() {
            let options = "";
            for (let i = 1.00; i <= 5.00; i += 0.05) {
                const value = i.toFixed(2);
                options += `<option value="${value}"></option>`;
            }
            return options;
        }

        function validateInputValue(input) {
            const value = parseFloat(input.value);
            if (value < 1.00 || value > 5.00 || isNaN(value)) {
                input.setCustomValidity("Please enter a value between 1.00 and 5.00.");
            } else {
                input.setCustomValidity("");
            }
        }

        function saveChanges(button) {
            const row = button.closest('tr');
            const id = row.getAttribute('data-id');
            const levelCell = row.querySelector('.level-value');
            const standardCell = row.querySelector('.standard-value');
            const actionCell = row.querySelector('.action-buttons');

            const newLevel = levelCell.querySelector("input").value.trim();
            const newStandard = parseFloat(standardCell.querySelector("input").value).toFixed(2);

            fetch('update_standard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        level: newLevel,
                        standard: newStandard
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        levelCell.textContent = newLevel;
                        standardCell.textContent = newStandard;

                        actionCell.innerHTML = `<button class='edit-btn' onclick='makeEditable(this)'>Edit</button>`;
                    } else {
                        alert('Failed to update standard.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function cancelChanges(button, originalLevel, originalStandard) {
            const row = button.closest('tr');
            const levelCell = row.querySelector('.level-value');
            const standardCell = row.querySelector('.standard-value');
            const actionCell = row.querySelector('.action-buttons');

            levelCell.textContent = originalLevel;
            standardCell.textContent = originalStandard;

            actionCell.innerHTML = `<button class='edit-btn' onclick='makeEditable(this)'>Edit</button>`;
        }

        function addNewStandard() {
            const tableBody = document.getElementById('standards-table-body');

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
        <td class='level-value'><input type="text" placeholder="Enter Level" /></td>
        <td class='standard-value'>
            <input 
                type="number" 
                step="0.05" 
                min="1.00" 
                max="5.00" 
                list="standard-options" 
                oninput="validateInputValue(this)" 
                placeholder="Enter Standard" />
            <datalist id="standard-options">
                ${generateDropdownOptions()}
            </datalist>
        </td>
        <td class='action-buttons'>
            <button class='save-btn' onclick='saveNewStandard(this)'>Save</button>
            <button class='cancel-btn' onclick='cancelNewStandard(this)'>Cancel</button>
        </td>
    `;

            tableBody.appendChild(newRow);

            const addBtn = document.getElementById('add-btn');
            addBtn.parentElement.appendChild(addBtn); // Move the add button below the new row
        }

        function saveNewStandard(button) {
    const row = button.closest('tr');
    const levelInput = row.querySelector('.level-value input');
    const standardInput = row.querySelector('.standard-value input');

    const newLevel = levelInput.value.trim();
    const newStandard = parseFloat(standardInput.value).toFixed(2);

    if (!newLevel || isNaN(newStandard)) {
        alert('Please fill out both fields correctly.');
        return;
    }

    fetch('add_standard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                level: newLevel,
                standard: newStandard
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.setAttribute('data-id', data.id);
                row.querySelector('.level-value').textContent = newLevel;
                row.querySelector('.standard-value').textContent = newStandard;

                row.querySelector('.action-buttons').innerHTML = `<button class='edit-btn' onclick='makeEditable(this)'>Edit</button>`;

                const addBtn = document.getElementById('add-btn');
                addBtn.parentElement.appendChild(addBtn);
            } else {
                // Show the specific error message from the server
                alert(data.message || 'Failed to add standard.');
                // Keep focus on the level input if it's a duplicate
                if (data.message.includes('already exists')) {
                    levelInput.focus();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the standard.');
        });
}

        function cancelNewStandard(button) {
            const row = button.closest('tr');
            row.remove();

            const addBtn = document.getElementById('add-btn');
            addBtn.parentElement.appendChild(addBtn);
        }
    </script>

</body>

</html>
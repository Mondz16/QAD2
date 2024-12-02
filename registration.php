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
    if (basename($_SERVER['PHP_SELF']) !== 'registration.php') {
        header("Location: registration.php");
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

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function displayRegistrations($conn, $tableName, $title)
{
    // Fetch all users (both pending and non-pending) to check for duplicates
    $sql = "";
    if ($tableName === 'internal_users') {
        $sql = "SELECT i.user_id, i.first_name, i.middle_initial, i.last_name, i.email, c.college_name, i.status
                FROM internal_users i
                LEFT JOIN college c ON i.college_code = c.code
                WHERE i.otp = 'verified'";
    } elseif ($tableName === 'external_users') {
        $sql = "SELECT e.user_id, e.first_name, e.middle_initial, e.last_name, e.email, c.company_name
                FROM external_users e
                LEFT JOIN company c ON e.company_code = c.code";
    }

    $result = $conn->query($sql);
    $userIds = [];
    $duplicateIds = [];

    // First pass: Identify duplicates across all users
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $trimmedUserId = substr($row['user_id'], 3);

            if (isset($userIds[$trimmedUserId])) {
                $duplicateIds[$trimmedUserId] = true;
            } else {
                $userIds[$trimmedUserId] = true;
            }
        }
    }

    // Second pass: Display only non-duplicate internal_users with status = 'pending'
    $result->data_seek(0); // Reset result pointer to the start

    if ($result->num_rows > 0) {
        echo "<table id='collegeTable' class='data-table table border rounded-2'>
            <tr>
                <th>ID</th>
                <th>FIRST NAME</th>
                <th>MIDDLE INITIAL</th>
                <th>LAST NAME</th>
                <th>EMAIL</th>";
    
        // Additional columns based on table type
        if ($tableName === 'internal_users') {
            echo "<th>COLLEGE</th>";
        } elseif ($tableName === 'external_users') {
            echo "<th>Company</th>";
        }
    
        echo "<th>Actions</th>
            </tr>";
    
        $rowsDisplayed = false; // Flag to track if any valid rows are displayed
    
        while ($row = $result->fetch_assoc()) {
            $trimmedUserId = substr($row['user_id'], 3);
    
            // Skip rows with duplicate user_id
            if (isset($duplicateIds[$trimmedUserId])) {
                continue;
            }
    
            // Display only internal_users with status = 'pending'
            if ($tableName === 'internal_users' && $row['status'] !== 'pending') {
                continue;
            }
    
            $rowsDisplayed = true; // Set the flag if at least one row is displayed
    
            echo "<tr>
                <td>{$row['user_id']}</td>
                <td>{$row['first_name']}</td>
                <td>{$row['middle_initial']}</td>
                <td>{$row['last_name']}</td>
                <td>{$row['email']}</td>";
    
            // Display additional column data based on table type
            if ($tableName === 'internal_users') {
                echo "<td>{$row['college_name']}</td>";
            } elseif ($tableName === 'external_users') {
                echo "<td>{$row['company_name']}</td>";
            }
    
            echo "<td class='action-buttons'>
                    <button class='btn btn-approve btn-sm' onclick='openApproveModal(\"{$row['user_id']}\")'>Approve</button>
                    <button class='btn btn-reject btn-sm' onclick='openRejectModal(\"{$row['user_id']}\")'>Disapprove</button>
                </td>
            </tr>";
        }
    
        echo "</table>";
    
        // If no valid rows were displayed, show the "NO PENDING REGISTRATIONS" message
        if (!$rowsDisplayed) {
            echo "<div class='no-schedule-prompt'><p>NO PENDING REGISTRATIONS</p></div>";
        }
    } else {
        // If no rows exist in the result set
        echo "<div class='no-schedule-prompt'><p>NO PENDING REGISTRATIONS</p></div>";
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

// Total pending users count
$totalPendingUsers = $internalPendingCount + $externalPendingCount;
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/registration_pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        .loading-spinner .spinner-border {
            width: 40px;
            height: 40px;
            border-width: 5px;
            border-color: #B73033 !important; /* Enforce the custom color */
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
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link-active">
                            <span style="margin-left: 8px;">Administrative</span>
                            <?php if ($totalPendingUsers > 0): ?>
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
                <h1 class="mt-5 mb-5">PENDING REGISTRATIONS</h1>
                <div class="row m-1 mt-3">
                    <div class="col-12 border rounded-2" style="background: white;">
                        <div class="row no-gutters py-2">
                            <div class="col-6">
                                <button id="collegesBtn" class="pobtn border btn w-100 py-2" onclick="showTable('collegeTable', 'collegesBtn')">INTERNAL</button>
                            </div>
                            <div class="col-6">
                                <button id="sucBtn" class="nebtn border btn w-100 py-2" onclick="showTable('sucTable', 'sucBtn')">EXTERNAL</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="table-responsive col-12">
                        <div class="tab-container">
                            <div class="admin-content">
                                <div class="tab-content active" id="internal">
                                    <?php displayRegistrations($conn, 'internal_users', 'INTERNAL PENDING REGISTRATIONS'); ?>
                                </div>
                                <div class="tab-content" id="external">
                                    <?php displayRegistrations($conn, 'external_users', 'EXTERNAL PENDING REGISTRATIONS'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div id="approveModal" class="modal">
            <div class="modal-content">
                <h4>Are you sure you want to approve this registration?</h4>
                <form id="approveForm" action="registration_approval.php" method="post">
                    <input type="hidden" name="id" id="approveUserId">
                    <input type="hidden" name="action" value="approve">
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeApproveModal()">NO</button>
                        <button type="submit" class="yes-btn positive">YES</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- disapprove Modal -->
        <div id="rejectModal" class="modal">
            <div class="modal-content">
                <h4>Are you sure you want to disapprove this registration?</h4>
                <form id="rejectForm" action="registration_approval.php" method="post">
                    <input type="hidden" name="id" id="rejectUserId">
                    <input type="hidden" name="action" value="reject">
                    <textarea rows="3" cols="52" id="rejectReason" name="reason" placeholder="Enter reason for rejection" required></textarea>
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeRejectModal()">NO</button>
                        <button type="submit" class="yes-btn">YES</button>
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
        document.getElementById('approveForm').addEventListener('submit', function(event) {
            // Show the loading spinner
            document.getElementById('loadingSpinner').classList.remove('spinner-hidden');
        });

        document.getElementById('rejectForm').addEventListener('submit', function(event) {
            // Show the loading spinner
            document.getElementById('loadingSpinner').classList.remove('spinner-hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const target = tab.getAttribute('data-tab');

                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    tabContents.forEach(content => {
                        if (content.id === target) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });
        });

        function openApproveModal(userId) {
            document.getElementById('approveUserId').value = userId;
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        function openRejectModal(userId) {
            document.getElementById('rejectUserId').value = userId;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('rejectReason').value;
            if (reason.trim() === "") {
                alert("Please provide a reason for rejection.");
                e.preventDefault();
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

            const buttons = document.querySelectorAll('.pobtn, .nebtn');
            buttons.forEach(button => {
                button.classList.remove('pobtn');
                button.classList.add('nebtn');
            });

            const activeButton = document.getElementById(buttonId);
            activeButton.classList.remove('nebtn');
            activeButton.classList.add('pobtn');
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>
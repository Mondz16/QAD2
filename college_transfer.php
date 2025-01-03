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
    if (basename($_SERVER['PHP_SELF']) !== 'college_transfer.php') {
        header("Location: college_transfer.php");
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

// Fetch all users and group by the same bb-cccc part of user_id
$sql = "SELECT user_id, college_code, first_name, middle_initial, last_name, email, status 
        FROM internal_users";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $bb_cccc = substr($row['user_id'], 3); // Extract bb-cccc part
    $users[$bb_cccc][] = $row;
}

// Filter out groups with less than 2 users (no transfer request)
$transfer_requests = array_filter($users, function ($group) {
    return count($group) > 1;
});
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Transfer Requests</title>
    <link rel="stylesheet" href="college_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/registration_pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        .college-transfer-table th {
            font-weight: bold;
        }

        .college-transfer-table th,
        .college-transfer-table td {
            border-bottom: 1px solid #ddd;
            background-color: white;
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
                        <a href="#" class="sidebar-link-active">
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
                <h1 class="mt-5 mb-5">COLLEGE TRANSFER</h1>
                <div class="row mt-3">
                    <div class="table-responsive col-12">
                        <div class="tab-container">
                            <div class="admin-content">
                                <div class="tab-content active" id="internal">
                                    <table class='college-transfer-table'>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Previous College</th>
                                            <th>New College</th>
                                            <th>Action</th>
                                        </tr>
                                        <?php foreach ($transfer_requests as $bb_cccc => $group) : ?>
                                            <?php
                                            $previous_user = null;
                                            $new_user = null;
                                            foreach ($group as $user) {
                                                if ($user['status'] == 'active') {
                                                    $previous_user = $user;
                                                } elseif ($user['status'] == 'pending') {
                                                    $new_user = $user;
                                                }
                                            }
                                            if ($previous_user && $new_user) :
                                                $previous_college_code = $previous_user['college_code'];
                                                $new_college_code = $new_user['college_code'];

                                                // Fetch college names
                                                $stmt_prev_college = $conn->prepare("SELECT college_name FROM college WHERE code = ?");
                                                $stmt_prev_college->bind_param("s", $previous_college_code);
                                                $stmt_prev_college->execute();
                                                $stmt_prev_college->bind_result($previous_college_name);
                                                $stmt_prev_college->fetch();
                                                $stmt_prev_college->close();

                                                $stmt_new_college = $conn->prepare("SELECT college_name FROM college WHERE code = ?");
                                                $stmt_new_college->bind_param("s", $new_college_code);
                                                $stmt_new_college->execute();
                                                $stmt_new_college->bind_result($new_college_name);
                                                $stmt_new_college->fetch();
                                                $stmt_new_college->close();
                                            ?>
                                                <tr>
                                                    <td><?php echo $previous_user['first_name'] . ' ' . $previous_user['middle_initial'] . '. ' . $previous_user['last_name']; ?></td>
                                                    <td><?php echo $previous_user['email']; ?></td>
                                                    <td><?php echo $previous_college_name; ?></td>
                                                    <td><?php echo $new_college_name; ?></td>
                                                    <td class="action-buttons">
                                                        <button class="btn btn-approve btn-sm" onclick="openAcceptModal('<?php echo $new_user['user_id']; ?>', '<?php echo $previous_user['user_id']; ?>', '<?php echo $new_user['email']; ?>', '<?php echo $new_user['first_name'] . ' ' . $new_user['middle_initial'] . '. ' . $new_user['last_name']; ?>')">Approve</button>
                                                        <button class="btn btn-reject btn-sm" onclick="openRejectModal('<?php echo $new_user['user_id']; ?>', '<?php echo $previous_user['user_id']; ?>')">Disapprove</button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- The Modal for Acceptance -->
        <div id="acceptModal" class="modal">
            <div class="modal-content">
                <h4>Are you sure you want to approve this request?</h4>
                <form id="acceptForm" action="college_transfer_process.php" method="post">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="new_user_id" id="accept_new_user_id">
                    <input type="hidden" name="previous_user_id" id="accept_previous_user_id">
                    <input type="hidden" name="new_user_email" id="accept_new_user_email">
                    <input type="hidden" name="new_user_name" id="accept_new_user_name">
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeAcceptModal()">NO</button>
                        <button type="submit" class="yes-btn positive">YES</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- The Modal for Rejection -->
        <div id="rejectModal" class="modal">
            <div class="modal-content">
                <h4>Are you sure you want to disapprove this request?</h4>
                <form id="rejectForm" action="college_transfer_process.php" method="post">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="new_user_id" id="reject_new_user_id">
                    <input type="hidden" name="previous_user_id" id="reject_previous_user_id">
                    <textarea id="reject_reason" name="reject_reason" rows="4" required></textarea>
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeRejectModal()">NO</button>
                        <button type="submit" class="yes-btn">YES</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
        
        <script>
            window.onclick = function(event) {
                var modals = [
                    document.getElementById('acceptModal'),
                    document.getElementById('rejectModal')
                ];

                modals.forEach(function(modal) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }
                });
            }
            
            function openAcceptModal(newUserId, previousUserId, newUserEmail, newUserName) {
                document.getElementById('accept_new_user_id').value = newUserId;
                document.getElementById('accept_previous_user_id').value = previousUserId;
                document.getElementById('accept_new_user_email').value = newUserEmail;
                document.getElementById('accept_new_user_name').value = newUserName;
                document.getElementById('acceptModal').style.display = 'block';
            }

            function closeAcceptModal() {
                document.getElementById('acceptModal').style.display = 'none';
            }

            function openRejectModal(newUserId, previousUserId) {
                document.getElementById('reject_new_user_id').value = newUserId;
                document.getElementById('reject_previous_user_id').value = previousUserId;
                document.getElementById('rejectModal').style.display = 'block';
            }

            function closeRejectModal() {
                document.getElementById('rejectModal').style.display = 'none';
            }
        </script>
</body>

</html>
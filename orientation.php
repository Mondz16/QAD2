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
    if (basename($_SERVER['PHP_SELF']) !== 'orientation.php') {
        header("Location: orientation.php");
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

function displayOrientationDetails($conn, $orientationType, $title)
{
    $sql = "SELECT o.id, o.orientation_date, o.orientation_time, 
            IF(o.orientation_type = 'online', ol.orientation_link, f2f.college_building) AS location,
            IF(o.orientation_type = 'online', ol.link_passcode, f2f.room_number) AS additional_info,
            o.schedule_id
            FROM orientation o
            LEFT JOIN online ol ON o.id = ol.orientation_id
            LEFT JOIN face_to_face f2f ON o.id = f2f.orientation_id
            WHERE o.orientation_type = '$orientationType' AND o.orientation_status = 'pending'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2 class='table-title'>$title</h2>";
        echo "<table class='data-table'>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Time</th>";

        if ($orientationType === 'online') {
            echo "<th>Link</th>
                  <th>Passcode</th>";
        } elseif ($orientationType === 'face_to_face') {
            echo "<th>Building</th>
                  <th>Room Number</th>";
        }

        echo "<th>Actions</th>
            </tr>";

        while ($row = $result->fetch_assoc()) {
            $formatted_date = date("F j, Y", strtotime($row['orientation_date']));
            $formatted_time = date("g:i A", strtotime($row['orientation_time']));
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$formatted_date}</td>
                <td>{$formatted_time}</td>";

            if ($orientationType === 'online') {
                echo "<td>{$row['location']}</td>
                      <td>{$row['additional_info']}</td>";
            } elseif ($orientationType === 'face_to_face') {
                echo "<td>{$row['location']}</td>
                      <td>{$row['additional_info']}</td>";
            }

            echo "<td class='action-buttons'>
                    <button class='btn btn-sm approve' onclick='openApproveModal({$row['id']})'>Approve</button>
                    <button class='btn btn-sm cancel' onclick='openDenyModal({$row['id']})'>Deny</button>
                    <button class='btn btn-sm btn-primary' onclick='viewSchedule({$row['schedule_id']})'>View Schedule</button>
                </td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<div class='no-schedule-prompt'><p>NO PENDING REQUEST FOR $title.</p></div>";
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
    <title>Orientations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/orientation_pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
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
                        <a href="#" class="sidebar-link">
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
            <div class="tab-container mt-4">
                <div class="admin-content">
                    <h1 class="tabheader mt-2 mb-5">PENDING ORIENTATIONS</h1>
                    <div class="col-12 border rounded-2" style="background: white;">
                        <div class="row no-gutters py-2">
                            <div class="tabs">
                                <div class="tab border active" data-tab="online">ONLINE</div>
                                <div class="tab border" data-tab="face_to_face">FACE TO FACE</div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content active" id="online">
                        <?php displayOrientationDetails($conn, 'online', 'ONLINE ORIENTATIONS'); ?>
                    </div>
                    <div class="tab-content" id="face_to_face">
                        <?php displayOrientationDetails($conn, 'face_to_face', 'FACE TO FACE ORIENTATIONS'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <h2>Are you sure you want to approve this orientation request?</h2>
            <div class="modal-buttons">
                <button class="no-btn" onclick="closeApproveModal()">NO</button>
                <button class="yes-btn" id="confirmApproveBtn">YES</button>
            </div>
        </div>
    </div>

    <!-- Deny Modal -->
    <div id="denyModal" class="modal">
            <div class="modal-content">
                <h2>Are you sure you want to deny this orientation request?</h2>
                <textarea rows="5" cols="52" id="denyReason" placeholder="Enter reason for denial"></textarea>
                <div class="modal-buttons">
                    <button class="no-btn" type="button" onclick="closeDenyModal()">NO</button>
                    <button class="yes-btn rejection" id="confirmDenyBtn">YES</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="header">
                <h2>Schedule Details</h2>
                <span class="close" onclick="closeScheduleModal()">&times;</span>
            </div>
            <div id="scheduleContent"></div>
        </div>
    </div>

    <form id="approveForm" action="orientation_approval.php" method="post" style="display: none;">
        <input type="hidden" name="id" id="approveOrientationId">
        <input type="hidden" name="action" value="approve">
    </form>

    <form id="denyForm" action="orientation_approval.php" method="post" style="display: none;">
        <input type="hidden" name="id" id="denyOrientationId">
        <input type="hidden" name="action" value="deny">
        <input type="hidden" name="reason" id="denyReasonInput">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        window.onclick = function(event) {
            var modals = [
                document.getElementById('approveModal'),
                document.getElementById('denyModal'),
                document.getElementById('scheduleModal')
            ];

            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        }
        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

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

        let approveOrientationId;
        let denyOrientationId;

        function openApproveModal(id) {
            approveOrientationId = id;
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        function openDenyModal(id) {
            denyOrientationId = id;
            document.getElementById('denyModal').style.display = 'block';
        }

        function closeDenyModal() {
            document.getElementById('denyModal').style.display = 'none';
        }

        function viewSchedule(scheduleId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_schedule.php?schedule_id=' + scheduleId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('scheduleContent').innerHTML = xhr.responseText;
                    document.getElementById('scheduleModal').style.display = 'block';
                }
            };
            xhr.send();
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        document.getElementById('confirmApproveBtn').addEventListener('click', function() {
            document.getElementById('approveOrientationId').value = approveOrientationId;
            document.getElementById('approveForm').submit();
        });

        document.getElementById('confirmDenyBtn').addEventListener('click', function() {
            const reason = document.getElementById('denyReason').value;
            if (reason.trim() === "") {
                alert("Please provide a reason for denial.");
                return;
            }
            document.getElementById('denyOrientationId').value = denyOrientationId;
            document.getElementById('denyReasonInput').value = reason;
            document.getElementById('denyForm').submit();
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
    </script>
</body>

</html>

<?php
$conn->close();
?>
<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'dashbaord.php') {
        header("Location: dashboard.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal_orientation.php') {
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
$stmt_college->bind_result($college_name);
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

// Fetch schedules matching the college code and status 'pending' or 'approved'
$sql_schedules = "
    SELECT 
        s.id AS schedule_id, 
        s.program_id, 
        p.program_name, 
        s.level_applied, 
        s.schedule_date, 
        s.schedule_time, 
        s.schedule_status,
        o.id AS orientation_id,
        o.orientation_status,
        c.college_name
    FROM schedule s
    JOIN program p ON s.program_id = p.id
    LEFT JOIN orientation o ON s.id = o.schedule_id
    JOIN college c ON s.college_code = c.code
    WHERE s.college_code = ? 
    AND s.schedule_status IN ('pending', 'approved')
    ORDER BY s.schedule_date, s.schedule_time
";

$stmt_schedules = $conn->prepare($sql_schedules);
$stmt_schedules->bind_param("s", $college_code);
$stmt_schedules->execute();
$result_schedules = $stmt_schedules->get_result();

$schedules = [];
$current_date_time = new DateTime();  // Current date and time

while ($row = $result_schedules->fetch_assoc()) {
    $schedule_date_time = new DateTime($row['schedule_date'] . ' ' . $row['schedule_time']);
    if ($schedule_date_time > $current_date_time) {
        $schedules[$row['schedule_id']] = [
            'program_name' => $row['program_name'],
            'level_applied' => $row['level_applied'],
            'schedule_date' => $row['schedule_date'],
            'schedule_time' => $row['schedule_time'],
            'schedule_status' => $row['schedule_status'],
            'orientation_id' => $row['orientation_id'],
            'orientation_status' => $row['orientation_status'],
            'college_name' => $row['college_name'],
            'team' => []
        ];
    }
}
$stmt_schedules->close();

// Fetch team members for the matched schedules
if (!empty($schedules)) {
    $schedule_ids = array_keys($schedules);
    $placeholders = implode(',', array_fill(0, count($schedule_ids), '?'));
    $types = str_repeat('i', count($schedule_ids));

    $sql_team = "
        SELECT 
            t.schedule_id, 
            t.role, 
            CONCAT(u.first_name, ' ', u.middle_initial, '. ', u.last_name) AS team_member_name
        FROM team t
        JOIN internal_users u ON t.internal_users_id = u.user_id
        WHERE t.schedule_id IN ($placeholders)
    ";

    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param($types, ...$schedule_ids);
    $stmt_team->execute();
    $result_team = $stmt_team->get_result();

    while ($row = $result_team->fetch_assoc()) {
        $schedules[$row['schedule_id']]['team'][] = [
            'role' => $row['role'],
            'name' => $row['team_member_name']
        ];
    }
    $stmt_team->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Orientation Requests</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .internal-external input[type="radio"]:checked+label {
            background-color: #FF7A7A;
            color: white;
        }

        .internal-external label {
            position: relative;
            color: black;
            cursor: pointer;
            font-size: 14px;
            border: 1px solid #7B7B7B;
            border-radius: 8px;
            padding: 12px 20px;
            display: inline-flex;
            align-items: center;
            width: 224px;
        }

        /* Modal overlay style */
        .orientationmodal {
            position: fixed; /* Stay in place even when scrolling */
            z-index: 9999; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            display: flex; /* Use flexbox to center content */
            align-items: center; /* Center content vertically */
            justify-content: center; /* Center content horizontally */
            overflow: auto; /* Enable scrolling if content is too large */
        }

        /* Modal content style */
        .orientationmodal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #AFAFAF;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); /* Optional: Adds a subtle shadow */
            z-index: 10000; /* Ensures the content is above the overlay */
        }

        /* Centered heading inside modal */
        .orientationmodal-content h2 {
            text-align: center;
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
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
        <div class="container">
            <div class="header1">
                <div class="nav-list">
                    <a href="internal.php" class="profile1">Profile <i class="fa-regular fa-user"></i></a>
                    <a href="internal_notification.php" class="orientation1" style="position: relative;">
                        NOTIFICATION<i class="fa-regular fa-bell" style="position: relative;">
                            <?php if ($notification_count > 0): ?>
                                <span id="notificationCount" class="notification-count"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </i>
                    </a>
                    <a href="internal_assessment.php" class="assessment1">Assessment<i class="fa-solid fa-medal"></i></a>
                    <a href="internal_orientation.php" class="active orientation1">Orientation<i class="fa-regular fa-calendar"></i></a>
                    <a class="logout" onclick="openLogoutModal()"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div style="height: 32px;"></div>
        <div class="orientation2">
            <?php if (!empty($schedules)): ?>
                <?php foreach ($schedules as $schedule_id => $schedule): ?>
                    <?php if ($schedule['schedule_status'] === 'approved' || $schedule['schedule_status'] === 'pending'): ?>
                    <div class="notification-list1">
                        <div class="orientation3">
                            <?php
                            $status_color = '';
                            if ($schedule['schedule_status'] === 'pending') {
                                $status_color = '#E6A33E'; // Pending color
                            } elseif ($schedule['schedule_status'] === 'approved') {
                                $status_color = '#34C759'; // Approved color
                            }
                            ?>
                            <?php if (empty($schedule['orientation_id'])): ?>
                    <!-- No orientation request exists, show the request orientation button -->
                    <p class="status1">STATUS: <strong style="color: <?php echo $status_color; ?>; margin-left: 5px;"><?php echo htmlspecialchars($schedule['schedule_status']); ?></strong> 
                    <button class="orientation-button" onclick="openModal(<?php echo $schedule_id; ?>)">REQUEST ORIENTATION</button></p>
                <?php else: ?>
                                <?php if ($schedule['orientation_status'] === 'pending'): ?>
                                    <p class="status1">STATUS: <strong style="color: <?php echo $status_color; ?>; margin-left: 5px;"><?php echo htmlspecialchars($schedule['schedule_status']); ?></strong> <button class="assessment-button-done" style="background-color: #AFAFAF; color: black; border: 1px solid #AFAFAF;">REQUEST PENDING</button> </p>
                                <?php elseif ($schedule['orientation_status'] === 'approved'): ?>
                                    <p class="status1">STATUS: <strong style="color: <?php echo $status_color; ?>; margin-left: 5px;"><?php echo htmlspecialchars($schedule['schedule_status']); ?></strong> <button class="assessment-button-done">REQUEST APPROVED</button> </p>
                                <?php elseif ($schedule['orientation_status'] === 'denied'): ?>
                                    <p class="status1">STATUS: <strong style="color: <?php echo $status_color; ?>; margin-left: 5px;"><?php echo htmlspecialchars($schedule['schedule_status']); ?></strong> <button class="assessment-button-done" style="background-color: red; color: white; border: 1px solid red;">REQUEST DENIED</button></p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="container">
                                <div class="body3">
                                <div class="bodyLeft2">
                            <p>COLLEGE <br>
                                <div style="height: 10px;"></div>
                                <div class="orientationname">
                                    <div class="nameContainer">
                                        <?php echo htmlspecialchars($schedule['college_name']); ?>
                                    </div>
                                </div>
                            </p>
                            <div style="height: 20px;"></div>
                            <p>PROGRAM <br>
                                <div style="height: 10px;"></div>
                                <div class="orientationname">
                                    <div class="nameContainer">
                                        <?php echo htmlspecialchars($schedule['program_name']); ?>
                                    </div>
                                </div>
                            </p>
                            <div class="orientationname">
                                    <div class="titleContainer">
                                        <p>LEVEL APPLIED</p>
                                    </div>
                                    <div class="titleContainer">
                                        <p>DATE</p>
                                    </div>
                                <div class="titleContainer">
                                            <p>TIME</p>
                                </div>
                            </div>
                            <div class="orientationname">
                                    <div class="nameContainer orientationContainer1">
                                        <?php echo htmlspecialchars($schedule['level_applied']); ?>
                                    </div>
                                    <div class="nameContainer orientationContainer">
                                        <?php 
                                            $date = new DateTime($schedule['schedule_date']);
                                            echo $date->format('F j, Y'); 
                                        ?>
                                    </div>
                                <div class="nameContainer orientationContainer">
                                            <?php 
                                        $time = new DateTime($schedule['schedule_time']);
                                        echo $time->format('g:i A'); 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="bodyRight2">
                            <?php 
                            $team_leaders = array_filter($schedule['team'], fn($member) => $member['role'] === 'Team Leader');
                            $team_members = array_filter($schedule['team'], fn($member) => $member['role'] !== 'Team Leader');
                            ?>
                            <p>TEAM LEADER</p>
                            <div style="height: 20px;"></div>
                            <ul style="list-style-type: none; margin-left: 30px; font-size: 18px">
                                <?php if (!empty($team_leaders)): ?>
                                    <?php foreach ($team_leaders as $team_leader): ?>
                                        <li style="font-weight: bold"><?php echo htmlspecialchars($team_leader['name']); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>NO TEAM LEADER ASSIGNED</li>
                                <?php endif; ?>
                            </ul>
                            <div style="height: 35px;"></div>
                            <p>TEAM MEMBERS</p>
                            <div style="height: 20px;"></div>
                            <ul style="list-style-type: none; margin-left: 30px; font-size: 18px">
                                <?php if (!empty($team_members)): ?>
                                    <?php foreach ($team_members as $team_member): ?>
                                        <li style="margin-bottom: 20px"><?php echo htmlspecialchars($team_member['name']); ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>NO TEAM MEMBERS ASSIGNED</li>
                                <?php endif; ?>
                            </ul>
                            
                        </div>
                    </div>
                    </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; font-size: 20px"><strong>NO SCHEDULED INTERNAL ACCREDITATION HAS BEEN ASSIGNED TO YOUR COLLEGE</strong></p>
            <?php endif; ?>
    </div>
    </div>
    <div id="orientationModal" class="orientationmodal" style="display: none;">
        <div class="orientationmodal-content">
            <h2>REQUEST ORIENTATION</h2>
            <form id="orientationForm" action="internal_orientation_process.php" method="POST">
                <input type="hidden" name="schedule_id" id="modal_schedule_id">
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>ORIENTATION DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="date" id="orientation_date" name="orientation_date" required>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="time" id="orientation_time" name="orientation_time" required>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>MODE OF ORIENTATION</strong></label>
                    </div>
                </div>
                <div class="internal-external">
                    <div class="internal-externalSelect">
                        <input type="radio" id="online" name="mode" value="online" checked onclick="toggleMode('online')">
                        <label style="margin-right: 5px;" for="online"><strong>ONLINE</strong></label>
                        <input type="radio" id="f2f" name="mode" value="f2f" onclick="toggleMode('f2f')">
                        <label for="f2f"><strong>FACE TO FACE</strong></label>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div id="onlineFields">
                    <div class="form-group">
                        <label for="orientation_link">Orientation Link:</label>
                        <input type="text" id="orientation_link" name="orientation_link" required>
                    </div>
                    <div class="form-group">
                        <label for="link_passcode">Link Passcode:</label>
                        <input type="text" id="link_passcode" name="link_passcode" required>
                    </div>
                </div>
                <div id="f2fFields" style="display: none;">
                    <div class="form-group">
                        <label for="orientation_building">Orientation Building:</label>
                        <input type="text" id="orientation_building" name="orientation_building">
                    </div>
                    <div class="form-group">
                        <label for="room_number">Room Number:</label>
                        <input type="text" id="room_number" name="room_number">
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="closeModal()">CLOSE</button>
                    <button class="submit-button1" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="logoutModal" class="modal1">
        <div class="modal-content1">
            <h4 id="confirmationMessage" style="font-size: 20px;">Are you sure you want to logout?</h4>
            <div class="button-container">
                <button type="button" class="accept-back-button" id="backButton" onclick="cancelLogout()">CANCEL</button>
                <button type="button" class="accept-confirm-button" id="confirmButton" onclick="confirmLogout()">CONFIRM</button>
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

        function toggleNotifications() {
            var dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }
        function openModal(scheduleId) {
            // Set the schedule ID for the form (if needed)
            document.getElementById('modal_schedule_id').value = scheduleId;
            
            // Get the modal element
            const modal = document.getElementById('orientationModal');
            
            // Make the modal visible by changing its display to 'flex'
            modal.style.display = 'flex';
        }

        function closeModal() {
            // Get the modal element
            const modal = document.getElementById('orientationModal');
            
            // Hide the modal by setting its display to 'none'
            modal.style.display = 'none';
        }

        function toggleMode(mode) {
            if (mode === 'online') {
                document.getElementById('onlineFields').style.display = 'block';
                document.getElementById('f2fFields').style.display = 'none';
                document.getElementById('orientation_link').required = true;
                document.getElementById('link_passcode').required = true;
                document.getElementById('orientation_building').required = false;
                document.getElementById('room_number').required = false;
            } else {
                document.getElementById('onlineFields').style.display = 'none';
                document.getElementById('f2fFields').style.display = 'block';
                document.getElementById('orientation_link').required = false;
                document.getElementById('link_passcode').required = false;
                document.getElementById('orientation_building').required = true;
                document.getElementById('room_number').required = true;
            }
        }
    </script>
</body>
</html>

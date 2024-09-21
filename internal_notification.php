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
if ($user_id === 'admin' && basename($_SERVER['PHP_SELF']) !== 'admin_sidebar.php') {
    header("Location: admin_sidebar.php");
    exit();
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal_notification.php') {
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

$accreditor_type = (substr($user_id, 3, 2) == '11') ? 'Internal Accreditor' : 'External Accreditor';

// Fetch notifications for the logged-in user
$sql_notifications = "
    SELECT s.id AS schedule_id, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, 
        t.id AS team_id, t.role, t.status AS team_status, c.college_name, 
        GROUP_CONCAT(a.area_name SEPARATOR ', ') AS assigned_area_names
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    JOIN program p ON s.program_id = p.id
    JOIN college c ON s.college_code = c.code
    LEFT JOIN team_areas ta ON t.id = ta.team_id
    LEFT JOIN area a ON ta.area_id = a.id
    WHERE t.internal_users_id = ? AND t.status = 'pending' AND s.schedule_status NOT IN ('cancelled', 'finished')
    GROUP BY t.id
";

$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->store_result();
$notification_count = $stmt_notifications->num_rows;
$stmt_notifications->bind_result($schedule_id, $program_name, $level_applied, $schedule_date, $schedule_time, $schedule_status, $team_id, $role, $team_status, $college_name, $assigned_area_names);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Notifications</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div style="height: 10px; width: 0px;"></div>
        <div class="container">
            <div class="header1">
                <div class="nav-list">
                    <a href="internal.php" class="profile1">Profile <i class="fa-regular fa-user"></i></a>
                    <a href="internal_notification.php" class="active orientation1" style="position: relative;">
                        NOTIFICATION<i class="fa-regular fa-bell" style="position: relative;">
                            <?php if ($notification_count > 0): ?>
                                <span id="notificationCount" class="notification-count"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </i>
                    </a>
                    <a href="internal_assessment.php" class="assessment1">Assessment<i class="fa-solid fa-medal"></i></a>
                    <a href="internal_orientation.php" class="orientation1">Orientation<i class="fa-regular fa-calendar"></i></a>
                    <a class="logout" onclick="openLogoutModal()"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div style="height: 32px;"></div>
        <div class="orientation2">
            <div class="notification-list">
                <?php
                if ($stmt_notifications->num_rows > 0):
                    while ($stmt_notifications->fetch()): ?>
                        <?php
                        // Format the schedule date and time
                        $date = new DateTime($schedule_date);
                        $time = new DateTime($schedule_time);
                        
                        // Determine status color
                        $status_color = '';
                        if ($schedule_status === 'pending') {
                            $status_color = '#E6A33E'; // Pending color
                        } elseif ($schedule_status === 'approved') {
                            $status_color = '#34C759'; // Approved color
                        }
                        ?>
                        <div class="notification">
                            <p><strong>College:</strong> <strong class="status" style="color: <?php echo $status_color; ?>;"><?php echo htmlspecialchars($schedule_status); ?></strong><br><?php echo htmlspecialchars($college_name); ?></p><br>
                            <p><strong>Program:</strong><br><?php echo htmlspecialchars($program_name); ?></p><br>
                            <p><strong>Level Applied:</strong> <?php echo htmlspecialchars($level_applied); ?></p><br>
                            <p><strong>Date:</strong> <?php echo $date->format('F j, Y'); ?> | <?php echo $time->format('g:i A'); ?></p><br>
                            <div class="role-area">
                                <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?><br><strong>Areas:</strong> <?php echo htmlspecialchars($assigned_area_names); ?></p>
                                <div class="notification-actions">
                                    <?php if ($role === 'Team Leader'): ?>
                                        <form id="actionForm-<?php echo $team_id; ?>" action="internal_notification_process.php" method="POST">
                                            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                                            <input type="hidden" name="action" id="action-<?php echo $team_id; ?>" value="">
                                            <button type="button" class="decline-button" onclick="confirmAction('<?php echo $team_id; ?>', 'decline')">DECLINE</button>
                                            <button type="button" class="accept-button" onclick="confirmAction('<?php echo $team_id; ?>', 'accept')">ACCEPT</button>
                                        </form>
                                    <?php else: ?>
                                        <form id="actionForm-<?php echo $team_id; ?>" action="internal_notification_process.php" method="POST">
                                            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                                            <input type="hidden" name="action" id="action-<?php echo $team_id; ?>" value="">
                                            <button type="button" class="decline-button" onclick="confirmAction('<?php echo $team_id; ?>', 'decline')">DECLINE</button>
                                            <button type="button" class="accept-button" onclick="confirmAction('<?php echo $team_id; ?>', 'accept')">ACCEPT</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <p style="text-align: center; font-size: 20px"><strong>NO SCHEDULED INTERNAL ACCREDITATION HAS BEEN ASSIGNED TO YOU</strong></p>
                <?php endif; ?>                              
            </div>
            <?php $stmt_notifications->close(); ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal1">
        <div class="modal-content1">
            <p id="confirmationMessage"></p>
            <div class="button-container">
                <button type="button" class="" id="backButton" onclick="closeConfirmationModal()">BACK</button>
                <button type="button" class="" id="confirmButton">CONFIRM</button>
            </div>
        </div>
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
        
        function openModal(scheduleId, teamId) {
            document.getElementById('modal_schedule_id').value = scheduleId;
            document.getElementById('modal_team_id').value = teamId;

            // Fetch team members for the schedule
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_team_members.php?schedule_id=' + scheduleId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var teamMembers = JSON.parse(xhr.responseText);
                    var teamMembersContainer = document.getElementById('teamMembers');
                    teamMembersContainer.innerHTML = '';

                    teamMembers.forEach(function(member) {
                        var div = document.createElement('div');
                        div.className = 'form-group';
                        
                        // Check if the member is a team leader
                        var isTeamLeader = member.role.toLowerCase() === 'team leader';

                        // Create the input element
                        var inputElement = '<input type="text" name="areas[' + member.id + ']" placeholder="ASSIGN AREA"';
                        
                        // If not a team leader, make the input required
                        if (!isTeamLeader) {
                            inputElement += ' required';
                        }

                        inputElement += '>';

                        div.innerHTML = '<label>' + member.name + ' (' + member.role + ')</label>' + inputElement;
                        teamMembersContainer.appendChild(div);
                    });

                    document.getElementById('assignModal').style.display = 'block';
                }
            };
            xhr.send();
        }


        function closeModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        function confirmAction(teamId, action) {
            var confirmationMessage = "Are you sure you want to " + action + " this schedule?";
            document.getElementById('confirmationMessage').innerText = confirmationMessage;

            var confirmButton = document.getElementById('confirmButton');
            var backButton = document.getElementById('backButton');

            // Apply styles based on action
            if (action === 'accept') {
                confirmButton.className = 'accept-confirm-button';
                backButton.className = 'accept-back-button';
            } else if (action === 'decline') {
                confirmButton.className = 'decline-confirm-button';
                backButton.className = 'decline-back-button';
            }

            confirmButton.onclick = function() {
                document.getElementById('action-' + teamId).value = action;
                document.getElementById('actionForm-' + teamId).submit();
            };

            document.getElementById('confirmationModal').style.display = 'block';
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            window.location.href = 'internal_notification.php'; // Redirect to internal.php on BACK
        }

        // Get the modal
        var modal = document.getElementById("confirmationModal");

        // Function to close the modal
        function closeConfirmationModal() {
            modal.style.display = "none";
        }

        // Close the modal when clicking anywhere outside of the modal content
        window.onclick = function(event) {
            if (event.target === modal) {
                closeConfirmationModal();
            }
        }

        // Display the notification count
        const notificationCount = <?php echo $notification_count; ?>;
        if (notificationCount > 0) {
            document.getElementById('notificationCount').innerText = notificationCount;
        }
    </script>
</body>
</html>

<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
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

$accreditor_type = (substr($user_id, 3, 2) == '11') ? 'Internal Accreditor' : 'External Accreditor';

// Fetch notifications for the logged-in user
$sql_notifications = "
    SELECT s.id AS schedule_id, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, t.id AS team_id, t.role, t.area, t.status, t.internal_users_id, c.college_name
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    JOIN program p ON s.program_id = p.id
    JOIN college c ON s.college_code = c.code
    WHERE t.internal_users_id = ? AND t.status = 'pending' AND s.schedule_status NOT IN ('cancelled', 'finished')
";

$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->store_result();
$stmt_notifications->bind_result($schedule_id, $program_name, $level_applied, $schedule_date, $schedule_time, $schedule_status, $team_id, $role, $area, $team_status, $internal_users_id, $college_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Notifications</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
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
    </div>
    <a href="internal.php" class="back-link">< BACK</a>
    <div class="container">
        <div class="notifications1">
            <h2>NOTIFICATIONS</h2>
            <div class="notification-list">
                <?php while ($stmt_notifications->fetch()): ?>
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
                            <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?><br><strong>Area:</strong> <?php echo htmlspecialchars($area); ?></p>
                            <div class="notification-actions">
                                <?php if ($role === 'team leader'): ?>
                                    <form action="internal_notification_process.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                                        <button type="submit" class="decline-button" name="action" value="decline">DECLINE</button>
                                        <button type="button" class="accept-button" onclick="openModal(<?php echo $schedule_id; ?>, <?php echo $team_id; ?>)">ACCEPT</button>
                                    </form>
                                <?php else: ?>
                                    <form action="internal_notification_process.php" method="POST">
                                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                                        <button type="submit" class="decline-button" name="action" value="decline">DECLINE</button>
                                        <button type="submit" class="accept-button" name="action" value="accept">ACCEPT</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php $stmt_notifications->close(); ?>
        </div>
    </div>

    <!-- Modal for team leader to assign areas -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Assign Areas</h2>
            <form id="assignForm" action="assign_areas_process.php" method="POST">
                <input type="hidden" name="schedule_id" id="modal_schedule_id">
                <input type="hidden" name="team_id" id="modal_team_id">
                <div id="teamMembers">
                    <!-- Team members' areas will be dynamically added here -->
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
                        div.innerHTML = '<label>' + member.name + ' (' + member.role + ')</label><input type="text" name="areas[' + member.id + ']" placeholder="Assign area">';
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
    </script>
</body>
</html>

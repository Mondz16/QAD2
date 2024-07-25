<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the logged-in user
$sql_notifications = "
    SELECT s.id AS schedule_id, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, t.id AS team_id, t.role, t.area, t.status, t.internal_users_id
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    JOIN program p ON s.program_id = p.id
    WHERE t.internal_users_id = ? AND t.status = 'pending' AND s.schedule_status NOT IN ('cancelled', 'finished')
";

$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->store_result();
$stmt_notifications->bind_result($schedule_id, $program_name, $level_applied, $schedule_date, $schedule_time, $schedule_status, $team_id, $role, $area, $team_status, $internal_users_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Notifications</title>
    <link rel="stylesheet" href="loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            text-align: center;
            padding: 20px;
            background-color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .wrapper header {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .site-header {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .site-header nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: center;
        }
        .site-header nav ul li {
            display: inline;
            margin: 0 10px;
        }
        .site-header nav ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            background-color: #444;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .site-header nav ul li a:hover {
            background-color: #555;
        }
        .notifications {
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .notification {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .notification p {
            margin: 0;
        }
        .notification small {
            color: #666;
        }
        .notification form {
            margin-top: 10px;
        }
        .notification form button {
            margin-right: 10px;
            background-color: #5cb85c;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .notification form button:hover {
            background-color: #4cae4c;
        }
        .notification form button[name="action"][value="decline"] {
            background-color: #d9534f;
        }
        .notification form button[name="action"][value="decline"]:hover {
            background-color: #c9302c;
        }
        .back-btn {
            margin-top: 20px;
            text-align: center;
        }
        .back-btn .btn {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-btn .btn:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>Internal Accreditor</header>
    </div>
    <header class="site-header">
        <nav>
            <ul class="nav-list">
                <li class="btn"><a href="internal.php">Home</a></li>
                <li class="btn"><a href="internal_notification.php">Notifications</a></li>
                <li class="btn"><a href="internal_orientation.php">Orientation</a></li>
                <li class="btn"><a href="internal_assessment.php">Assessment</a></li>
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <div class="notifications">
        <h2>Notifications</h2>
        <?php while ($stmt_notifications->fetch()): ?>
            <div class="notification">
                <p><?php echo "Program: " . htmlspecialchars($program_name) . "<br>Level Applied: " . htmlspecialchars($level_applied); ?></p>
                <p><?php echo "Date: " . htmlspecialchars($schedule_date) . "<br>Time: " . htmlspecialchars($schedule_time); ?></p>
                <p><?php echo "Role: " . htmlspecialchars($role) . "<br>Area: " . htmlspecialchars($area); ?></p>
                <p>Status: <?php echo htmlspecialchars($team_status); ?></p><br>
                <?php if ($role === 'team leader'): ?>
                    <button type="button" onclick="openModal(<?php echo $schedule_id; ?>, <?php echo $team_id; ?>)">Accept</button>
                    <form action="internal_notification_process.php" method="POST" style="display:inline;">
                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <button type="submit" name="action" value="decline">Decline</button>
                    </form>
                <?php else: ?>
                    <form action="internal_notification_process.php" method="POST">
                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <button type="submit" name="action" value="accept">Accept</button>
                        <button type="submit" name="action" value="decline">Decline</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        <?php $stmt_notifications->close(); ?>
        <div class="back-btn">
            <a href="internal.php" class="btn">Back to Home</a>
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
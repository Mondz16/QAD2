<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the college code of the logged-in user
$sql_college_code = "SELECT college_code FROM internal_users WHERE user_id = ?";
$stmt_college_code = $conn->prepare($sql_college_code);
$stmt_college_code->bind_param("s", $user_id);
$stmt_college_code->execute();
$stmt_college_code->bind_result($college_code);
$stmt_college_code->fetch();
$stmt_college_code->close();

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
        (SELECT COUNT(*) FROM orientation o WHERE o.schedule_id = s.id) AS orientation_requested
    FROM schedule s
    JOIN program p ON s.program_id = p.id
    WHERE s.college_code = ? 
    AND s.schedule_status IN ('pending', 'approved')
    ORDER BY s.schedule_date, s.schedule_time
";

$stmt_schedules = $conn->prepare($sql_schedules);
$stmt_schedules->bind_param("s", $college_code);
$stmt_schedules->execute();
$result_schedules = $stmt_schedules->get_result();

$schedules = [];
while ($row = $result_schedules->fetch_assoc()) {
    if ($row['orientation_requested'] == 0) {
        $schedules[$row['schedule_id']] = [
            'program_name' => $row['program_name'],
            'level_applied' => $row['level_applied'],
            'schedule_date' => $row['schedule_date'],
            'schedule_time' => $row['schedule_time'],
            'schedule_status' => $row['schedule_status'],
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
        .schedules {
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .schedule {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .schedule p {
            margin: 0;
        }
        .schedule small {
            color: #666;
        }
        .schedule form {
            margin-top: 10px;
        }
        .schedule form button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .schedule form button:hover {
            background-color: #0056b3;
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
            left: 50%;
            top: 20%;
            transform: translate(-50%, -20%);
            width: 100%;
            max-width: 600px;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15px auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            position: relative;
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
        .form-group input[type="text"],
        .form-group input[type="datetime-local"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group input[type="radio"] {
            margin-right: 5px;
        }
        .form-inline {
            display: flex;
            align-items: center;
        }
        .form-inline label {
            margin: 0 10px 0 0;
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
    <div class="schedules">
        <h2>Orientation Requests</h2>
        <?php if (!empty($schedules)): ?>
            <?php foreach ($schedules as $schedule_id => $schedule): ?>
                <div class="schedule">
                    <p><?php echo "Program: " . htmlspecialchars($schedule['program_name']) . "<br>Level Applied: " . htmlspecialchars($schedule['level_applied']); ?></p>
                    <p><?php 
                        $date = new DateTime($schedule['schedule_date']);
                        echo "Date: " . $date->format('F j, Y'); 
                    ?></p>
                    <p><?php 
                        $time = new DateTime($schedule['schedule_time']);
                        echo "Time: " . $time->format('g:i A'); 
                    ?></p>
                    <p>Status: <?php echo htmlspecialchars($schedule['schedule_status']); ?></p><br>
                    <?php 
                    $team_leaders = array_filter($schedule['team'], fn($member) => $member['role'] === 'team leader');
                    $team_members = array_filter($schedule['team'], fn($member) => $member['role'] !== 'team leader');
                    ?>
                    <p><strong>Team Leader:</strong></p>
                    <ul>
                        <?php if (!empty($team_leaders)): ?>
                            <?php foreach ($team_leaders as $team_leader): ?>
                                <li><?php echo htmlspecialchars($team_leader['name']); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No team leader assigned.</li>
                        <?php endif; ?>
                    </ul>
                    <p><strong>Team Members:</strong></p>
                    <ul>
                        <?php if (!empty($team_members)): ?>
                            <?php foreach ($team_members as $team_member): ?>
                                <li><?php echo htmlspecialchars($team_member['name']); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No team members assigned.</li>
                        <?php endif; ?>
                    </ul>
                    <button onclick="openModal(<?php echo $schedule_id; ?>)">Request Orientation</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No schedules found.</p>
        <?php endif; ?>
        <div class="back-btn">
            <a href="internal.php" class="btn">Back to Home</a>
        </div>
    </div>

    <!-- Modal -->
    <div id="orientationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Request Orientation</h2>
            <form id="orientationForm" action="internal_orientation_process.php" method="POST">
                <input type="hidden" name="schedule_id" id="modal_schedule_id">
                <div class="form-group">
                    <label for="orientation_date">Orientation Date:</label>
                    <input type="date" id="orientation_date" name="orientation_date" required>
                </div>
                <div class="form-group">
                    <label for="orientation_time">Orientation Time:</label>
                    <input type="time" id="orientation_time" name="orientation_time" required>
                </div>
                <div class="form-group form-inline">
                    <input type="radio" id="online" name="mode" value="online" checked onclick="toggleMode('online')">
                    <label for="online">Online</label>
                    <input type="radio" id="f2f" name="mode" value="f2f" onclick="toggleMode('f2f')">
                    <label for="f2f">Face to Face</label>
                </div>
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
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(scheduleId) {
            document.getElementById('modal_schedule_id').value = scheduleId;
            document.getElementById('orientationModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('orientationModal').style.display = 'none';
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

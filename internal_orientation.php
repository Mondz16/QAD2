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
        o.orientation_status
    FROM schedule s
    JOIN program p ON s.program_id = p.id
    LEFT JOIN orientation o ON s.id = o.schedule_id
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
    <link rel="stylesheet" href="style.css">
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
                    <div class="SDMD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/SDMDLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
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
    <div style="height: 30px; width: 0px;"></div>
    <div class="container">
        <div class="profile">
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
            <div class="profile-details">
                <p class="profile-name"><?php echo $first_name . ' ' . $middle_initial . '. ' . $last_name; ?></p>
                <p class="profile-type"><?php echo $college_name; ?> (<?php echo $accreditor_type; ?>)</p>
            </div>
        </div>
    </div>
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
                    <?php if ($schedule['orientation_id']): ?>
                        <?php if ($schedule['orientation_status'] === 'pending'): ?>
                            <p>A request for orientation has been submitted. Please wait for the approval.</p>
                            <p>Orientation Status: <?php echo htmlspecialchars($schedule['orientation_status']); ?></p>
                        <?php elseif ($schedule['orientation_status'] === 'approved'): ?>
                            <p>This orientation request has been approved.</p>
                            <p>Orientation Status: <?php echo htmlspecialchars($schedule['orientation_status']); ?></p>
                        <?php elseif ($schedule['orientation_status'] === 'denied'): ?>
                            <p>This orientation request has been denied. Do you want to request again?</p>
                            <button onclick="openModal(<?php echo $schedule_id; ?>)">Rerequest Orientation</button>
                            <p>Orientation Status: <?php echo htmlspecialchars($schedule['orientation_status']); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <button onclick="openModal(<?php echo $schedule_id; ?>)">Request Orientation</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No schedules found.</p>
        <?php endif; ?>
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
                <div class="form-group form-inline horizontal-radio-group">
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

<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

// Retrieve logged-in user details
$user_id = $_SESSION['user_id'];
$sql_user_details = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
$stmt_user_details = $conn->prepare($sql_user_details);
$stmt_user_details->bind_param("s", $user_id);
$stmt_user_details->execute();
$stmt_user_details->bind_result($first_name, $middle_initial, $last_name);
$stmt_user_details->fetch();
$stmt_user_details->close();

// Query to find team_id based on user's user_id and check the status
$sql_find_team_id = "SELECT id, status FROM team WHERE internal_users_id = ?";
$stmt_find_team_id = $conn->prepare($sql_find_team_id);
$stmt_find_team_id->bind_param("s", $user_id);
$stmt_find_team_id->execute();
$stmt_find_team_id->bind_result($team_id, $status);
$stmt_find_team_id->fetch();
$stmt_find_team_id->close();

$assessment_submitted = false;

if ($team_id && $status === 'accepted') {
    // Retrieve schedule details based on team_id from schedule table
    $sql_get_schedule = "SELECT s.id, c.college_name AS college_name, p.program AS program, s.level_applied, s.schedule_date, s.schedule_time
                         FROM schedule s
                         INNER JOIN team t ON s.id = t.schedule_id
                         INNER JOIN college c ON s.college_id = c.id
                         INNER JOIN program p ON s.program_id = p.id
                         WHERE t.id = ?";
    $stmt_get_schedule = $conn->prepare($sql_get_schedule);
    $stmt_get_schedule->bind_param("i", $team_id);
    $stmt_get_schedule->execute();
    $stmt_get_schedule->bind_result($schedule_id, $college_name, $program, $level_applied, $schedule_date, $schedule_time);
    $stmt_get_schedule->fetch();
    $stmt_get_schedule->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Assessment</title>
    <link rel="stylesheet" href="admin_style.css">
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
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1001;
            border-radius: 10px;
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
                <li><a href="internal_notification.php">Notification</a></li>
                <li><a href="internal_assessment.php">Assessment</a></li>
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <div class="notifications">
        <h1>Internal Assessment Details</h1>
        <?php if ($assessment_submitted): ?>
            <p>Assessment has already been submitted.</p>
        <?php elseif (isset($schedule_id)): ?>
            <div>
                <p><strong>College:</strong> <?php echo $college_name; ?></p>
                <p><strong>Program:</strong> <?php echo $program; ?></p>
                <p><strong>Level Applied:</strong> <?php echo $level_applied; ?></p>
                <p><strong>Schedule Date:</strong> <?php echo $schedule_date; ?></p>
                <p><strong>Schedule Time:</strong> <?php echo $schedule_time; ?></p>
            </div>
            <button onclick="openPopup()">Assessment</button>

            <!-- Popup Form -->
            <div class="overlay" id="overlay"></div>
            <div class="popup" id="popup">
                <h2>Assessment Form</h2>

                <form action="internal_assessment_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                    <label for="college">College:</label>
                    <input type="text" id="college" name="college" value="<?php echo $college_name; ?>" readonly><br><br>
                    <label for="program">Program:</label>
                    <input type="text" id="program" name="program" value="<?php echo $program; ?>" readonly><br><br>
                    <label for="level">Level Applied:</label>
                    <input type="text" id="level" name="level" value="<?php echo $level_applied; ?>" readonly><br><br>
                    <label for="date">Schedule Date:</label>
                    <input type="text" id="date" name="date" value="<?php echo $schedule_date; ?>" readonly><br><br>
                    <label for="result">Result:</label>
                    <input type="text" id="result" name="result" required><br><br>
                    <label for="area_evaluated">Area Evaluated:</label>
                    <input type="text" id="area_evaluated" name="area_evaluated" required><br><br>
                    <label for="findings">Findings:</label>
                    <textarea id="findings" name="findings" rows="4" required></textarea><br><br>
                    <label for="recommendations">Recommendations:</label>
                    <textarea id="recommendations" name="recommendations" rows="4" required></textarea><br><br>
                    <label for="evaluator">Evaluator:</label>
                    <input type="text" id="evaluator" name="evaluator" required><br><br>
                    <label for="evaluator_signature">Evaluator Signature (PNG format):</label>
                    <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                    <button type="submit">Submit Assessment</button>
                </form>
                <button onclick="closePopup()">Close</button>
            </div>
        <?php else: ?>
            <p>No accepted schedule found for the logged-in user.</p>
        <?php endif; ?>
    </div>
    <script>
        function openPopup() {
            document.getElementById("overlay").style.display = "block";
            document.getElementById("popup").style.display = "block";
        }

        function closePopup() {
            document.getElementById("overlay").style.display = "none";
            document.getElementById("popup").style.display = "none";
        }
    </script>
</body>
</html>

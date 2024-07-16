<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

// Check if the assessment has been submitted
$assessment_submitted = isset($_SESSION['assessment_submitted']) && $_SESSION['assessment_submitted'];

// Retrieve schedule details from URL parameters
if (isset($_GET['college'], $_GET['program'], $_GET['level'], $_GET['date'], $_GET['time'])) {
    $college = $_GET['college'];
    $program = $_GET['program'];
    $level = $_GET['level'];
    $date = $_GET['date'];
    $time = $_GET['time'];
}

// Get logged-in user details
$user_id = $_SESSION['user_id'];
$sql_user_details = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
$stmt_user_details = $conn->prepare($sql_user_details);
$stmt_user_details->bind_param("s", $user_id);
$stmt_user_details->execute();
$stmt_user_details->bind_result($first_name, $middle_initial, $last_name);
$stmt_user_details->fetch();
$stmt_user_details->close();

// Query to check if user's name matches a team member's name with accepted status
$sql_check_team = "SELECT s.id, s.college, s.program, s.level_applied, s.schedule_date, s.schedule_time
                   FROM team t
                   INNER JOIN schedule s ON t.schedule_id = s.id
                   WHERE t.fname = ? AND t.mi = ? AND t.lname = ? AND t.status = 'accepted'";
$stmt_check_team = $conn->prepare($sql_check_team);
$stmt_check_team->bind_param("sss", $first_name, $middle_initial, $last_name);
$stmt_check_team->execute();
$stmt_check_team->bind_result($schedule_id, $team_college, $team_program, $team_level, $team_date, $team_time);

// Fetch the first matching row (assuming there should only be one match)
$stmt_check_team->fetch();
$stmt_check_team->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Assessment</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Styles for popup form and overlay */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            z-index: 1000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .readonly-input {
            border: none;
            background-color: #f0f0f0;
            padding: 5px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<header class="site-header">
    <nav>
        <ul class="nav-list">
            <li><a href="internal_notification.php">Notification</a></li>
            <li><a href="internal_assessment.php">Assessment</a></li>
            <li class="btn"><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>
<div class="admin-content">
    <h1>Internal Assessment Details</h1>
    <?php if ($assessment_submitted): ?>
    <p>Assessment has already been submitted.</p>
    <?php elseif (isset($team_college)): ?>
    <div>
        <p><strong>College:</strong> <?php echo $team_college; ?></p>
        <p><strong>Program:</strong> <?php echo $team_program; ?></p>
        <p><strong>Level Applied:</strong> <?php echo $team_level; ?></p>
        <p><strong>Schedule Date:</strong> <?php echo $team_date; ?></p>
        <p><strong>Schedule Time:</strong> <?php echo $team_time; ?></p>
    </div>
    <button onclick="openPopup()">Assessment</button>

    <!-- Popup Form -->
    <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
        <h2>Assessment Form</h2>
        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
            
        <label for="college">College:</label>
        <input type="text" id="college" name="college" value="<?php echo $team_college; ?>" readonly><br><br>
        
        <label for="program">Program:</label>
        <input type="text" id="program" name="program" value="<?php echo $team_program; ?>" readonly><br><br>
        
        <label for="level">Level Applied:</label>
        <input type="text" id="level" name="level" value="<?php echo $team_level; ?>" readonly><br><br>
        
        <label for="date">Schedule Date:</label>
        <input type="text" id="date" name="date" value="<?php echo $team_date; ?>" readonly><br><br>

        <form action="internal_assessment_process.php" method="POST" enctype="multipart/form-data">
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
    <?php else: ?>
    <p>No accepted schedule found for the logged-in user.</p>
    <?php endif; ?>
</div>
</body>
</html>

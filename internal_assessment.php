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

$full_name = $first_name . ' ' . $middle_initial . ' ' . $last_name;

// Query to find team_id based on user's user_id and check the status
$sql_find_team_id = "SELECT id FROM team WHERE internal_users_id = ?";
$stmt_find_team_id = $conn->prepare($sql_find_team_id);
$stmt_find_team_id->bind_param("s", $user_id);
$stmt_find_team_id->execute();
$stmt_find_team_id->bind_result($team_id);
$stmt_find_team_id->fetch();
$stmt_find_team_id->close();

$schedule_id = null;
$schedule_status = null;

if ($team_id) {
    // Get the schedule_id and status from the schedule table
    $sql_get_schedule_status = "SELECT s.id, s.schedule_status 
                                FROM schedule s
                                INNER JOIN team t ON s.id = t.schedule_id
                                WHERE t.id = ?";
    $stmt_get_schedule_status = $conn->prepare($sql_get_schedule_status);
    $stmt_get_schedule_status->bind_param("i", $team_id);
    $stmt_get_schedule_status->execute();
    $stmt_get_schedule_status->bind_result($schedule_id, $schedule_status);
    $stmt_get_schedule_status->fetch();
    $stmt_get_schedule_status->close();
}

$assessment_submitted = false;
$assessment_file = null;
$summary_submitted = false;
$summary_file = null;

if ($schedule_status === 'done') {
    // Check if assessment has already been submitted for the team
    $sql_check_assessment = "SELECT assessment_file FROM assessment WHERE team_id = ?";
    $stmt_check_assessment = $conn->prepare($sql_check_assessment);
    $stmt_check_assessment->bind_param("i", $team_id);
    $stmt_check_assessment->execute();
    $stmt_check_assessment->bind_result($assessment_file);
    $assessment_submitted = $stmt_check_assessment->fetch();
    $stmt_check_assessment->close();

    // Check if assessment has already been submitted for the team
    $sql_check_summary = "SELECT summary_file FROM summary WHERE team_id = ?";
    $stmt_check_summary = $conn->prepare($sql_check_summary);
    $stmt_check_summary->bind_param("i", $team_id);
    $stmt_check_summary->execute();
    $stmt_check_summary->bind_result($summary_file);
    $summary_submitted = $stmt_check_summary->fetch();
    $stmt_check_summary->close();
}

$college_name = null;
$program = null;
$level_applied = null;
$schedule_date = null;
$schedule_time = null;

if ($schedule_id && $schedule_status === 'done') {
    // Retrieve schedule details based on team_id from schedule table
    $sql_get_schedule = "SELECT c.college_name AS college_name, p.program AS program, s.level_applied, s.schedule_date, s.schedule_time
                         FROM schedule s
                         INNER JOIN team t ON s.id = t.schedule_id
                         INNER JOIN college c ON s.college_id = c.id
                         INNER JOIN program p ON s.program_id = p.id
                         WHERE t.id = ?";
    $stmt_get_schedule = $conn->prepare($sql_get_schedule);
    $stmt_get_schedule->bind_param("i", $team_id);
    $stmt_get_schedule->execute();
    $stmt_get_schedule->bind_result($college_name, $program, $level_applied, $schedule_date, $schedule_time);
    $stmt_get_schedule->fetch();
    $stmt_get_schedule->close();
}

// Fetch the status of the logged-in user
$user_status = null;

$sql_get_user_status = "SELECT status FROM team WHERE internal_users_id = ?";
$stmt_get_user_status = $conn->prepare($sql_get_user_status);
$stmt_get_user_status->bind_param("s", $user_id);
$stmt_get_user_status->execute();
$stmt_get_user_status->bind_result($user_status);
$stmt_get_user_status->fetch();
$stmt_get_user_status->close();

// Fetch the role of the logged-in user
$user_role = null;

$sql_get_user_role = "SELECT role FROM team WHERE internal_users_id = ?";
$stmt_get_user_role = $conn->prepare($sql_get_user_role);
$stmt_get_user_role->bind_param("s", $user_id);
$stmt_get_user_role->execute();
$stmt_get_user_role->bind_result($user_role);
$stmt_get_user_role->fetch();
$stmt_get_user_role->close();

// Additional logic to display assessment files of all team members with the same schedule_id
$team_ids = [];
$assessment_files = [];

if ($schedule_id && $user_role === 'team leader') {
    // Get all team_ids with the same schedule_id
    $sql_get_team_ids = "SELECT id FROM team WHERE schedule_id = ?";
    $stmt_get_team_ids = $conn->prepare($sql_get_team_ids);
    $stmt_get_team_ids->bind_param("i", $schedule_id);
    $stmt_get_team_ids->execute();
    $result_get_team_ids = $stmt_get_team_ids->get_result();
    
    while ($row = $result_get_team_ids->fetch_assoc()) {
        $team_ids[] = $row['id'];
    }
    $stmt_get_team_ids->close();

    if ($schedule_id && $schedule_status === 'done' && $user_role === 'team leader') {
    // Get all team members' assessment files with the same schedule_id
    $sql_get_team_members = "SELECT t.id, a.assessment_file 
                             FROM team t
                             LEFT JOIN assessment a ON t.id = a.team_id
                             WHERE t.schedule_id = ? AND t.role = 'team member'";
    $stmt_get_team_members = $conn->prepare($sql_get_team_members);
    $stmt_get_team_members->bind_param("i", $schedule_id);
    $stmt_get_team_members->execute();
    $result_get_team_members = $stmt_get_team_members->get_result();
    
    while ($row = $result_get_team_members->fetch_assoc()) {
        if ($row['assessment_file']) {
            $assessment_files[$row['id']] = $row['assessment_file'];
        }
    }
    $stmt_get_team_members->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Assessment</title>
    <link rel="stylesheet" href="loginstyle.css">
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
        .overlay, .Summaryoverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }
        .popup, .Summarypopup {
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
                <li class="btn"><a href="internal.php">Home</a></li>
                <li class="btn"><a href="internal_notification.php">Notification</a></li>
                <li class="btn"><a href="internal_assessment.php">Assessment</a></li>
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <div class="notifications">
        <h1>Internal Assessment Details</h1>
        <div class="notification">
            <?php if ($schedule_status === 'pending'): ?>
                <p>Please wait for the scheduled assessment to be completed.</p>
            <?php elseif ($schedule_status === 'done'): ?>
                <?php if ($user_status === 'pending'): ?>
                    <p>No assessment schedule accepted.</p>
                <?php elseif ($user_role === 'team member' && $assessment_submitted && isset($schedule_id)): ?>
                    <h3>Assessment Form</h3>
                <div>
                    <p><strong>College:</strong> <?php echo $college_name; ?></p>
                    <p><strong>Program:</strong> <?php echo $program; ?></p>
                    <p><strong>Level Applied:</strong> <?php echo $level_applied; ?></p>
                    <p><strong>Schedule Date:</strong> <?php echo $schedule_date; ?></p>
                    <p><strong>Schedule Time:</strong> <?php echo $schedule_time; ?></p>
                </div>
                <p>This schedule's assessment has already been submitted.</p>
                <a href="<?php echo $assessment_file; ?>" download>Download Assessment PDF</a> <button onclick="openPopup()">Reassess?</button>

                <!-- Popup Form -->
                <div class="overlay" id="overlay"></div>
                <div class="popup" id="popup">
                    <h2>Assessment Form</h2>

                    <form action="internal_reassessment_process.php" method="POST" enctype="multipart/form-data">
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
                        <input type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly   ><br><br>
                        <label for="evaluator_signature">Evaluator Signature (PNG format):</label>
                        <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                        <button type="submit">Submit Assessment</button>
                    </form>
                    <button onclick="closePopup()">Close</button>
                </div>
        </div>
        <?php endif; ?>

        <div class="notification">
            <?php if ($summary_submitted && isset($schedule_id)): ?>
                <h3>Summary Form</h3>
                <div>
                    <p><strong>College:</strong> <?php echo $college_name; ?></p>
                    <p><strong>Program:</strong> <?php echo $program; ?></p>
                    <p><strong>Level Applied:</strong> <?php echo $level_applied; ?></p>
                    <p><strong>Schedule Date:</strong> <?php echo $schedule_date; ?></p>
                    <p><strong>Schedule Time:</strong> <?php echo $schedule_time; ?></p>
                </div>
                <p>This schedule's assessment has already been submitted.</p>
                <a href="<?php echo $summary_file; ?>" download>Download Summary PDF</a> <button onclick="SummaryopenPopup()">Reassess?</button>

                <div class="Summaryoverlay" id="Summaryoverlay"></div>
                <div class="Summarypopup" id="Summarypopup">
                    <h2>Assessment Form</h2>
                    <form action="internal_resummary_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <label for="college">College:</label>
                        <input type="text" id="college" name="college" value="<?php echo $college_name; ?>" readonly><br><br>
                        <label for="program">Program:</label>
                        <input type="text" id="program" name="program" value="<?php echo $program; ?>" readonly><br><br>
                        <label for="level">Level Applied:</label>
                        <input type="text" id="level" name="level" value="<?php echo $level_applied; ?>" readonly><br><br>
                        <label for="date">Schedule Date:</label>
                        <input type="text" id="date" name="date" value="<?php echo $schedule_date; ?>" readonly><br><br>
                        <label for="area_evaluated">Areas</label>
                        <textarea id="areas" name="areas" rows="4" required></textarea><br><br>
                        <label for="result">Results:</label>
                        <textarea id="results" name="results" rows="4" required></textarea><br><br>
                        <label for="evaluator">Evaluator:</label>
                        <input type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly   ><br><br>
                        <label for="evaluator_signature">Team Leader Signature (PNG format):</label>
                        <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                        <button type="submit">Submit Assessment</button>
                    </form>
                    <button onclick="SummaryclosePopup()">Close</button>
                </div>
        </div>

                <div class="notification">
                    <h3>Team Members' Assessments:</h3>
                    <ul>
                        <?php foreach ($assessment_files as $file): ?>
                            <li><a href="uploads/<?php echo $file; ?>" target="_blank"><?php echo $file; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="notification">
                <?php elseif ($user_role === 'team leader' && isset($schedule_id)): ?>
                    <h2>Summary Form</h2>
                    <div>
                        <p><strong>College:</strong> <?php echo $college_name; ?></p>
                        <p><strong>Program:</strong> <?php echo $program; ?></p>
                        <p><strong>Level Applied:</strong> <?php echo $level_applied; ?></p>
                        <p><strong>Schedule Date:</strong> <?php echo $schedule_date; ?></p>
                        <p><strong>Schedule Time:</strong> <?php echo $schedule_time; ?></p>
                    </div>
                    <button onclick="SummaryopenPopup()">Assess</button>

                    <!-- Popup Form -->
                    <div class="Summaryoverlay" id="Summaryoverlay"></div>
                    <div class="Summarypopup" id="Summarypopup">
                        <h2>Assessment Form</h2>
                        <form action="internal_summary_assessment_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                            <label for="college">College:</label>
                            <input type="text" id="college" name="college" value="<?php echo $college_name; ?>" readonly><br><br>
                            <label for="program">Program:</label>
                            <input type="text" id="program" name="program" value="<?php echo $program; ?>" readonly><br><br>
                            <label for="level">Level Applied:</label>
                            <input type="text" id="level" name="level" value="<?php echo $level_applied; ?>" readonly><br><br>
                            <label for="date">Schedule Date:</label>
                            <input type="text" id="date" name="date" value="<?php echo $schedule_date; ?>" readonly><br><br>
                            <label for="area_evaluated">Areas</label>
                            <textarea id="areas" name="areas" rows="4" required></textarea><br><br>
                            <label for="result">Results:</label>
                            <textarea id="results" name="results" rows="4" required></textarea><br><br>
                            <label for="evaluator">Evaluator:</label>
                            <input type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly   ><br><br>
                            <label for="evaluator_signature">Team Leader Signature (PNG format):</label>
                            <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                            <button type="submit">Submit Assessment</button>
                        </form>
                        <button onclick="SummaryclosePopup()">Close</button>
                    </div>
                </div>
                <div class="notification">
                            <h3>Team Members' Assessments:</h3>
                            <ul>
                                <?php foreach ($assessment_files as $file): ?>
                                    <li><a href="uploads/<?php echo $file; ?>" target="_blank"><?php echo $file; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                    <?php endif; ?>
                </div>

                <div class="notification">
                <?php if ($user_role === 'team member' && isset($schedule_id) && $user_status === 'accepted' && !$assessment_submitted && $schedule_status === 'done'): ?>
                    <h3>Assessment Form</h3>
                    <div>
                        <p><strong>College:</strong> <?php echo $college_name; ?></p>
                        <p><strong>Program:</strong> <?php echo $program; ?></p>
                        <p><strong>Level Applied:</strong> <?php echo $level_applied; ?></p>
                        <p><strong>Schedule Date:</strong> <?php echo $schedule_date; ?></p>
                        <p><strong>Schedule Time:</strong> <?php echo $schedule_time; ?></p>
                    </div>
                    <button onclick="openPopup()">Assess</button>

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
                            <input type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly   ><br><br>
                            <label for="evaluator_signature">Evaluator Signature (PNG format):</label>
                            <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                            <button type="submit">Submit Assessment</button>
                        </form>
                        <button onclick="closePopup()">Close</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No assessment scheduled.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="back-btn">
        <a href="internal_notification.php" class="btn">Back</a>
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

        function SummaryopenPopup() {
            document.getElementById("Summaryoverlay").style.display = "block";
            document.getElementById("Summarypopup").style.display = "block";
        }

        function SummaryclosePopup() {
            document.getElementById("Summaryoverlay").style.display = "none";
            document.getElementById("Summarypopup").style.display = "none";
        }
    </script>
</body>
</html>

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'connection.php';

// Function to display response popup
function showResponsePopup($type, $message)
{
    $imageFile = ($type === 'success') ? 'Success.png' : 'Error.png';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 600px;
            padding: 24px;
            background-color: #fff;
            border-radius: 20px;
            border: 2px solid #AFAFAF;
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>
<body>
    <div class="popup-content">
    <div style="height: 50px; width: 0px;"></div>
        <img src="images/{$imageFile}" height="100" alt="{$type}">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message">{$message}</div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="add_schedule.php" class="btn-hover">OKAY</a>
        <div style="height: 100px; width: 0px;"></div>
        <div class="hairpop-up"></div>
    </div>
</body>
</html>
HTML;
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $collegeId = mysqli_real_escape_string($conn, $_POST['college']);
    $programs = $_POST['program']; // Array of programs
    $levels = $_POST['level-output']; // Array of levels
    $level_validities = $_POST['level_validity']; // Array of validities
    $dates = $_POST['date']; // Array of dates
    $times = $_POST['time']; // Array of times
    $zooms = isset($_POST['zoom']) ? $_POST['zoom'] : array(); // Array of zoom links with null check
    $team_leader_id = mysqli_real_escape_string($conn, $_POST['team_leader']);
    $team_members_ids = $_POST['team_members'];

    // Check college exists
    $sql_college = "SELECT college_name, college_email FROM college WHERE code = ?";
    $stmt_college = $conn->prepare($sql_college);
    $stmt_college->bind_param("s", $collegeId);
    $stmt_college->execute();
    $stmt_college->bind_result($college_name, $college_email);
    $stmt_college->fetch();
    $stmt_college->close();

    if (!$college_name) {
        showResponsePopup('error', 'Error: Invalid college selected.');
        exit;
    }

    // Set timezone
    date_default_timezone_set('Asia/Manila');
    $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $result = $currentDateTime->format('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        $schedule_ids = []; // Store all created schedule IDs
        $email_content = []; // Store program details for email

        // Process each program
        for ($i = 0; $i < count($programs); $i++) {
            $programId = mysqli_real_escape_string($conn, $programs[$i]);
            $level = mysqli_real_escape_string($conn, $levels[$i]);
            $level_validity = mysqli_real_escape_string($conn, $level_validities[$i]);
            $date = mysqli_real_escape_string($conn, $dates[$i]);
            $time = mysqli_real_escape_string($conn, $times[$i]);
            echo "<script>console.log(`$programId | $level | $level_validity | $date | $time`)</script>";

            // Handle empty or null zoom values
            $zoom = '';
            if (isset($zooms[$i]) && $zooms[$i] !== null && trim($zooms[$i]) !== '') {
                $zoom = mysqli_real_escape_string($conn, $zooms[$i]);
            }

            // Check program exists
            $sql_program = "SELECT program_name FROM program WHERE id = ? AND college_code = ?";
            $stmt_program = $conn->prepare($sql_program);
            $stmt_program->bind_param("is", $programId, $collegeId);
            $stmt_program->execute();
            $stmt_program->bind_result($program_name);
            $stmt_program->fetch();
            $stmt_program->close();

            if (!$program_name) {
                throw new Exception('Error: Invalid program selected.');
            }

            // Check for schedule conflicts
            $sql_check_status = "SELECT id FROM schedule 
                                WHERE schedule_date = ? 
                                AND schedule_time = ? 
                                AND college_code != ? 
                                AND schedule_status IN ('approved', 'pending')";

            $stmt_check_date = $conn->prepare($sql_check_status);
            $stmt_check_date->bind_param("sss", $date, $time, $collegeId);
            $stmt_check_date->execute();
            $stmt_check_date->store_result();

            if ($stmt_check_date->num_rows > 0) {
                throw new Exception("Schedule conflict exists for program '$program_name' on selected date and time.");
            }
            $stmt_check_date->close();

            // Insert schedule
            $sql_schedule = "INSERT INTO schedule (college_code, program_id, level_applied, level_validity, 
                            schedule_date, schedule_time, zoom, status_date)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_schedule = $conn->prepare($sql_schedule);
            $stmt_schedule->bind_param(
                "sissssss",
                $collegeId,
                $programId,
                $level,
                $level_validity,
                $date,
                $time,
                $zoom,
                $result
            );
            $stmt_schedule->execute();
            $schedule_id = $stmt_schedule->insert_id;
            $schedule_ids[] = $schedule_id;
            $stmt_schedule->close();
        }

        // Insert team assignments for each schedule
        foreach ($schedule_ids as $schedule_id) {
            // Insert team leader
            $sql_insert_leader = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                    VALUES (?, ?, 'Team Leader', 'pending')";
            $stmt_insert_leader = $conn->prepare($sql_insert_leader);
            $stmt_insert_leader->bind_param("is", $schedule_id, $team_leader_id);
            $stmt_insert_leader->execute();
            $stmt_insert_leader->close();

            // Insert team members
            $sql_insert_members = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                    VALUES (?, ?, 'Team Member', 'pending')";

            foreach ($team_members_ids as $member_id) {
                $stmt_insert_members = $conn->prepare($sql_insert_members);
                $stmt_insert_members->bind_param("is", $schedule_id, $member_id);
                $stmt_insert_members->execute();
                $stmt_insert_members->close();
            }
        }

        $conn->commit();

        showResponsePopup('success', 'New schedules and team members have been successfully created.');
    } catch (Exception $e) {
        $conn->rollback();
        showResponsePopup('error', $e->getMessage());
    }

    $conn->close();
} else {
    header("Location: add_schedule.php");
    exit();
}

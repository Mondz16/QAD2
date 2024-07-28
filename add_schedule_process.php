<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is included
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $collegeId = mysqli_real_escape_string($conn, $_POST['college']);
    $programId = mysqli_real_escape_string($conn, $_POST['program']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $level_validity = mysqli_real_escape_string($conn, $_POST['level_validity']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $team_leader_id = mysqli_real_escape_string($conn, $_POST['team_leader']);
    $team_members_ids = $_POST['team_members'];

    // Check if the collegeId exists in the college table
    $sql_college = "SELECT college_name, college_email FROM college WHERE code = ?";
    $stmt_college = $conn->prepare($sql_college);
    $stmt_college->bind_param("s", $collegeId);
    $stmt_college->execute();
    $stmt_college->bind_result($college_name, $college_email);
    $stmt_college->fetch();
    $stmt_college->close();

    if (!$college_name) {
        echo "Error: Invalid college selected.";
        exit();
    }

    // Check if the programId exists in the program table
    $sql_program = "SELECT program_name FROM program WHERE id = ? AND college_code = ?";
    $stmt_program = $conn->prepare($sql_program);
    $stmt_program->bind_param("is", $programId, $collegeId);
    $stmt_program->execute();
    $stmt_program->bind_result($program_name);
    $stmt_program->fetch();
    $stmt_program->close();

    if (!$program_name) {
        echo "Error: Invalid program selected.";
        exit();
    }

    // Check if the date already exists
    $sql_check_status = "SELECT id FROM schedule WHERE schedule_date = ? AND schedule_status != 'cancelled'";
    $stmt_check_date = $conn->prepare($sql_check_status);
    $stmt_check_date->bind_param("s", $date);
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    if ($stmt_check_date->num_rows > 0) {
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #973939;
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
        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }
        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h2>Operation Result</h2>
        <div class=\"message\">
            Error: Schedule already exists for the selected date.
        </div>
        <button class=\"button-primary\" onclick=\"window.location.href='add_schedule.php'\">OK</button>
    </div>
</body>
</html>";
        $stmt_check_date->close();
        $conn->close();
        exit();
    }

    $stmt_check_date->close();

    date_default_timezone_set('Asia/Manila');
    $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $result = $currentDateTime->format('Y-m-d H:i:s');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into schedule table
        $sql_schedule = "INSERT INTO schedule (college_code, program_id, level_applied, level_validity, schedule_date, schedule_time, status_date)
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_schedule = $conn->prepare($sql_schedule);
        $stmt_schedule->bind_param("sisssss", $collegeId, $programId, $level, $level_validity, $date, $time, $result);
        $stmt_schedule->execute();

        $schedule_id = $stmt_schedule->insert_id;

        // Insert team leader into team table
        $sql_insert_leader = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                              VALUES (?, ?, 'team leader', 'pending')";
        $stmt_insert_leader = $conn->prepare($sql_insert_leader);
        $stmt_insert_leader->bind_param("is", $schedule_id, $team_leader_id);
        $stmt_insert_leader->execute();
        $stmt_insert_leader->close();

        // Insert team members into team table
        $sql_insert_members = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                               VALUES (?, ?, 'team member', 'pending')";

        foreach ($team_members_ids as $member_id) {
            $stmt_insert_members = $conn->prepare($sql_insert_members);
            $stmt_insert_members->bind_param("is", $schedule_id, $member_id);
            $stmt_insert_members->execute();
            $stmt_insert_members->close();
        }

        // Fetch emails and names of team leader and team members
        $team_leader_email = '';
        $team_leader_name = '';
        $team_members = [];

        $sql_user_details = "SELECT email, CONCAT(first_name, ' ', middle_initial, '. ', last_name) AS name FROM internal_users WHERE user_id = ?";
        
        // Fetch team leader email and name
        $stmt_user_details = $conn->prepare($sql_user_details);
        $stmt_user_details->bind_param("s", $team_leader_id);
        $stmt_user_details->execute();
        $stmt_user_details->bind_result($email, $name);
        $stmt_user_details->fetch();
        $team_leader_email = $email;
        $team_leader_name = $name;
        $stmt_user_details->close();

        // Fetch team member emails and names
        foreach ($team_members_ids as $member_id) {
            $stmt_user_details = $conn->prepare($sql_user_details);
            $stmt_user_details->bind_param("s", $member_id);
            $stmt_user_details->execute();
            $stmt_user_details->bind_result($email, $name);
            $stmt_user_details->fetch();
            $team_members[] = ['email' => $email, 'name' => $name];
            $stmt_user_details->close();
        }

        // Send email notification
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            // Replace the below username and password with your Gmail email and App Password
            $mail->Username = 'usepqad@gmail.com';
            $mail->Password = 'vmvf vnvq ileu tmev';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Bypass SSL certificate verification
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
            $mail->addAddress($college_email);
            $mail->addAddress($team_leader_email);
            foreach ($team_members as $team_member) {
                $mail->addAddress($team_member['email']);
            }

            // Prepare the team members list
            $team_members_list = '';
            foreach ($team_members as $team_member) {
                $team_members_list .= '<li>' . $team_member['name'] . '</li>';
            }

            // Format date and time
            $formatted_date = date("F j, Y", strtotime($date));
            $formatted_time = date("g:i A", strtotime($time));

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Schedule Notification';
            $mail->Body    = "Dear Team,<br><br>A new schedule has been added:<br><br>
                              College: $college_name<br>
                              Program: $program_name<br>
                              Level Applied: $level<br>
                              Date: $formatted_date<br>
                              Time: $formatted_time<br><br>
                              <strong>Team Leader:</strong> $team_leader_name<br>
                              <strong>Team Members:</strong><br><ul>$team_members_list</ul><br>
                              Best regards,<br>USeP - Quality Assurance Division";

            $mail->send();

            // Commit transaction
            $conn->commit();

            echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #973939;
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
        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }
        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h2>Operation Result</h2>
        <div class=\"message\">
            New schedule and team members added successfully
        </div>
        <button class=\"button-primary\" onclick=\"window.location.href='schedule.php'\">OK</button>
    </div>
</body>
</html>";

        } catch (Exception $e) {
            // Rollback transaction if email fails to send
            $conn->rollback();
            echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #973939;
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
        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }
        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h2>Operation Result</h2>
        <div class=\"message error\">
            Schedule could not be added because there's an internet problem. Please try again.
        </div>
        <button class=\"button-primary\" onclick=\"window.location.href='add_schedule.php'\">OK</button>
    </div>
</body>
</html>";
        }

    } catch (Exception $e) {
        // Rollback transaction if any other error occurs
        $conn->rollback();
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            font-size: 24px;
            color: #973939;
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
        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }
        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h2>Operation Result</h2>
        <div class=\"message error\">
            Schedule could not be added because there's an internet problem. Please try again.
        </div>
        <button class=\"button-primary\" onclick=\"window.location.href='add_schedule.php'\">OK</button>
    </div>
</body>
</html>";
    }

    $stmt_schedule->close();
    
    $conn->close();

} else {
    header("Location: add_schedule.php");
    exit();
}
?>

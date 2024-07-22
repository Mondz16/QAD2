<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $collegeId = mysqli_real_escape_string($conn, $_POST['college']);
    $programId = mysqli_real_escape_string($conn, $_POST['program']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $team_leader_id = mysqli_real_escape_string($conn, $_POST['team_leader']);
    $team_members_ids = $_POST['team_members'];

    // Fetch college name
    $sql_college = "SELECT college_name FROM college WHERE code = ?";
    $stmt_college = $conn->prepare($sql_college);
    $stmt_college->bind_param("i", $collegeId);
    $stmt_college->execute();
    $stmt_college->bind_result($college_name);
    $stmt_college->fetch();
    $stmt_college->close();

    // Fetch program name
    $sql_program = "SELECT program_name FROM program WHERE id = ?";
    $stmt_program = $conn->prepare($sql_program);
    $stmt_program->bind_param("i", $programId);
    $stmt_program->execute();
    $stmt_program->bind_result($program);
    $stmt_program->fetch();
    $stmt_program->close();

    // Check if the date already exists
    $sql_check_status = "SELECT id FROM schedule WHERE id = ? AND schedule_status != 'cancelled'";
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

    // Insert into schedule table
    $sql_schedule = "INSERT INTO schedule (college_code, program_id, level_applied, schedule_date, schedule_time, status_date)
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("iiisss", $collegeId, $programId, $level, $date, $time, $result);

    if ($stmt_schedule->execute()) {
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

    } else {
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
            " . $stmt_schedule->error . "
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

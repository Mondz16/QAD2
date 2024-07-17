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

    // Debugging statements to check received data
    echo "College ID: " . $collegeId . "<br>";
    echo "Program ID: " . $programId . "<br>";
    echo "Level: " . $level . "<br>";
    echo "Date: " . $date . "<br>";
    echo "Time: " . $time . "<br>";
    echo "Team Leader ID: " . $team_leader_id . "<br>";
    echo "Team Members IDs:<br>";
    foreach ($team_members_ids as $id) {
        echo $id . "<br>";
    }

    // Check if the date already exists
    $sql_check_date = "SELECT id FROM schedule WHERE schedule_date = ?";
    $stmt_check_date = $conn->prepare($sql_check_date);
    $stmt_check_date->bind_param("s", $date);
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    if ($stmt_check_date->num_rows > 0) {
        echo "Error: Schedule already exists for the selected date.";
        $stmt_check_date->close();
        $conn->close();
        exit();
    }

    $stmt_check_date->close();

    // Insert into schedule table
    $sql_schedule = "INSERT INTO schedule (college_id, program_id, level_applied, schedule_date, schedule_time)
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("iiiss", $collegeId, $programId, $level, $date, $time);

    if ($stmt_schedule->execute()) {
        $schedule_id = $stmt_schedule->insert_id;

        // Insert team leader into team table
        $sql_insert_leader = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                              VALUES (?, ?, 'team leader', 'pending')";
        $stmt_insert_leader = $conn->prepare($sql_insert_leader);
        $stmt_insert_leader->bind_param("is", $schedule_id, $team_leader_id);
        $stmt_insert_leader->execute();
        $stmt_insert_leader->close();

        // Insert notification for team leader
        $message = "New schedule created: College - $collegeId, Program - $programId, Level - $level, Date - $date, Time - $time. You are assigned as the team leader.";
        $sql_notification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $stmt_notification = $conn->prepare($sql_notification);
        $stmt_notification->bind_param("ss", $team_leader_id, $message);
        $stmt_notification->execute();
        $stmt_notification->close();

        // Insert team members into team table
        $sql_insert_members = "INSERT INTO team (schedule_id, internal_users_id, role, status)
                               VALUES (?, ?, 'team member', 'pending')";

        foreach ($team_members_ids as $member_id) {
            $stmt_insert_members = $conn->prepare($sql_insert_members);
            $stmt_insert_members->bind_param("is", $schedule_id, $member_id);
            $stmt_insert_members->execute();
            $stmt_insert_members->close();

            // Insert notification for team members
            $message = "New schedule created: College - $collegeId, Program - $programId, Level - $level, Date - $date, Time - $time. You are assigned as a team member.";
            $stmt_notification = $conn->prepare($sql_notification);
            $stmt_notification->bind_param("ss", $member_id, $message);
            $stmt_notification->execute();
            $stmt_notification->close();
        }

        echo "New schedule and team members added successfully";
        echo '<br><button onclick="location.href=\'schedule.php\'">OK</button>';

    } else {
        echo "Error: " . $stmt_schedule->error;
    }

    $stmt_schedule->close();
    
    $conn->close();

} else {
    header("Location: add_schedule.php");
    exit();
}
?>

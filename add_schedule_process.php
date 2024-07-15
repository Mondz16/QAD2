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

    // Get college name
    $sql_college = "SELECT college_name FROM college WHERE id = ?";
    $stmt_college = $conn->prepare($sql_college);
    $stmt_college->bind_param("i", $collegeId);
    $stmt_college->execute();
    $stmt_college->bind_result($college_name);
    $stmt_college->fetch();
    $stmt_college->close();

    // Get program name
    $sql_program = "SELECT program FROM program WHERE id = ?";
    $stmt_program = $conn->prepare($sql_program);
    $stmt_program->bind_param("i", $programId);
    $stmt_program->execute();
    $stmt_program->bind_result($program_name);
    $stmt_program->fetch();
    $stmt_program->close();

    // Get team leader details
    $sql_team_leader = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
    $stmt_team_leader = $conn->prepare($sql_team_leader);
    $stmt_team_leader->bind_param("s", $team_leader_id);
    $stmt_team_leader->execute();
    $stmt_team_leader->bind_result($team_leader_fname, $team_leader_mi, $team_leader_lname);
    $stmt_team_leader->fetch();
    $stmt_team_leader->close();

    // Insert into schedule table
    $sql_schedule = "INSERT INTO schedule (college, program, level_applied, schedule_date, schedule_time)
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("sssss", $college_name, $program_name, $level, $date, $time);

    if ($stmt_schedule->execute()) {
        $schedule_id = $stmt_schedule->insert_id;

        // Insert team leader into team table
        $sql_insert_leader = "INSERT INTO team (schedule_id, fname, mi, lname, role, status)
                              VALUES (?, ?, ?, ?, 'team leader', 'pending')";
        $stmt_insert_leader = $conn->prepare($sql_insert_leader);
        $stmt_insert_leader->bind_param("isss", $schedule_id, $team_leader_fname, $team_leader_mi, $team_leader_lname);
        $stmt_insert_leader->execute();
        $stmt_insert_leader->close();

        // Insert notification for team leader
        $message = "New schedule created: College - $college_name, Program - $program_name, Level - $level, Date - $date, Time - $time. You are assigned as the team leader.";
        $sql_notification = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
        $stmt_notification = $conn->prepare($sql_notification);
        $stmt_notification->bind_param("ss", $team_leader_id, $message);
        $stmt_notification->execute();
        $stmt_notification->close();

        // Insert team members into team table
        $sql_insert_members = "INSERT INTO team (schedule_id, fname, mi, lname, role, status)
                               VALUES (?, ?, ?, ?, 'team member', 'pending')";

        foreach ($team_members_ids as $member_id) {
            // Get team member details
            $sql_team_member = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
            $stmt_team_member = $conn->prepare($sql_team_member);
            $stmt_team_member->bind_param("s", $member_id);
            $stmt_team_member->execute();
            $stmt_team_member->bind_result($member_fname, $member_mi, $member_lname);
            $stmt_team_member->fetch();
            $stmt_team_member->close();

            // Insert team member
            $stmt_insert_members = $conn->prepare($sql_insert_members);
            $stmt_insert_members->bind_param("isss", $schedule_id, $member_fname, $member_mi, $member_lname);
            $stmt_insert_members->execute();
            $stmt_insert_members->close();

            // Insert notification for team members
            $message = "New schedule created: College - $college_name, Program - $program_name, Level - $level, Date - $date, Time - $time. You are assigned as a team member.";
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

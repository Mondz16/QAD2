<?php
include 'connection.php';

// Check if schedule_id is provided in the URL
if (isset($_GET['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_GET['schedule_id']);

    // Retrieve schedule details
    $sql_schedule = "SELECT s.id, s.college, s.program, s.level_applied, s.schedule_date, TIME_FORMAT(s.schedule_time, '%h:%i %p') AS schedule_time_format,
                           t.fname AS leader_fname, t.mi AS leader_mi, t.lname AS leader_lname
                    FROM schedule s
                    LEFT JOIN team t ON s.id = t.schedule_id
                    WHERE s.id = '$schedule_id'";

    $result_schedule = $conn->query($sql_schedule);

    if ($result_schedule->num_rows > 0) {
        $row_schedule = $result_schedule->fetch_assoc();

        // Retrieve team members
        $sql_team = "SELECT fname, mi, lname
                     FROM team
                     WHERE schedule_id = '$schedule_id' AND role = 'team member'";

        $result_team = $conn->query($sql_team);

        // Display schedule details
        echo "<h2>Cancel Schedule</h2>";
        echo "<p>College: " . htmlspecialchars($row_schedule['college']) . "</p>";
        echo "<p>Program: " . htmlspecialchars($row_schedule['program']) . "</p>";
        echo "<p>Level Applied: " . htmlspecialchars($row_schedule['level_applied']) . "</p>";
        echo "<p>Date: " . htmlspecialchars($row_schedule['schedule_date']) . "</p>";
        echo "<p>Time: " . htmlspecialchars($row_schedule['schedule_time_format']) . "</p>";
        echo "<p>Team Leader: " . htmlspecialchars($row_schedule['leader_fname'] . " " . $row_schedule['leader_mi'] . " " . $row_schedule['leader_lname']) . "</p>";
        
        if ($result_team->num_rows > 0) {
            echo "<p>Team Members:</p>";
            echo "<ul>";
            while ($row_team = $result_team->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row_team['fname'] . " " . $row_team['mi'] . " " . $row_team['lname']) . "</li>";
            }
            echo "</ul>";
        }

        // Confirmation message
        echo "<p>Are you sure you want to cancel this schedule?</p>";

        // Yes and No buttons
        echo "<form method='post' action='schedule_cancel_process.php'>";
        echo "<input type='hidden' name='schedule_id' value='" . htmlspecialchars($schedule_id) . "'>";
        echo "<input type='submit' name='yes' value='Yes'>";
        echo "<a href='schedule_college.php?college=" . urlencode($row_schedule['college']) . "'>No</a>";
        echo "</form>";
    } else {
        echo "Schedule not found.";
    }
} else {
    echo "Error: Schedule ID not specified.";
}

$conn->close();
?>

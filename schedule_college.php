<?php
include 'connection.php';

// Handle form submissions for reschedule and cancel actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reschedule'])) {
        $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
        header("Location: reschedule.php?schedule_id=$schedule_id");
        exit();
    } elseif (isset($_POST['cancel'])) {
        $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
        header("Location: schedule_cancel.php?schedule_id=$schedule_id");
        exit();
    }
}

// Retrieve schedules and their team information for the specified college
if (isset($_GET['college'])) {
    $college = mysqli_real_escape_string($conn, $_GET['college']);

    $sql = "SELECT s.id, s.program, s.schedule_date, TIME_FORMAT(s.schedule_time, '%h:%i %p') AS schedule_time_format,
                   t.fname, t.mi, t.lname, t.role, t.status
            FROM schedule s
            LEFT JOIN team t ON s.id = t.schedule_id
            WHERE s.college = '$college'
            ORDER BY s.id";

    $result = $conn->query($sql);

    echo "<h2>Schedules for $college</h2>";
    echo '<a href="schedule.php">Back</a>';

    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Program</th><th>Date</th><th>Time</th><th>Team Leader</th><th>Team Members</th><th>Actions</th></tr>";

        $current_schedule_id = null;
        $team_leader = null;
        $team_members = [];

        while ($row = $result->fetch_assoc()) {
            if ($row['id'] != $current_schedule_id) {
                // Output the schedule details if not first iteration
                if ($current_schedule_id !== null) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($current_row["program"]) . "</td>";
                    echo "<td>" . htmlspecialchars($current_row["schedule_date"]) . "</td>";
                    echo "<td>" . htmlspecialchars($current_row["schedule_time_format"]) . "</td>";
                    echo "<td>" . htmlspecialchars($team_leader['name']) . " - " . htmlspecialchars($team_leader['status']) . "</td>";
                    echo "<td>"; // Start of team members column
                    foreach ($team_members as $member) {
                        echo htmlspecialchars($member['name']) . " - " . htmlspecialchars($member['status']) . "<br>"; // Output each member and their status
                    }
                    echo "</td>";
                    echo "<td>"; // Actions column
                    echo "<form method='post' action=''>";
                    echo "<input type='hidden' name='schedule_id' value='" . $current_row['id'] . "'>";
                    echo "<input type='submit' name='reschedule' value='Reschedule'>";
                    echo "<input type='submit' name='cancel' value='Cancel'>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }

                // Reset arrays for new schedule
                $current_schedule_id = $row['id'];
                $team_leader = null;
                $team_members = [];
            }

            // Determine if current row is a team leader or member
            if ($row['role'] === 'team leader') {
                $team_leader = [
                    'name' => $row["fname"] . " " . $row["mi"] . " " . $row["lname"],
                    'status' => ucfirst($row["status"]) // Capitalize the status
                ];
            } elseif ($row['role'] === 'team member') {
                $team_members[] = [
                    'name' => $row["fname"] . " " . $row["mi"] . " " . $row["lname"],
                    'status' => ucfirst($row["status"]) // Capitalize the status
                ];
            }

            // Store the current row to output after the loop ends
            $current_row = $row;
        }

        // Output the last schedule's details after loop ends
        if ($current_schedule_id !== null) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($current_row["program"]) . "</td>";
            echo "<td>" . htmlspecialchars($current_row["schedule_date"]) . "</td>";
            echo "<td>" . htmlspecialchars($current_row["schedule_time_format"]) . "</td>";
            echo "<td>" . htmlspecialchars($team_leader['name']) . " - " . htmlspecialchars($team_leader['status']) . "</td>";
            echo "<td>"; // Start of team members column
            foreach ($team_members as $member) {
                echo htmlspecialchars($member['name']) . " - " . htmlspecialchars($member['status']) . "<br>"; // Output each member and their status
            }
            echo "</td>";
            echo "<td>"; // Actions column
            echo "<form method='post' action=''>";
            echo "<input type='hidden' name='schedule_id' value='" . $current_row['id'] . "'>";
            echo "<input type='submit' name='reschedule' value='Reschedule'>";
            echo "<input type='submit' name='cancel' value='Cancel'>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No schedules found for $college";
    }
} else {
    echo "Error: College parameter not specified";
}

$conn->close();
?>

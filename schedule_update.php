<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $schedule_date = mysqli_real_escape_string($conn, $_POST['schedule_date']);
    $schedule_time = mysqli_real_escape_string($conn, $_POST['schedule_time']);

    // Check if the new schedule date conflicts with existing schedules
    $sql_check_conflict = "SELECT * FROM schedule WHERE schedule_date = '$schedule_date' AND id != '$schedule_id'";
    $result_check_conflict = $conn->query($sql_check_conflict);

    if ($result_check_conflict->num_rows > 0) {
        echo "Error: There is already a schedule on $schedule_date. Please choose another date.<br>";
        echo "<a href='javascript:history.go(-1)'>Go Back</a>";
    } else {
        // Update schedule date and time in the database
        $sql_update_schedule = "UPDATE schedule SET schedule_date = '$schedule_date', schedule_time = '$schedule_time' WHERE id = '$schedule_id'";

        if ($conn->query($sql_update_schedule) === TRUE) {
            // Fetch college name associated with the schedule
            $sql_college = "SELECT college FROM schedule WHERE id = '$schedule_id'";
            $result_college = $conn->query($sql_college);
            if ($result_college->num_rows > 0) {
                $row = $result_college->fetch_assoc();
                $college = $row['college'];
                echo "Schedule ID: $schedule_id updated successfully<br>";
                echo "<a href='schedule_college.php?college=" . urlencode($college) . "'>Back to Schedules</a>";
            } else {
                echo "Error: College not found";
            }
        } else {
            echo "Error updating schedule: " . $conn->error;
        }
    }
} else {
    echo "Error: Invalid request";
}

$conn->close();
?>

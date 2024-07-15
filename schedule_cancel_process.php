<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);

    // Delete team members associated with the schedule
    $sql_delete_team = "DELETE FROM team WHERE schedule_id = '$schedule_id'";
    if ($conn->query($sql_delete_team) === TRUE) {
        // Delete the schedule itself
        $sql_delete_schedule = "DELETE FROM schedule WHERE id = '$schedule_id'";
        if ($conn->query($sql_delete_schedule) === TRUE) {
            // Redirect to schedule_college.php with the college parameter
            header("Location: schedule_college.php?college=" . urlencode($college));
            exit();
        } else {
            echo "Error deleting schedule: " . $conn->error;
        }
    } else {
        echo "Error deleting team members: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>

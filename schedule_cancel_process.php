<?php
include 'connection.php';

if (isset($_GET['schedule_id']) && isset($_GET['college'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_GET['schedule_id']);
    $college = mysqli_real_escape_string($conn, $_GET['college']);

    // Update the schedule_status to "cancelled"
    $sql_update_status = "UPDATE schedule SET schedule_status = 'cancelled' WHERE id = '$schedule_id'";
    if ($conn->query($sql_update_status) === TRUE) {
        // Redirect to schedule_college.php with the college parameter
        header('Location:schedule_college.php?college='. $college);
        echo "Updated successfully";
        exit();
    } else {
        echo "Error updating schedule status: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>

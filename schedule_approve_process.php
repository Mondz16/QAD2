<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scheduleId = intval($_POST['schedule_id']);

    // Set the timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time

    // Update the schedule status to "approved" and set the status_date
    $sql = "UPDATE schedule SET schedule_status = 'approved', status_date = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $currentDateTime, $scheduleId);

    if ($stmt->execute()) {
        echo "Schedule approved successfully.";
    } else {
        echo "Error approving schedule: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>

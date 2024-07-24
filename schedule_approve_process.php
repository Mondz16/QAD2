<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scheduleId = intval($_POST['schedule_id']);

    // Update the schedule status to "approved"
    $sql = "UPDATE schedule SET schedule_status = 'approved' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $scheduleId);

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

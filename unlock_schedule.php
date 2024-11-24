<?php
include 'connection.php';  // Adjust the path if needed

// Check if the 'id' parameter is passed
if (isset($_POST['id'])) {
    $scheduleId = intval($_POST['id']);  // Ensure it's an integer to prevent SQL injection

    // Prepare the SQL query to update the schedule status and manually unlock flag
    $sql = "UPDATE schedule 
            SET schedule_status = 'approved', manually_unlocked = 1, status_date = NOW() 
            WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind the schedule ID parameter to the query
        $stmt->bind_param("i", $scheduleId);
        
        // Execute the query
        if ($stmt->execute()) {
            // If successful, return a success message
            echo "Schedule unlocked successfully!";
        } else {
            // If the query failed, return an error message
            echo "Error unlocking the schedule: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // If the SQL query preparation failed, return an error message
        echo "Error preparing the SQL query: " . $conn->error;
    }

    // Close the database connection
    $conn->close();
} else {
    // If no ID is passed, return an error message
    echo "No schedule ID provided.";
}
?>

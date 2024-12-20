<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Unauthorized access';
    exit();
}

// Check if IDs are provided
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];

    // Prepare a SQL statement to delete the parameters
    $sql = "DELETE FROM parameters WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")";
    
    if ($conn->query($sql) === TRUE) {
        echo "Parameters deleted successfully";
    } else {
        echo "Error deleting parameters: " . $conn->error;
    }
} else {
    echo "No IDs provided";
}
?>

<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the area ID and the new parameter details
    $area_id = $_POST['area_id'];
    $parameter_name = $_POST['modal_param_name'];
    $parameter_description = $_POST['modal_param_description'];

    // Insert the new parameter into the parameters table
    $sql = "INSERT INTO parameters (area_id, parameter_name, parameter_description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $area_id, $parameter_name, $parameter_description);

    if ($stmt->execute()) {
        // Redirect back to the edit area page with a success message
        header("Location: edit_area.php?code=$area_id&success=Parameter added successfully");
    } else {
        // Handle the error
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<?php
// Include database connection
include 'connection.php';

// Get the raw POST data and decode it as JSON
$data = json_decode(file_get_contents('php://input'), true);

// Check if the required data exists
if (isset($data['id']) && isset($data['standard']) && isset($data['level'])) {
    // Sanitize and assign the data
    $id = intval($data['id']);
    $standard = floatval($data['standard']); // Assuming the standard value is decimal (3, 2)
    $level = htmlspecialchars(trim($data['level'])); // Sanitize level input as a string

    // Prepare the SQL statement to update both standard and level
    $sql = "UPDATE accreditation_standard SET Standard = ?, Level = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind the parameters
        $stmt->bind_param("dsi", $standard, $level, $id); // "dsi" means double, string, and integer

        // Execute the statement
        if ($stmt->execute()) {
            // Respond with success
            echo json_encode(["success" => true, "message" => "Standard and Level updated successfully."]);
        } else {
            // Respond with error if execution fails
            echo json_encode(["success" => false, "message" => "Failed to update the standard and level."]);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Respond with error if statement preparation fails
        echo json_encode(["success" => false, "message" => "Failed to prepare the SQL statement."]);
    }
} else {
    // Respond with error if data is missing
    echo json_encode(["success" => false, "message" => "Invalid input data."]);
}

// Close the database connection
$conn->close();
?>
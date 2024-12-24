<?php
// Include database connection
include 'connection.php';

// Get the raw POST data and decode it as JSON
$data = json_decode(file_get_contents('php://input'), true);

// Check if the required data exists
if (isset($data['id']) && isset($data['standard'])) {
    // Sanitize and assign the data
    $id = intval($data['id']);
    $standard = floatval($data['standard']); // Assuming the standard value is decimal (3, 2)

    // Prepare the SQL statement to update the standard
    $sql = "UPDATE accreditation_standard SET Standard = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind the parameters
        $stmt->bind_param("di", $standard, $id); // "di" means double and integer

        // Execute the statement
        if ($stmt->execute()) {
            // Respond with success
            echo json_encode(["success" => true, "message" => "Standard updated successfully."]);
        } else {
            // Respond with error if execution fails
            echo json_encode(["success" => false, "message" => "Failed to update the standard."]);
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

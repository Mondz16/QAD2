<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'connection.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Extract level and standard values
$level = isset($data['level']) ? $data['level'] : '';
$standard = isset($data['standard']) ? $data['standard'] : '';

// Validation: Ensure both level and standard are provided and valid
if (empty($level) || empty($standard) || !is_numeric($standard) || $standard < 1.00 || $standard > 5.00) {
    echo json_encode(['success' => false, 'message' => 'Invalid input values']);
    exit;
}

// Prepare the SQL statement to insert a new record
$sql = "INSERT INTO accreditation_standard (Level, Standard) VALUES (?, ?)";

try {
    // Prepare the statement using mysqli
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters to the statement
        $stmt->bind_param("sd", $level, $standard); // 's' for string, 'd' for double (decimal)

        // Execute the statement
        if ($stmt->execute()) {
            // Return success response with the ID of the new row
            $newId = $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert the record']);
        }
        // Close the statement
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the statement']);
    }
} catch (Exception $e) {
    // Handle errors and return failure response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
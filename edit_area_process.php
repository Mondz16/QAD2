<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Get the form data
$area_id = $_POST['area_id'];
$area_name = $_POST['area_name'];
$parameter_ids = $_POST['parameter_ids'] ?? [];
$parameter_names = $_POST['parameter_names'] ?? [];
$parameter_descriptions = $_POST['parameter_descriptions'] ?? [];
$new_parameter_names = $_POST['new_parameter_names'] ?? [];
$new_parameter_descriptions = $_POST['new_parameter_descriptions'] ?? [];
$removed_parameter_ids = $_POST['removed_parameter_ids'] ?? '';

// Start transaction
$conn->begin_transaction();

try {
    // Update the area name
    $sql = "UPDATE area SET area_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $area_name, $area_id);
    $stmt->execute();

    // Update existing parameters
    foreach ($parameter_ids as $index => $param_id) {
        $param_name = $parameter_names[$index];
        $param_description = $parameter_descriptions[$index];

        $sql = "UPDATE parameters SET parameter_name = ?, parameter_description = ? WHERE id = ? AND area_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $param_name, $param_description, $param_id, $area_id);
        $stmt->execute();
    }

    // Add new parameters
    foreach ($new_parameter_names as $index => $param_name) {
        $param_description = $new_parameter_descriptions[$index];

        $sql = "INSERT INTO parameters (area_id, parameter_name, parameter_description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $area_id, $param_name, $param_description);
        $stmt->execute();
    }

    // Remove parameters if there are any marked for removal
    if (!empty($removed_parameter_ids)) {
        $removed_ids = explode(',', $removed_parameter_ids);
    
        // Add the area_id to the list of parameters
        $removed_ids[] = $area_id; // Add area_id to the end of the array
    
        // Use placeholders to prepare a single DELETE statement
        $placeholders = implode(',', array_fill(0, count($removed_ids) - 1, '?')); // All removed IDs placeholders
        $sql = "DELETE FROM parameters WHERE id IN ($placeholders) AND area_id = ?";
    
        // Prepare the statement and dynamically bind the parameter ids and area id
        $stmt = $conn->prepare($sql);
        $types = str_repeat('i', count($removed_ids)); // 'i' for each parameter ID and the area ID
        $stmt->bind_param($types, ...$removed_ids); // Unpack the IDs and area_id together
        $stmt->execute();
    }
    

    // Commit the transaction
    $conn->commit();

    // Redirect back to the area page after successful update
    header("Location: area.php?success=1");
    exit();
} catch (Exception $e) {
    // Rollback if there was an error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
    exit();
}
?>

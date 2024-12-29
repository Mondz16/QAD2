<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'connection.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Extract level and standard values
$level = isset($data['level']) ? trim($data['level']) : '';
$standard = isset($data['standard']) ? $data['standard'] : '';

// Validation: Ensure both level and standard are provided and valid
if (empty($level) || empty($standard) || !is_numeric($standard) || $standard < 1.00 || $standard > 5.00) {
    echo json_encode(['success' => false, 'message' => 'Invalid input values']);
    exit;
}

// Check for existing level first
$checkSql = "SELECT COUNT(*) as count FROM accreditation_standard WHERE Level = ?";
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt) {
    $checkStmt->bind_param("s", $level);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Level '$level' already exists in the database. Please use a different level."
        ]);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    
    $checkStmt->close();
    
    // If we get here, the level is unique - proceed with insert
    $sql = "INSERT INTO accreditation_standard (Level, Standard) VALUES (?, ?)";
    
    try {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sd", $level, $standard);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo json_encode([
                    'success' => true,
                    'id' => $newId,
                    'message' => 'Standard added successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to insert the record'
                ]);
            }
            $stmt->close();
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare the statement'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to prepare validation statement'
    ]);
}

$conn->close();
?>
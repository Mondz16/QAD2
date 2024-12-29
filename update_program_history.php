<?php
include 'connection.php';

// Helper function to update program_level_id based on most recent date_received
function updateProgramLevelId($conn, $program_id)
{
    // Get the most recent program level history entry
    $sql = "SELECT id FROM program_level_history 
            WHERE program_id = ? 
            ORDER BY date_received DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Update the program table with the most recent program_level_id
        $update_sql = "UPDATE program 
                      SET program_level_id = ? 
                      WHERE id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $row['id'], $program_id);
        return $update_stmt->execute();
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'add') {
        if (!isset($_POST['program_id'], $_POST['program_level'], $_POST['date_received'], $_POST['year_of_validity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $program_id = intval($_POST['program_id']);
        $program_level = $_POST['program_level'];
        $date_received = $_POST['date_received'];
        $year_of_validity = $_POST['year_of_validity'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert new program level history
            $sql = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) 
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $program_id, $program_level, $date_received, $year_of_validity);
            $insert_success = $stmt->execute();

            if ($insert_success) {
                // Update program_level_id
                $update_success = updateProgramLevelId($conn, $program_id);

                if ($update_success) {
                    $conn->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Program level added and program updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update program level ID');
                }
            } else {
                throw new Exception('Failed to insert program level history');
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if ($action === 'update') {
        $history_id = intval($_POST['history_id']);
        $program_level = $_POST['program_level'];
        $date_received = $_POST['date_received'];
        $year_of_validity = $_POST['year_of_validity'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Get program_id before update
            $get_program_id = "SELECT program_id FROM program_level_history WHERE id = ?";
            $stmt = $conn->prepare($get_program_id);
            $stmt->bind_param("i", $history_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $program_id = $result->fetch_assoc()['program_id'];

            // Update program level history
            $sql = "UPDATE program_level_history 
                    SET program_level = ?, date_received = ?, year_of_validity = ? 
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $program_level, $date_received, $year_of_validity, $history_id);
            $update_success = $stmt->execute();

            if ($update_success) {
                // Update program_level_id
                $update_success = updateProgramLevelId($conn, $program_id);

                if ($update_success) {
                    $conn->commit();
                    echo json_encode([
                        'success' => true,
                        'message' => 'History and program updated successfully'
                    ]);
                } else {
                    throw new Exception('Failed to update program level ID');
                }
            } else {
                throw new Exception('Failed to update program level history');
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

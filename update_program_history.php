<?php
include 'connection.php';

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

        $sql = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $program_id, $program_level, $date_received, $year_of_validity);
        
        $success = $stmt->execute();
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Program level added successfully' : 'Error adding program level'
        ]);
        exit;
    }

    $history_id = $_POST['history_id'];
    
    if ($action === 'delete') {
        $sql = "DELETE FROM program_level_history WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $history_id);
        
        $response = array(
            'success' => $stmt->execute(),
            'message' => $stmt->execute() ? 'History deleted successfully' : 'Error deleting history'
        );
        
        echo json_encode($response);
        exit;
    }
    
    if ($action === 'update') {
        $program_level = $_POST['program_level'];
        $date_received = $_POST['date_received'];
        $year_of_validity = $_POST['year_of_validity'];
        
        $sql = "UPDATE program_level_history 
                SET program_level = ?, date_received = ?, year_of_validity = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $program_level, $date_received, $year_of_validity, $history_id);
        
        $response = array(
            'success' => $stmt->execute(),
            'message' => $stmt->execute() ? 'History updated successfully' : 'Error updating history'
        );
        
        echo json_encode($response);
        exit;
    }
}
?>
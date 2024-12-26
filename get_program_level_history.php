<?php
include 'connection.php';

if (isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];
    
    // Get program details and its level history
    $sql = "SELECT p.program_name, plh.id, plh.program_level, plh.date_received, plh.year_of_validity 
            FROM program p
            LEFT JOIN program_level_history plh ON p.id = plh.program_id
            WHERE p.id = ?
            ORDER BY plh.date_received DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = array();
    $response['history'] = array();
    
    while ($row = $result->fetch_assoc()) {
        if (empty($response['program_name'])) {
            $response['program_name'] = $row['program_name'];
        }
        if ($row['program_level']) {  // Only add if there's actual history
            $response['history'][] = array(
                'id' => $row['id'],
                'level' => $row['program_level'],
                'date_received' => $row['date_received'],
                'year_of_validity' => $row['year_of_validity']
            );
        }
    }
    
    echo json_encode($response);
    exit;
}
?>
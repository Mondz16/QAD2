<?php
include 'connection.php';

if (isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];

    $sql = "SELECT plh.program_level, plh.date_received 
            FROM program p 
            LEFT JOIN program_level_history plh 
            ON p.program_level_id = plh.id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response = [
            'program_level' => $row['program_level'] ?? 'N/A',
            'date_received' => $row['date_received'] ?? 'N/A'
        ];
        echo json_encode($response);
    } else {
        $response = [
            'program_level' => 'N/A',
            'date_received' => 'N/A'
        ];
        echo json_encode($response);
    }
}
?>

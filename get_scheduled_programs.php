<?php
// get_scheduled_programs.php

include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['college_id'])) {
    $collegeId = $_POST['college_id'];
    
    // Prepare SQL statement to fetch scheduled programs for the college
    $sql = "SELECT DISTINCT p.id, p.program
            FROM program p
            JOIN schedule s ON p.id = s.program_id
            WHERE s.college_id = $collegeId";
    
    $result = $conn->query($sql);
    
    $scheduledPrograms = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $program = [
                'id' => $row['id'],
                'program' => $row['program']
            ];
            $scheduledPrograms[] = $program;
        }
    }
    
    echo json_encode($scheduledPrograms);
} else {
    echo json_encode([]);
}

$conn->close();
?>

<?php
include 'connection.php';

if (isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];

    $sql = "SELECT plh.program_level 
            FROM program p 
            LEFT JOIN program_level_history plh 
            ON p.program_level_id = plh.id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo $row['program_level'] ?? 'N/A'; // Return 'N/A' if program_level is NULL
    } else {
        echo '0'; // Return a default level if no result found
    }
}
?>

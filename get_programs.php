<?php
include 'connection.php';

if (isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    // Query to fetch programs that are not scheduled
    $sql = "SELECT p.id, p.program 
            FROM program p
            WHERE p.college_id = $college_id 
            AND NOT EXISTS (
                SELECT 1 FROM schedule s 
                WHERE s.program = p.program
            )
            ORDER BY p.program";

    $result = $conn->query($sql);

    echo '<option value="">Select Program</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['program']}</option>";
    }
}
?>

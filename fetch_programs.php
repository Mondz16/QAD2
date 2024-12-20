<?php
include 'connection.php';

$sql = "SELECT id, program_name FROM program ORDER BY program_name";
$result = $conn->query($sql);

$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = ['id' => $row['id'], 'name' => $row['program_name']];
}

echo json_encode($programs);
?>

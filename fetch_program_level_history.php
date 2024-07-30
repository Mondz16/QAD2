<?php
// fetch_program_level_history.php

include 'databasetable.php'; // Include your database connection

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$campus = $input['campus'];
$college = $input['college'];
$program = $input['program'];

$query = "SELECT date_received, program_level FROM program_level_history WHERE campus = ? AND college = ? AND program = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $campus, $college, $program);
$stmt->execute();
$result = $stmt->get_result();

$dates = [];
$levels = [];

while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date_received'];
    $levels[] = $row['program_level'];
}

$response = [
    'labels' => $dates,
    'values' => $levels
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>

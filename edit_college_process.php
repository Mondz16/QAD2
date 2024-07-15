<?php
include 'connection.php';

$college_id = $_POST['college_id'];
$college_name = $_POST['college_name'];
$program_ids = $_POST['program_ids'];
$programs = $_POST['programs'];
$levels = $_POST['levels'];
$dates_received = $_POST['dates_received'];
$new_programs = $_POST['new_programs'];
$new_levels = $_POST['new_levels'];
$new_dates_received = $_POST['new_dates_received'];

$sql = "UPDATE college SET college_name = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $college_name, $college_id);
$stmt->execute();

foreach ($program_ids as $index => $program_id) {
    $program = $programs[$index];
    $level = $levels[$index];
    $date_received = $dates_received[$index];
    
    $sql = "UPDATE program SET program = ?, level = ?, date_received = ? WHERE id = ? AND college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $program, $level, $date_received, $program_id, $college_id);
    $stmt->execute();
}

foreach ($new_programs as $index => $new_program) {
    $new_level = $new_levels[$index];
    $new_date_received = $new_dates_received[$index];

    $sql = "INSERT INTO program (program, level, date_received, college_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $new_program, $new_level, $new_date_received, $college_id);
    $stmt->execute();
}

$stmt->close();
$conn->close();

header("Location: college.php");
exit;
?>
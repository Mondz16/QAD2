<?php
include 'connection.php';

$college_code = $_POST['college_code'];
$college_name = $_POST['college_name'];
$college_email = $_POST['college_email'];
$program_ids = isset($_POST['program_ids']) ? $_POST['program_ids'] : [];
$programs = isset($_POST['programs']) ? $_POST['programs'] : [];
$levels = isset($_POST['levels']) ? $_POST['levels'] : [];
$dates_received = isset($_POST['dates_received']) ? $_POST['dates_received'] : [];
$new_programs = isset($_POST['new_programs']) ? $_POST['new_programs'] : [];
$new_levels = isset($_POST['new_levels']) ? $_POST['new_levels'] : [];
$new_dates_received = isset($_POST['new_dates_received']) ? $_POST['new_dates_received'] : [];
$removed_program_ids = isset($_POST['removed_program_ids']) ? explode(',', $_POST['removed_program_ids']) : [];

// Update college details
$sql = "UPDATE college SET college_name = ?, college_email = ? WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $college_name, $college_email, $college_code);
$stmt->execute();

// Update existing programs
$sql_update_program = "UPDATE program SET program_name = ?, program_level = ?, date_received = ? WHERE id = ?";
$stmt_update_program = $conn->prepare($sql_update_program);
for ($i = 0; $i < count($program_ids); $i++) {
    $stmt_update_program->bind_param("sssi", $programs[$i], $levels[$i], $dates_received[$i], $program_ids[$i]);
    $stmt_update_program->execute();
}

// Insert new programs
if (!empty($new_programs)) {
    $sql_insert_program = "INSERT INTO program (college_code, program_name, program_level, date_received) VALUES (?, ?, ?, ?)";
    $stmt_insert_program = $conn->prepare($sql_insert_program);
    for ($j = 0; $j < count($new_programs); $j++) {
        $stmt_insert_program->bind_param("isss", $college_code, $new_programs[$j], $new_levels[$j], $new_dates_received[$j]);
        $stmt_insert_program->execute();
    }
}

// Delete removed programs
if (!empty($removed_program_ids)) {
    $sql_delete_program = "DELETE FROM program WHERE id = ?";
    $stmt_delete_program = $conn->prepare($sql_delete_program);
    foreach ($removed_program_ids as $removed_id) {
        $stmt_delete_program->bind_param("i", $removed_id);
        $stmt_delete_program->execute();
    }
}

// Redirect back to the college.php page after processing
header("Location: college.php");
exit();
?>

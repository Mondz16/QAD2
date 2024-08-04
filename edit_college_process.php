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
$stmt->bind_param("sss", $college_name, $college_email, $college_code); // Corrected to "sss"
if (!$stmt->execute()) {
    die('Error updating college details: ' . htmlspecialchars($stmt->error));
}

// Update existing programs and their levels
$sql_update_program = "UPDATE program SET program_name = ? WHERE id = ?";
$sql_check_program_level_id = "SELECT program_level_id FROM program WHERE id = ?";
$sql_update_program_level = "UPDATE program_level_history SET program_level = ?, date_received = ? WHERE id = ?";
$sql_insert_program_level = "INSERT INTO program_level_history (program_id, program_level, date_received) VALUES (?, ?, ?)";
$sql_update_program_with_new_level = "UPDATE program SET program_level_id = ? WHERE id = ?";

$stmt_update_program = $conn->prepare($sql_update_program);
$stmt_check_program_level_id = $conn->prepare($sql_check_program_level_id);
$stmt_update_program_level = $conn->prepare($sql_update_program_level);
$stmt_insert_program_level = $conn->prepare($sql_insert_program_level);
$stmt_update_program_with_new_level = $conn->prepare($sql_update_program_with_new_level);

for ($i = 0; $i < count($program_ids); $i++) {
    $stmt_update_program->bind_param("si", $programs[$i], $program_ids[$i]);
    $stmt_update_program->execute();

    // Check if program_level_id exists
    $stmt_check_program_level_id->bind_param("i", $program_ids[$i]);
    $stmt_check_program_level_id->execute();
    $stmt_check_program_level_id->store_result();
    $stmt_check_program_level_id->bind_result($program_level_id);
    $stmt_check_program_level_id->fetch();

    // Ensure program_level is not NULL
    if (is_null($levels[$i])) {
        die('Error: program_level cannot be NULL');
    }

    if ($stmt_check_program_level_id->num_rows > 0 && $program_level_id !== NULL) {
        // Update existing program_level_history
        $stmt_update_program_level->bind_param("ssi", $levels[$i], $dates_received[$i], $program_level_id);
        $stmt_update_program_level->execute();
    } else {
        // Insert new program_level_history
        $stmt_insert_program_level->bind_param("iss", $program_ids[$i], $levels[$i], $dates_received[$i]);
        $stmt_insert_program_level->execute();
        $new_program_level_id = $conn->insert_id;

        // Update program with new program_level_id
        $stmt_update_program_with_new_level->bind_param("ii", $new_program_level_id, $program_ids[$i]);
        $stmt_update_program_with_new_level->execute();
    }

    // Free the result to avoid the "Commands out of sync" error
    $stmt_check_program_level_id->free_result();
}

// Insert new programs and their levels
if (!empty($new_programs)) {
    $sql_insert_program = "INSERT INTO program (college_code, program_name, program_level_id) VALUES (?, ?, ?)";
    $stmt_insert_program = $conn->prepare($sql_insert_program);

    for ($j = 0; $j < count($new_programs); $j++) {
        // Ensure new_levels is not NULL
        if (is_null($new_levels[$j])) {
            die('Error: new program_level cannot be NULL');
        }

        // Insert into program_level_history first to get the new ID
        $stmt_insert_program_level->bind_param("iss", $new_programs[$j], $new_levels[$j], $new_dates_received[$j]);
        $stmt_insert_program_level->execute();
        $new_program_level_id = $conn->insert_id;

        // Insert into program with the new program_level_id
        $stmt_insert_program->bind_param("isi", $college_code, $new_programs[$j], $new_program_level_id);
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

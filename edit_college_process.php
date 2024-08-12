<?php
include 'connection.php';

$college_code = $_POST['college_code'];
$college_name = $_POST['college_name'];
$college_email = $_POST['college_email'];
$program_ids = isset($_POST['program_ids']) ? $_POST['program_ids'] : [];
$programs = isset($_POST['programs']) ? $_POST['programs'] : [];
$levels = isset($_POST['levels']) ? $_POST['levels'] : [];
$dates_received = isset($_POST['dates_received']) ? $_POST['dates_received'] : [];
$new_programs_ids = isset($_POST['new_program_ids']) ? $_POST['new_program_ids'] : [];
$new_programs = isset($_POST['new_programs']) ? $_POST['new_programs'] : [];
$new_levels = isset($_POST['new_levels']) ? $_POST['new_levels'] : [];
$new_dates_received = isset($_POST['new_dates_received']) ? $_POST['new_dates_received'] : [];
$removed_program_ids = isset($_POST['removed_program_ids']) ? explode(',', $_POST['removed_program_ids']) : [];

$message = "";
$status = "";
echo "Program ID's:" . '<pre>'; print_r($program_ids); echo '</pre>' . " | End Program ID <br>";
echo "Programs: " . '<pre>'; print_r($programs); echo '</pre>' . " | End Programs <br>";
echo "Levels: " .'<pre>'; print_r($levels); echo '</pre>' . " | End Levels <br>";

// Update college details
$sql = "UPDATE college SET college_name = ?, college_email = ? WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $college_name, $college_email, $college_code); // Corrected to "sss"
if (!$stmt->execute()) {
    die('Error updating college details: ' . htmlspecialchars($stmt->error));
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update college details
    $sql = "UPDATE college SET college_name = ?, college_email = ? WHERE code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $college_name, $college_email, $college_code);
    $stmt->execute();

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

        // Update program with new program_level_id
        $stmt_update_program_with_new_level->bind_param("ii", $new_program_level_id, $program_ids[$i]);
        $stmt_update_program_with_new_level->execute();
    }

    // Free the result to avoid the "Commands out of sync" error
    $stmt_check_program_level_id->free_result();
}

// Insert new programs and their levels
if (!empty($new_programs)) {
    $sql_insert_program = "INSERT INTO program (college_code, program_name) VALUES (?, ?)";
    $stmt_insert_program = $conn->prepare($sql_insert_program);

    for ($j = 0; $j < count($new_programs); $j++) {
        // Ensure new_levels is not NULL
        if (is_null($new_levels[$j])) {
            die('Error: new program_level cannot be NULL');
        }

        // Insert the new program into the program table
        $stmt_insert_program->bind_param("ss", $college_code, $new_programs[$j]);
        $stmt_insert_program->execute();
        $new_program_id = $conn->insert_id;

        // Insert into program_level_history with the newly created program_id
        $stmt_insert_program_level->bind_param("iss", $new_program_id, $new_levels[$j], $new_dates_received[$j]);
        $stmt_insert_program_level->execute();

        // Update the program with the new program_level_id
        $new_program_level_id = $conn->insert_id;
        $sql_update_program_with_new_level = "UPDATE program SET program_level_id = ? WHERE id = ?";
        $stmt_update_program_with_new_level = $conn->prepare($sql_update_program_with_new_level);
        $stmt_update_program_with_new_level->bind_param("ii", $new_program_level_id, $new_program_id);
        $stmt_update_program_with_new_level->execute();
    }

    // Insert new programs and their levels
    if (!empty($new_programs)) {
        $sql_insert_program = "INSERT INTO program (college_code, program_name, program_level_id) VALUES (?, ?, ?)";
        $stmt_insert_program = $conn->prepare($sql_insert_program);

        for ($j = 0; $j < count($new_programs); $j++) {
            // Ensure new_levels is not NULL
            if (is_null($new_levels[$j])) {
                throw new Exception('Error: new program_level cannot be NULL');
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

    // Commit transaction
    $conn->commit();
    $message = "College and program details updated successfully.";
    $status = "success";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $message = $e->getMessage();
    $status = "error";
}

// Delete removed programs and their associated levels
if (!empty($removed_program_ids)) {
    // Prepare the SQL statement to delete from program_level_history
    $sql_delete_program_level = "DELETE FROM program_level_history WHERE program_id = ?";
    $stmt_delete_program_level = $conn->prepare($sql_delete_program_level);

    // Prepare the SQL statement to delete from program
    $sql_delete_program = "DELETE FROM program WHERE id = ?";
    $stmt_delete_program = $conn->prepare($sql_delete_program);

    foreach ($removed_program_ids as $removed_id) {
        // Delete from program_level_history first
        $stmt_delete_program_level->bind_param("i", $removed_id);
        $stmt_delete_program_level->execute();

        // Then delete from program
        $stmt_delete_program->bind_param("i", $removed_id);
        $stmt_delete_program->execute();
    }
}

// Redirect back to the college.php page after processing
header("Location: college.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }
        .success {
            color: green;
        }

        .error {
            color: red;
        }
        .btn-hover {
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>
<body>
    <div class="popup-content">
        <div style='height: 50px; width: 0px;'></div>
        <img src="images/<?php echo ucfirst($status); ?>.png" height="100" alt="<?php echo ucfirst($status); ?>">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="college.php" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>

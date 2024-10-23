<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $college_name = mysqli_real_escape_string($conn, $_POST['college']); // College name
    $program_name = mysqli_real_escape_string($conn, $_POST['program']); // Program name
    $exclude_schedule_id = isset($_POST['exclude_schedule_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_schedule_id']) : null;

    // Step 1: Get the college_code using the college_name
    $college_query = "SELECT code FROM college WHERE college_name = ?";
    $stmt_college = $conn->prepare($college_query);
    $stmt_college->bind_param("s", $college_name);
    $stmt_college->execute();
    $stmt_college->bind_result($college_code);
    $stmt_college->fetch();
    $stmt_college->close();

    // Step 2: Get the program_id using the program_name and college_code
    $program_query = "SELECT id FROM program WHERE program_name = ? AND college_code = ?";
    $stmt_program = $conn->prepare($program_query);
    $stmt_program->bind_param("ss", $program_name, $college_code);
    $stmt_program->execute();
    $stmt_program->bind_result($program_id);
    $stmt_program->fetch();
    $stmt_program->close();

    // Step 3: Check for conflicting schedules using the retrieved college_code and program_id
    $sql_check_status = "SELECT id, college_code, schedule_status 
                         FROM schedule 
                         WHERE schedule_date = ? 
                         AND schedule_time = ? 
                         AND schedule_status IN ('approved', 'pending')";
    if ($exclude_schedule_id) {
        $sql_check_status .= " AND id != ?";
    }

    $stmt_check_date = $conn->prepare($sql_check_status);
    if ($exclude_schedule_id) {
        $stmt_check_date->bind_param("sss", $date, $time, $exclude_schedule_id); // Bind date, time, and exclude id
    } else {
        $stmt_check_date->bind_param("ss", $date, $time); // Bind date and time
    }
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    $response = ['status' => 'available']; // Default response for no conflict

    if ($stmt_check_date->num_rows > 0) {
        // Conflicting schedule found, check the college
        $stmt_check_date->bind_result($id, $conflict_college_code, $schedule_status);
        $stmt_check_date->fetch();

        // Allow if it's the same college, otherwise block
        if ($conflict_college_code !== $college_code) {
            // Different college with the same date and time -> block submission
            $response = ['status' => 'exists', 'schedule_status' => $schedule_status];
        } else {
            // Same college with the same date and time -> allow submission
            $response = ['status' => 'available'];
        }
    }

    $stmt_check_date->close();
    $conn->close();
    echo json_encode($response);
}

<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    $college_name = mysqli_real_escape_string($conn, $_POST['college']); // College name
    $exclude_schedule_id = isset($_POST['exclude_schedule_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_schedule_id']) : null;

    // Step 1: Get the college_code using the college_name
    $college_query = "SELECT code FROM college WHERE college_name = ?";
    $stmt_college = $conn->prepare($college_query);
    $stmt_college->bind_param("s", $college_name);
    $stmt_college->execute();
    $stmt_college->bind_result($college_code);
    $stmt_college->fetch();
    $stmt_college->close();

    // Step 2: Check for conflicting schedules using the retrieved college_code, new date, and new time
    $sql_check_conflict = "SELECT id, college_code, schedule_time, schedule_status 
                           FROM schedule 
                           WHERE schedule_date = ? 
                           AND schedule_time = ? 
                           AND (schedule_status = 'approved' OR schedule_status = 'pending')";

    if ($exclude_schedule_id) {
        $sql_check_conflict .= " AND id != ?";
    }

    $stmt_check_conflict = $conn->prepare($sql_check_conflict);
    if ($exclude_schedule_id) {
        $stmt_check_conflict->bind_param("sss", $new_date, $new_time, $exclude_schedule_id); // Bind date, time, and exclude schedule_id
    } else {
        $stmt_check_conflict->bind_param("ss", $new_date, $new_time); // Bind date and time
    }
    $stmt_check_conflict->execute();
    $stmt_check_conflict->store_result();

    $response = ['status' => 'available']; // Default response for no conflict

    if ($stmt_check_conflict->num_rows > 0) {
        // Conflicting schedule found, check if the time and college match
        $stmt_check_conflict->bind_result($id, $conflict_college_code, $conflict_time, $schedule_status);
        while ($stmt_check_conflict->fetch()) {
            if ($conflict_college_code !== $college_code && $conflict_time === $new_time) {
                // Conflict: Different college on the same date and time -> block submission
                $response = ['status' => 'exists', 'schedule_status' => $schedule_status];
                break;
            } else if ($conflict_college_code === $college_code && $conflict_time === $new_time) {
                // Same college, same time is allowed
                $response = ['status' => 'available'];
                break;
            }
        }
    }

    $stmt_check_conflict->close();
    $conn->close();
    echo json_encode($response);
}

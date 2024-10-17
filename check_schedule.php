<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']); // Get the time value
    $exclude_schedule_id = isset($_POST['exclude_schedule_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_schedule_id']) : null;

    // Prepare SQL query to check for conflicting dates and times
    $sql_check_status = "SELECT id, schedule_status FROM schedule WHERE schedule_date = ? AND schedule_time = ? AND schedule_status NOT IN ('cancelled', 'finished', 'failed', 'passed')";
    if ($exclude_schedule_id) {
        $sql_check_status .= " AND id != ?";
    }

    $stmt_check_date = $conn->prepare($sql_check_status);
    if ($exclude_schedule_id) {
        $stmt_check_date->bind_param("sss", $date, $time, $exclude_schedule_id); // Bind both date and time
    } else {
        $stmt_check_date->bind_param("ss", $date, $time); // Bind date and time
    }
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    $response = ['status' => 'available'];

    if ($stmt_check_date->num_rows > 0) {
        // Conflicting date and time found, fetch the status
        $stmt_check_date->bind_result($id, $schedule_status);
        $stmt_check_date->fetch();
        $response = ['status' => 'exists', 'schedule_status' => $schedule_status];
    }

    $stmt_check_date->close();
    $conn->close();
    echo json_encode($response);
}

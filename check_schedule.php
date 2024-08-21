<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $exclude_schedule_id = isset($_POST['exclude_schedule_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_schedule_id']) : null;

    // Prepare SQL query to check for conflicting dates
    $sql_check_status = "SELECT id, schedule_status FROM schedule WHERE schedule_date = ? AND schedule_status NOT IN ('cancelled', 'finished', 'failed', 'passed')";
    if ($exclude_schedule_id) {
        $sql_check_status .= " AND id != ?";
    }
    
    $stmt_check_date = $conn->prepare($sql_check_status);
    if ($exclude_schedule_id) {
        $stmt_check_date->bind_param("ss", $date, $exclude_schedule_id);
    } else {
        $stmt_check_date->bind_param("s", $date);
    }
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    $response = ['status' => 'available'];

    if ($stmt_check_date->num_rows > 0) {
        // Conflicting date found, fetch the status
        $stmt_check_date->bind_result($id, $schedule_status);
        $stmt_check_date->fetch();
        $response = ['status' => 'exists', 'schedule_status' => $schedule_status];
    }

    $stmt_check_date->close();
    $conn->close();
    echo json_encode($response);
}

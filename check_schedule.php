<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $exclude_schedule_id = isset($_POST['exclude_schedule_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_schedule_id']) : null;

    // Check if the date already exists, excluding the current schedule if provided
    $sql_check_status = "SELECT id FROM schedule WHERE schedule_date = ? AND schedule_status != 'cancelled'";
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

    if ($stmt_check_date->num_rows > 0) {
        echo json_encode(['status' => 'exists']);
    } else {
        echo json_encode(['status' => 'available']);
    }

    $stmt_check_date->close();
    $conn->close();
}

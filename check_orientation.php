<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $exclude_orientation_id = isset($_POST['exclude_orientation_id']) ? mysqli_real_escape_string($conn, $_POST['exclude_orientation_id']) : null;

    // Prepare SQL query to check for conflicting dates in the orientation table
    $sql_check_status = "SELECT id, orientation_status FROM orientation WHERE orientation_date = ? AND orientation_status NOT IN ('finished', 'denied')";
    if ($exclude_orientation_id) {
        $sql_check_status .= " AND id != ?";
    }
    
    $stmt_check_date = $conn->prepare($sql_check_status);
    if ($exclude_orientation_id) {
        $stmt_check_date->bind_param("ss", $date, $exclude_orientation_id);
    } else {
        $stmt_check_date->bind_param("s", $date);
    }
    $stmt_check_date->execute();
    $stmt_check_date->store_result();

    $response = ['status' => 'available'];

    if ($stmt_check_date->num_rows > 0) {
        // Conflicting orientation found, fetch the status
        $stmt_check_date->bind_result($id, $orientation_status);
        $stmt_check_date->fetch();
        $response = ['status' => 'exists', 'orientation_status' => $orientation_status];
    }

    $stmt_check_date->close();
    $conn->close();
    echo json_encode($response);
}
?>

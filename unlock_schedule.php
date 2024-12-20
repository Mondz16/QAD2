<?php
header('Content-Type: application/json');
include 'connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['scheduleId'], $data['hours'], $data['minutes'])) {
    $scheduleId = $data['scheduleId'];
    $hours = $data['hours'];
    $minutes = $data['minutes'];

    // Calculate unlock expiration time
    $unlockExpiration = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $unlockExpiration->modify("+$hours hours +$minutes minutes");
    $unlockExpirationStr = $unlockExpiration->format('Y-m-d H:i:s');

    // Update the schedule in the database
    $updateSql = "UPDATE schedule SET schedule_status = 'approved' , status_date = NOW(), manually_unlocked = 1, unlock_expiration = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param('si', $unlockExpirationStr, $scheduleId);
    $stmt->execute();   

    if ($stmt->affected_rows > 0) {
        echo json_encode(['message' => 'Schedule unlocked successfully.']);
    } else {
        echo json_encode(['message' => 'Failed to unlock the schedule.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['message' => 'Invalid input.']);
}
?>

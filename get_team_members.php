<?php
include 'connection.php';

$schedule_id = intval($_GET['schedule_id']);

$sql = "
    SELECT t.id, iu.first_name, iu.last_name, t.role
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    WHERE t.schedule_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

$team_members = [];

while ($row = $result->fetch_assoc()) {
    $team_members[] = [
        'id' => $row['id'],
        'name' => $row['first_name'] . ' ' . $row['last_name'],
        'role' => $row['role']
    ];
}

echo json_encode($team_members);

$stmt->close();
$conn->close();
?>

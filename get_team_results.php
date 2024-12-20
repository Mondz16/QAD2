<?php
include 'connection.php';

$schedule_id = $_GET['schedule_id'];

$sql = "
    SELECT t.role, a.result
    FROM team t
    LEFT JOIN assessment a ON t.id = a.team_id
    WHERE t.schedule_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stmt->bind_result($role, $result);

$results = [];
while ($stmt->fetch()) {
    if ($role !== 'team leader' && $result) {
        $results[] = $result;
    }
}

$stmt->close();
$conn->close();

echo json_encode($results);
?>

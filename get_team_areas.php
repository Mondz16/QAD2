<?php
include 'connection.php';

$schedule_id = $_GET['schedule_id'];

$sql = "
    SELECT area 
    FROM team 
    WHERE schedule_id = ? AND status = 'accepted'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stmt->bind_result($area);

$areas = [];
while ($stmt->fetch()) {
    if (!empty($area)) {
        $areas[] = $area;
    }
}

$stmt->close();
$conn->close();

echo json_encode($areas);
?>

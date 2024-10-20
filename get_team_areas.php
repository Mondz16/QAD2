<?php
include 'connection.php';

$schedule_id = $_GET['schedule_id'];

// Modified SQL query to join team and team_areas tables
$sql = "
    SELECT a.id AS area_id, a.area_name, ta.rating 
    FROM area a
    LEFT JOIN team_areas ta ON a.id = ta.area_id
    LEFT JOIN team t ON ta.team_id = t.id
    WHERE t.schedule_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stmt->bind_result($area_id, $area_name, $rating);

$areas = [];
while ($stmt->fetch()) {
    $areas[] = [
        'area_id' => $area_id,
        'area_name' => $area_name,
        'rating' => $rating // This can be null if not rated
    ];
}

$stmt->close();
$conn->close();

echo json_encode($areas);

?>

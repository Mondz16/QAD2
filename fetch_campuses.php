<?php
include 'connection.php';

$sql = "SELECT DISTINCT college_campus FROM college ORDER BY college_campus";
$result = $conn->query($sql);

$campuses = [];
while ($row = $result->fetch_assoc()) {
    $campuses[] = $row['college_campus'];
}

echo json_encode($campuses);
?>

<?php
include 'connection.php';

$sql = "SELECT code, college_name FROM college ORDER BY college_name";
$result = $conn->query($sql);

$colleges = [];
while ($row = $result->fetch_assoc()) {
    $colleges[] = ['code' => $row['code'], 'name' => $row['college_name']];
}

echo json_encode($colleges);
?>

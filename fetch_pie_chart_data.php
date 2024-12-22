<?php
// Database connection
include 'connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data for pie chart
$sql = "
    SELECT c.college_campus, 
           COUNT(DISTINCT c.code) AS college_count, 
           COUNT(p.id) AS program_count
    FROM college c
    LEFT JOIN program p ON c.code = p.college_code
    GROUP BY c.college_campus";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
    SELECT 
        p.program_name, 
        plh.program_level, 
        plh.date_received
    FROM 
        program_level_history plh
    JOIN 
        program p ON plh.program_id = p.id
    ORDER BY 
        plh.date_received DESC
    LIMIT 5
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>

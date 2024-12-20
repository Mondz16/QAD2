<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$area_code = isset($_GET['area_code']) ? intval($_GET['area_code']) : 0;

$sql = "SELECT parameter_name, parameter_description 
        FROM parameters 
        WHERE area_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $area_code);
$stmt->execute();
$result = $stmt->get_result();

$parameters = [];
while ($row = $result->fetch_assoc()) {
    $parameters[] = $row;
}

echo json_encode($parameters);
?>
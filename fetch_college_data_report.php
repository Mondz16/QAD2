<?php
include 'connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$campus = $_GET['programLevel']; // We're reusing the programLevel parameter for campus
$year = $_GET['year'];

$whereClause = $campus != "All" ? "WHERE c.college_campus = '$campus'" : "";

$sql = "
    SELECT 
        c.college_campus, 
        SUM(CASE WHEN plh.program_level = 'No Graduates Yet' THEN 1 ELSE 0 END) AS 'Not Accreditable',
        SUM(CASE WHEN plh.program_level = 'Candidate' || plh.program_level = 'PSV' THEN 1 ELSE 0 END) AS 'Candidate',
        SUM(CASE WHEN plh.program_level = '1' THEN 1 ELSE 0 END) AS '1',
        SUM(CASE WHEN plh.program_level = '2' THEN 1 ELSE 0 END) AS '2',
        SUM(CASE WHEN plh.program_level = '3' THEN 1 ELSE 0 END) AS '3',
        SUM(CASE WHEN plh.program_level = '4' THEN 1 ELSE 0 END) AS '4'
    FROM 
        college c
    LEFT JOIN 
        program p ON c.code = p.college_code
    LEFT JOIN (
        SELECT plh.*
        FROM program_level_history plh
        INNER JOIN (
            SELECT program_id, MAX(id) as latest_history_id
            FROM program_level_history
            WHERE ('$year' = 'All' OR YEAR(date_received) = '$year')
            GROUP BY program_id
        ) latest ON plh.id = latest.latest_history_id
    ) plh ON p.id = plh.program_id
    $whereClause
    GROUP BY 
        c.college_campus
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);
?>
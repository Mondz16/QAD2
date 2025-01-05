<?php
include 'connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$programLevel = $_GET['programLevel'];
$year = $_GET['year'];

if ($programLevel == "All") {
    $sql = "
        SELECT 
            c.college_campus, 
            SUM(CASE WHEN plh.program_level = 'No Graduates Yet' THEN 1 ELSE 0 END) AS 'No Graduates Yet',
            SUM(CASE WHEN plh.program_level = 'Candidate' THEN 1 ELSE 0 END) AS 'Candidate',
            SUM(CASE WHEN plh.program_level = 'PSV' THEN 1 ELSE 0 END) AS 'PSV',
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
        GROUP BY 
            c.college_campus
    ";
} else {
    $sql = "
        SELECT 
            c.college_campus, 
            COUNT(plh.id) as program_count
        FROM 
            college c
        LEFT JOIN 
            program p ON c.code = p.college_code
        LEFT JOIN 
            program_level_history plh ON p.id = plh.program_id
        WHERE 
            plh.program_level = '$programLevel' AND
            ('$year' = 'All' OR YEAR(plh.date_received) = '$year')
        GROUP BY 
            c.college_campus
    ";
}

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

echo json_encode($data);

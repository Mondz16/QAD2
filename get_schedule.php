<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['schedule_id'])) {
    $schedule_id = $_GET['schedule_id'];

    $sql = "SELECT s.id, s.schedule_date, s.schedule_time, s.level_applied, s.schedule_status, 
            p.program_name, c.college_name, c.college_campus
            FROM schedule s
            JOIN program p ON s.program_id = p.id
            JOIN college c ON s.college_code = c.code
            WHERE s.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        echo "<p><strong>Program:</strong> " . htmlspecialchars($row['program_name']) . "</p>";
        echo "<p><strong>College:</strong> " . htmlspecialchars($row['college_name']) . " (" . htmlspecialchars($row['college_campus']) . ")</p>";
        echo "<p><strong>Date:</strong> " . htmlspecialchars($row['schedule_date']) . "</p>";
        echo "<p><strong>Time:</strong> " . htmlspecialchars($row['schedule_time']) . "</p>";
        echo "<p><strong>Level Applied:</strong> " . htmlspecialchars($row['level_applied']) . "</p>";
        echo "<p><strong>Status:</strong> " . htmlspecialchars($row['schedule_status']) . "</p>";
    } else {
        echo "<p>No details found for the selected schedule.</p>";
    }

    $stmt->close();
}

$conn->close();
?>

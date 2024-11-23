<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_POST['action'] === 'getProgramSchedule') {
    $search = $_POST['search'];
    $offset = (int)$_POST['offset'];

    $schedules = getProgramSchedules($conn, $search, $offset);

    echo json_encode([
        'recordsTotal' => count($schedules),
        'recordsFiltered' => count($schedules),
        'data' => $schedules
    ]);
}

function getProgramSchedules($conn, $search, $offset) {
    $query = "
        SELECT 
            program.program_name,
            COUNT(schedule.id) AS total_schedule_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS canceled_count
        FROM program
        INNER JOIN schedule ON program.id = schedule.program_id
        WHERE program.program_name LIKE ?
        GROUP BY program.id
        ORDER BY total_schedule_count DESC
        LIMIT 10 OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $search_param = "%$search%";
    $stmt->bind_param('si', $search_param, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    return $schedules;
}
?>

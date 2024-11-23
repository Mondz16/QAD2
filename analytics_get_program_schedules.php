<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Execute only if the action is 'getProgramSchedule'
if ($_POST['action'] === 'getProgramSchedule') {
    // Retrieve parameters from the POST request
    $search = $_POST['search'] ?? '';
    $offset = (int)$_POST['offset'];
    $college_code = $_POST['college_code'] ?? ''; // Get college_code if provided
    $year = $_POST['year'] ?? date('Y'); // Default to current year if not provided

    // Call the function with all required parameters
    $schedules = getProgramSchedules($conn, $search, $offset, $college_code, $year);

    // Return JSON response
    echo json_encode([
        'recordsTotal' => count($schedules),
        'recordsFiltered' => count($schedules),
        'data' => $schedules
    ]);
}
elseif ($_POST['action'] == 'getColleges') {
    $campus = $_POST['campus'];
    $colleges = getCollegesByCampus($conn, $campus);
    echo json_encode($colleges);
}

/**
 * Fetch program schedules from the database.
 *
 * @param mysqli $conn Database connection
 * @param string $search Search query
 * @param int $offset Pagination offset
 * @param string $college_code Filter by college code
 * @param string $year Filter by year
 * @return array List of schedules
 */
function getProgramSchedules($conn, $search, $offset, $college_code, $year) {
    // Base query
    $query = "
        SELECT 
            program.program_name,
            COUNT(schedule.id) AS total_schedule_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS canceled_count
        FROM program
        INNER JOIN schedule ON program.id = schedule.program_id
        WHERE program.program_name LIKE ?
          AND YEAR(schedule.schedule_date) = ?
    ";

    // Add college_code filter if provided
    if (!empty($college_code)) {
        $query .= " AND schedule.college_code = ? ";
    }

    $query .= "
        GROUP BY program.id
        ORDER BY total_schedule_count DESC
        LIMIT 10 OFFSET ?";

    // Prepare and bind parameters
    $stmt = $conn->prepare($query);
    $search_param = "%$search%";

    if (!empty($college_code)) {
        $stmt->bind_param('sssi', $search_param, $year, $college_code, $offset);
    } else {
        $stmt->bind_param('ssi', $search_param, $year, $offset);
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results into an array
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    return $schedules;
}

function getCollegesByCampus($conn, $campus)
{
    $query = "SELECT code, college_name FROM college WHERE college_campus = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $campus);
    $stmt->execute();
    $result = $stmt->get_result();

    $colleges = [];
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }

    return $colleges;
}

?>

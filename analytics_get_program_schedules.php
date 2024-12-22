<?php
include 'connection.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Execute only if the action is 'getProgramSchedule'
if ($_POST['action'] === 'getProgramSchedule') {
    $search = $_POST['search'] ?? '';
    $offset = (int)$_POST['offset'];
    $limit = (int)$_POST['limit'] ?? 15;
    $sort_column = $_POST['sort_column'] ?? 'total_schedule_count'; // Default sort column
    $sort_direction = strtoupper($_POST['sort_direction'] ?? 'DESC'); // Default sort direction
    $college_code = $_POST['college_code'] ?? '';
    $year = $_POST['year'] ?? date('Y');

    // Whitelist columns to prevent SQL injection
    $allowed_columns = ['program_name', 'approved_count', 'total_reschedule_count', 'canceled_count', 'total_schedule_count'];
    if (!in_array($sort_column, $allowed_columns)) {
        $sort_column = 'total_schedule_count';
    }
    if (!in_array($sort_direction, ['ASC', 'DESC'])) {
        $sort_direction = 'DESC';
    }

    // Get total record count
    $totalQuery = "
        SELECT COUNT(DISTINCT program.id) AS total
        FROM program
        INNER JOIN schedule ON program.id = schedule.program_id
        WHERE YEAR(schedule.schedule_date) = ? AND program.program_name LIKE ?
    ";
    if (!empty($college_code)) {
        $totalQuery .= " AND schedule.college_code = ?";
    }

    $stmt = $conn->prepare($totalQuery);
    $search_param = "%$search%";
    if (!empty($college_code)) {
        $stmt->bind_param('sss', $year, $search_param, $college_code);
    } else {
        $stmt->bind_param('ss', $year, $search_param);
    }
    $stmt->execute();
    $totalResult = $stmt->get_result()->fetch_assoc();
    $recordsTotal = $totalResult['total'];

    // Fetch sorted and paginated results
    $query = "
        SELECT 
            program.program_name,
            COUNT(schedule.id) AS total_schedule_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status IN ('approved', 'done', 'finished', 'passed', 'failed') THEN 1 ELSE 0 END), 0) AS approved_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS canceled_count,
            COALESCE(SUM(schedule.reschedule_count), 0) AS total_reschedule_count
        FROM program
        INNER JOIN schedule ON program.id = schedule.program_id
        WHERE program.program_name LIKE ?
          AND YEAR(schedule.schedule_date) = ?
    ";
    if (!empty($college_code)) {
        $query .= " AND schedule.college_code = ? ";
    }
    $query .= "
        GROUP BY program.id
        ORDER BY $sort_column $sort_direction
        LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    if (!empty($college_code)) {
        $stmt->bind_param('sssii', $search_param, $year, $college_code, $limit, $offset);
    } else {
        $stmt->bind_param('ssii', $search_param, $year, $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode([
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsTotal, // Update if filters are added
        'data' => array_map(function ($row) {
            return [
                'program_name' => $row['program_name'],
                'approved_count' => $row['approved_count'],
                'total_reschedule_count' => $row['total_reschedule_count'],
                'canceled_count' => $row['canceled_count'],
                'total_schedule_count' => $row['total_schedule_count']
            ];
        }, $schedules)
    ]);
} elseif ($_POST['action'] == 'getColleges') {
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
function getProgramSchedules($conn, $search, $offset, $college_code, $year)
{
    // Base query
    $query = "
        SELECT 
            program.program_name,
            COUNT(schedule.id) AS total_schedule_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status IN ('approved', 'done', 'finished', 'passed', 'failed') THEN 1 ELSE 0 END), 0) AS approved_count,
            COALESCE(SUM(CASE WHEN schedule.schedule_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS canceled_count,
            COALESCE(SUM(schedule.reschedule_count), 0) AS total_reschedule_count
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
        LIMIT 20 OFFSET ?";

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

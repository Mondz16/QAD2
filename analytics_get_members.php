<?php
include 'connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_POST['action'] === 'getMembers') {
    $campus = $_POST['campus'];
    $college = $_POST['college'];
    $search = $_POST['search'];
    $offset = (int)$_POST['offset'];
    $year = $_POST['year'];

    $members = getMembers($conn, $campus, $college, $search, $offset, $year);

    // Return the members data in a format that DataTables expects
    echo json_encode([
        'recordsTotal' => count($members), // Update this with the total count of records
        'recordsFiltered' => count($members), // Update this with the filtered count
        'data' => $members
    ]);
} elseif ($_POST['action'] == 'getColleges') {
    $campus = $_POST['campus'];
    $colleges = getCollegesByCampus($conn, $campus);
    echo json_encode($colleges);
}

function getMembers($conn, $campus, $college, $search, $offset, $year)
{
    if ($year == 'all') {
        $query = "SELECT 
                  internal_users.first_name, 
                  internal_users.last_name, 
                  COALESCE(COUNT(team.id), 0) AS schedule_count,
                  COALESCE(SUM(CASE WHEN team.status = 'accepted' OR team.status = 'finished' THEN 1 ELSE 0 END), 0) AS accepted_count,
                  COALESCE(SUM(CASE WHEN team.status = 'declined' THEN 1 ELSE 0 END), 0) AS declined_count,
                  COALESCE(SUM(CASE WHEN team.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count
              FROM internal_users 
              LEFT JOIN team ON internal_users.user_id = team.internal_users_id
              LEFT JOIN schedule ON team.schedule_id = schedule.id 
                                  AND schedule.schedule_status NOT IN ('cancelled')
              LEFT JOIN program ON schedule.program_id = program.id
              WHERE (CONCAT(internal_users.first_name, ' ', internal_users.last_name) LIKE ?)
                AND (internal_users.college_code LIKE ? OR ? = '')
              GROUP BY internal_users.user_id
              ORDER BY schedule_count DESC
              LIMIT 10 OFFSET ?";
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param('sssi', $search_param, $college, $college, $offset);
    } else {
        // Original query with year condition
        $query = "SELECT 
            internal_users.first_name,
            internal_users.last_name,
            COALESCE(COUNT(DISTINCT CASE WHEN YEAR(schedule.schedule_date) = ? THEN team.id END), 0) AS schedule_count,
            COALESCE(SUM(CASE 
                WHEN YEAR(schedule.schedule_date) = ? AND (team.status = 'accepted' OR team.status = 'finished') 
                THEN 1 ELSE 0 
            END), 0) AS accepted_count,
            COALESCE(SUM(CASE 
                WHEN YEAR(schedule.schedule_date) = ? AND team.status = 'declined' 
                THEN 1 ELSE 0 
            END), 0) AS declined_count,
            COALESCE(SUM(CASE 
                WHEN YEAR(schedule.schedule_date) = ? AND team.status = 'pending' 
                THEN 1 ELSE 0 
            END), 0) AS pending_count
        FROM internal_users
        LEFT JOIN team ON internal_users.user_id = team.internal_users_id
        LEFT JOIN schedule ON team.schedule_id = schedule.id 
            AND schedule.schedule_status NOT IN ('cancelled')
        LEFT JOIN program ON schedule.program_id = program.id
        WHERE (CONCAT(internal_users.first_name, ' ', internal_users.last_name) LIKE ?)
            AND (internal_users.college_code LIKE ? OR ? = '')
        GROUP BY internal_users.user_id
        ORDER BY schedule_count DESC
        LIMIT 10 OFFSET ?";

        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param(
            'iiiisssi',
            $year,
            $year,
            $year,
            $year,
            $search_param,
            $college,
            $college,
            $offset
        );
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    return $members;
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

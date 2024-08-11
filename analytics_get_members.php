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

if ($_POST['action'] == 'getMembers') {
    $campus = $_POST['campus'];
    $college = $_POST['college'];
    $search = $_POST['search'];
    $offset = $_POST['offset'];

    $members = getMembers($conn, $campus, $college, $search, $offset);

    $response = [
        'recordsTotal' => count($members),
        'recordsFiltered' => count($members),
        'data' => $members
    ];

    echo json_encode($response);
} elseif ($_POST['action'] == 'getColleges') {
    $campus = $_POST['campus'];
    $colleges = getCollegesByCampus($conn, $campus);
    echo json_encode($colleges);
}

function getMembers($conn, $campus, $college, $search, $offset) {
    $query = "SELECT internal_users.first_name, internal_users.last_name, COUNT(team.id) AS schedule_count
              FROM internal_users 
              LEFT JOIN team ON internal_users.user_id = team.internal_users_id
              LEFT JOIN schedule ON team.schedule_id = schedule.id
              LEFT JOIN program ON schedule.program_id = program.id
              WHERE (CONCAT(internal_users.first_name, ' ', internal_users.last_name) LIKE ?)
                AND (internal_users.college_code LIKE ? OR ? = '')
              GROUP BY internal_users.user_id
              LIMIT 10 OFFSET ?";
    $stmt = $conn->prepare($query);
    $search_param = "%$search%";
    $stmt->bind_param('sssi', $search_param, $college, $college, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    return $members;
}

function getCollegesByCampus($conn, $campus) {
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
<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    // Prepare and execute the query to fetch users based on the logic provided
    $sql = "
        SELECT iu.user_id, CONCAT(iu.first_name, ' ', iu.middle_initial, ' ', iu.last_name) AS name
        FROM internal_users iu
        WHERE iu.status = 'approved'
        AND iu.college_id != ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the users and store in arrays
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['user_id'],
            'name' => $row['name']
        ];
    }

    // Output the users in JSON format
    echo json_encode([
        'teamLeaders' => $users,
        'teamMembers' => $users
    ]);
} else {
    // Invalid request
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>

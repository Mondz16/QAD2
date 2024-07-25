<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    // Prepare and execute the combined query
    $sql = "
        SELECT iu.user_id, CONCAT(iu.first_name, ' ', iu.middle_initial, ' ', iu.last_name) AS name,
               (SELECT COUNT(*) FROM team t 
                JOIN schedule s ON t.schedule_id = s.id 
                WHERE t.internal_users_id = iu.user_id 
                AND s.schedule_status NOT IN ('cancelled', 'finished')) AS count
        FROM internal_users iu
        WHERE iu.status = 'active'
        AND iu.college_code != ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the users and store in arrays
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['user_id'],
            'name' => $row['name'],
            'count' => $row['count']
        ];
    }

    // Output the users in JSON format
    $response = [
        'teamLeaders' => $users,
        'teamMembers' => $users
    ];

    echo json_encode($response);
} else {
    // Invalid request
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>

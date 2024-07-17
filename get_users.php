<?php
include 'connection.php';

if (isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    // Query to get users from the selected college, excluding users in the team table
    $sql = "
        SELECT u.user_id, u.first_name, u.middle_initial, u.last_name
        FROM internal_users u
        WHERE u.college = (
            SELECT college_name FROM college WHERE id = ?
        )
        AND u.user_id NOT IN (
            SELECT t.internal_users_id FROM team t
            JOIN schedule s ON t.schedule_id = s.id
            WHERE s.college_id = ?
        )
        AND u.status = 'approved'
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $college_id, $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => $row['user_id'],
            'first_name' => $row['first_name'],
            'middle_initial' => $row['middle_initial'],
            'last_name' => $row['last_name']
        ];
    }

    echo json_encode($users);
}
?>

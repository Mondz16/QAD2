<?php
include 'connection.php';

$schedule_id = $_GET['schedule_id'];

$sql = "SELECT t.id as team_id, t.internal_users_id, t.role, t.status, u.first_name, u.middle_initial, u.last_name
        FROM team t
        JOIN internal_users u ON t.internal_users_id = u.user_id
        WHERE t.schedule_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<table>';
    echo '<tr><th>First Name</th><th>Middle Initial</th><th>Last Name</th><th>Role</th><th>Status</th><th>Actions</th></tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['middle_initial']) . '</td>';
        echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['role']) . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>';
        echo '<button onclick="viewUser(\'' . $row['internal_users_id'] . '\')">View User</button>';
        if ($row['status'] == 'declined') {
            echo '<button onclick="changeUser(' . $row['team_id'] . ')">Change User</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo 'No team members found for this schedule.';
}

$stmt->close();
$conn->close();
?>

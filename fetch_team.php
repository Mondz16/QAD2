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
    echo '<tr><th>NAME</th><th>ROLE</th><th>STATUS</th><th>ACTIONS</th></tr>';
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['first_name'] . " " . $row['middle_initial'] . " " . $row["last_name"];
        echo '<tr>';
        echo '<td>' . htmlspecialchars($full_name) . '</td>';
        echo '<td>' . htmlspecialchars($row['role']) . '</td>';
        echo '<td>' . htmlspecialchars($row['status']) . '</td>';
        echo '<td>';
        echo '<button onclick="viewUser(\'' . $row['internal_users_id'] . '\')">View</button>';
        if ($row['status'] == 'declined') {
            echo '<button onclick="changeUser(' . $row['team_id'] . ')">Change</button>';
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

<?php
include 'connection.php';

$sql = "SELECT team_leader_id, GROUP_CONCAT(team_member_id) AS team_members FROM schedule GROUP BY team_leader_id";
$result = $conn->query($sql);

$existingMembers = [];
while ($row = $result->fetch_assoc()) {
    $existingMembers[] = [
        'user_id' => $row['team_leader_id'],
        'team_members' => explode(',', $row['team_members'])
    ];
}

echo json_encode($existingMembers);

$conn->close();
?>

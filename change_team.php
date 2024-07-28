<?php
include 'connection.php';

$team_id = intval($_POST['team_id']);
$new_user_id = intval($_POST['new_user_id']);

$sql = "UPDATE team SET internal_users_id = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $new_user_id, $team_id);

if ($stmt->execute()) {
    echo "Team member changed successfully.";
} else {
    echo "Error changing team member: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

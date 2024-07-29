<?php
include 'connection.php';

$team_id = $_POST['team_id'];
$new_user = $_POST['new_user'];

$sql = "UPDATE team
        SET internal_users_id = ?, status = 'pending'
        WHERE id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_user, $team_id);

if ($stmt->execute()) {
    echo 'User changed successfully.';
} else {
    echo 'Error changing user: ' . $stmt->error;
}

$stmt->close();
$conn->close();
?>

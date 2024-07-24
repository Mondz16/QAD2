<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id = intval($_POST['team_id']);
    $schedule_id = intval($_POST['schedule_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $status = 'accepted';
    } elseif ($action === 'decline') {
        $status = 'declined';
    } else {
        header("Location: internal_notification.php");
        exit();
    }

    $sql_update = "UPDATE team SET status = ? WHERE id = ? AND schedule_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sii", $status, $team_id, $schedule_id);

    if ($stmt_update->execute()) {
        header("Location: internal_notification.php");
    } else {
        echo "Error updating notification: " . $conn->error;
    }

    $stmt_update->close();
} else {
    header("Location: internal_notification.php");
}
$conn->close();
?>
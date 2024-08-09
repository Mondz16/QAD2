<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = intval($_POST['schedule_id']);
    $areas = $_POST['areas'];

    $conn->begin_transaction();

    try {
        foreach ($areas as $team_member_id => $area) {
            // Ensure the area is not null and set to an empty string if it's blank
            $area = trim($area) === '' ? '' : $area;

            $sql_update = "UPDATE team SET area = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $area, $team_member_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // Update the team leader status only
        $sql_update_leader = "UPDATE team SET status = 'accepted' WHERE internal_users_id = ? AND schedule_id = ?";
        $stmt_update_leader = $conn->prepare($sql_update_leader);
        $stmt_update_leader->bind_param("si", $_SESSION['user_id'], $schedule_id);
        $stmt_update_leader->execute();
        $stmt_update_leader->close();

        $conn->commit();
        header("Location: internal_notification.php");
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
} else {
    header("Location: internal_notification.php");
}

?>

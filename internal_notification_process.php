<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['notification_id']) && isset($_POST['action'])) {
        $notification_id = $_POST['notification_id'];
        $action = $_POST['action'];
        
        if ($action == 'accept') {
            // Update team status to accepted
            $sql_update_status = "UPDATE team t 
                                  INNER JOIN internal_users i ON t.internal_users_id = i.user_id
                                  SET t.status = 'accepted' 
                                  WHERE i.user_id = ?";
            $stmt_update_status = $conn->prepare($sql_update_status);
            $stmt_update_status->bind_param("s", $_SESSION['user_id']);
            $stmt_update_status->execute();
            $stmt_update_status->close();
            
            // Delete the accepted notification
            $sql_delete_notification = "DELETE FROM notifications WHERE id = ?";
            $stmt_delete_notification = $conn->prepare($sql_delete_notification);
            $stmt_delete_notification->bind_param("i", $notification_id);
            $stmt_delete_notification->execute();
            $stmt_delete_notification->close();
            
            // Redirect to assessment page with schedule details
            header("Location: internal_assessment.php");
            exit();
        } elseif ($action == 'decline') {
            
            $sql_update_status = "UPDATE team t 
                                  INNER JOIN internal_users i ON t.internal_users_id = i.user_id
                                  SET t.status = 'declined' 
                                  WHERE i.user_id = ?";
            $stmt_update_status = $conn->prepare($sql_update_status);
            $stmt_update_status->bind_param("s", $_SESSION['user_id']);
            $stmt_update_status->execute();
            $stmt_update_status->close();

            $sql_delete_notification = "DELETE FROM notifications WHERE id = ?";
            $stmt_delete_notification = $conn->prepare($sql_delete_notification);
            $stmt_delete_notification->bind_param("i", $notification_id);
            $stmt_delete_notification->execute();
            $stmt_delete_notification->close();
        }
    }
}

// Redirect back to notifications page if action was not handled properly
header("Location: internal_notification.php");
exit();
?>
